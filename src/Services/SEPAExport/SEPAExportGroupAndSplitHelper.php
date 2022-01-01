<?php
/*
 * Copyright (C)  2020-2021  Jan Böhmer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Services\SEPAExport;

use App\Entity\BankAccount;
use App\Entity\Embeddable\PayeeInfo;
use App\Entity\PaymentOrder;
use App\Exception\SEPAExportAutoModeNotPossible;
use App\Exception\SinglePaymentOrderExceedsLimit;
use Doctrine\ORM\EntityManagerInterface;

class SEPAExportGroupAndSplitHelper
{
    /** @var BankAccount */
    private $fsr_kom_bank_account;

    /** @var int The maximum number of transactions which should be put in a single SEPA XML file. */
    private $limit_max_number_of_transactions;
    /** @var int The maximum sum of transactions which should be put in a single SEPA XML file. This value is in cents  */
    private $limit_max_amount;

    public function __construct(int $fsr_kom_bank_account_id, EntityManagerInterface $em, int $limit_max_number_of_transactions, int $limit_max_amount)
    {
        $this->fsr_kom_bank_account = $em->find(BankAccount::class, $fsr_kom_bank_account_id);

        $this->limit_max_number_of_transactions = $limit_max_number_of_transactions;
        $this->limit_max_amount = $limit_max_amount;
    }

    /**
     * Groups the given payment orders by their bank accounts and split the groups according to the set Limits (see limit_ service params).
     * An SplObjectStorage is returned, where the bank accounts are the keys and an array containing arrays for the multiple files are contained.
     * [BankAccount1 =>
     *      [
     *          [PaymentOrder1, PaymentOrder2],
     *          [PaymentOrder3],
     *      ]
     * ]
     * @param  PaymentOrder[]  $payment_orders
     * @throws SEPAExportAutoModeNotPossible If an element has no assigned default bank account, then automatic mode is not possible
     * @return \SplObjectStorage
     */
    public function groupAndSplitPaymentOrdersForAutoExport(array $payment_orders): \SplObjectStorage
    {
        $grouped = new \SplObjectStorage();

        //Iterate over every payment order and sort them according to their bank accounts
        foreach ($payment_orders as $payment_order) {
            //Throw exception if the associated department has no default bank account and grouping is not possible
            if ($payment_order->getDepartment()->getBankAccount() === null) {
                throw new SEPAExportAutoModeNotPossible($payment_order->getDepartment());
            }

            //Normally we just use the default bank account of the department
            $bank_account = $payment_order->getDepartment()->getBankAccount();

            //Except if we have an fsr kom transaction, then we have to use the fsr-kom bank account
            if ($payment_order->isFsrKomResolution()) {
                $bank_account = $this->fsr_kom_bank_account;
            }

            //If no array object is existing yet, create the array
            if(!is_array($grouped[$bank_account])) {
                $grouped[$bank_account] = [];
            }

            //Assign it to the grouped object
            $grouped[$bank_account][] = $payment_order;
        }

        //Split the elements for each element
        $split = new \SplObjectStorage();
        foreach ($grouped as $bank_account => $group) {
            $split[$bank_account] = $this->splitPaymentOrders($group);
        }

        return $split;
    }

    /**
     * Split the given payment orders into arrays that fit the configured limits.
     * An array of the form [ [PaymentOrder1, PaymentOrder2], [PaymentOrder3]] is returned.
     * @param  PaymentOrder[]  $input The array of payment orders that should be split
     * @param  int|null $limit_max_transactions The maximum number of transactions per split group. Set to null to use global defaults
     * @param  int|null $limit_max_amount The maximimum amount in a single split group (in cents). Set to null to use global defaults.
     * @throws SinglePaymentOrderExceedsLimit
     * @return PaymentOrder[][]
     */
    public function splitPaymentOrders(array $input, ?int $limit_max_transactions = null, ?int $limit_max_amount = null): array
    {
        if ($limit_max_amount === null) {
            $limit_max_amount = $this->limit_max_amount;
        }
        if ($limit_max_transactions === null) {
            $limit_max_transactions = $this->limit_max_number_of_transactions;
        }

        //First we split according to number of transactions
        /** @var PaymentOrder[][] $output */
        $tmp = array_chunk($input, $limit_max_transactions);

        //Limit the sum amount of each group
        $groups_exceeding_limit = $tmp;
        $output = [];
        while(!empty($groups_exceeding_limit)) {
            foreach ($groups_exceeding_limit as $key => $group) {
                /** @var PaymentOrder[] $group */
                //If the group does not exceed the limit, then remove it from the bad list and put it to output array
                if ($this->calculateSumAmountOfPaymentOrders($group) <= $limit_max_amount) {
                    unset($groups_exceeding_limit[$key]);
                    $output[] = $group;
                    continue;
                }

                //Sort it and try to split the maximum amount of elements from it:
                $group = $this->sortPaymentOrderArrayByAmount($group, false);
                //We try to extract the maximum amount of elements from the list.
                $split_index = 1;
                for ($n = 0, $nMax = count($group); $n < $nMax; $n++) {
                    $part = array_slice($group, 0, $n + 1);
                    if ($this->calculateSumAmountOfPaymentOrders($part) > $limit_max_amount) {
                        //If our group contains just a single element which exceed the limit, then throw an exception, as we can not split it further.
                        if(count($part) === 1) {
                            throw new SinglePaymentOrderExceedsLimit($part[0], $limit_max_amount);
                        }

                        $split_index = $n;
                        break;
                    }
                }

                //Split group into our two subgroups of which at least one is below the limit
                $a = array_slice($group, 0 , $split_index);
                $b = array_slice($group, $split_index);

                //Remove the old group from list and add the new split groups
                unset($groups_exceeding_limit[$key]);
                $groups_exceeding_limit[] = $a;
                $groups_exceeding_limit[] = $b;
            }
        }


        return $output;
    }

    /**
     * Calculate the sum amount of all given payment orders. Returns value in cents.
     * @param  PaymentOrder[]  $payment_orders
     * @return int
     */
    public function calculateSumAmountOfPaymentOrders(array $payment_orders): int
    {
        $sum = 0;
        foreach ($payment_orders as $payment_order) {
            $sum += $payment_order->getAmount();
        }

        return $sum;
    }

    /**
     * Sorts the given array according to the amount of the payments.
     * @param  PaymentOrder[]  $payment_orders
     * @param  bool $ascending If true the payments are sorted ascending, otherwise descending.
     * @return PaymentOrder[]
     */
    public function sortPaymentOrderArrayByAmount(array $payment_orders, bool $ascending = true): array
    {
        usort($payment_orders, function (PaymentOrder $a, PaymentOrder $b) {
           return $a->getAmount() <=> $b->getAmount();
        });

        if (!$ascending) {
            return array_reverse($payment_orders);
        }

        return $payment_orders;
    }
}