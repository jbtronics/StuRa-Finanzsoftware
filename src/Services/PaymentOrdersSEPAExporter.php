<?php
/*
 * Copyright (C) 2020  Jan Böhmer
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

namespace App\Services;


use App\Entity\PaymentOrder;
use App\Exception\SEPAExportAutoModeNotPossible;
use Digitick\Sepa\DomBuilder\DomBuilderFactory;
use Digitick\Sepa\GroupHeader;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\CustomerCreditTransferFile;
use Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory;
use Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\UnicodeString;

class PaymentOrdersSEPAExporter
{
    protected const PARTY_NAME = "StuRa FSU Jena";
    protected const ID_PREFIX = "StuRa Export";
    protected const PAYMENT_PREFIX = "Payment";

    public function export(array $payment_orders, array $options): string
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $groupHeader = new GroupHeader(
            static::ID_PREFIX . ' ' . uniqid('', false),
            static::PARTY_NAME
        );
        $sepaFile = new CustomerCreditTransferFile($groupHeader);

        $accounts = [];

        if ($options['mode'] === "manual") {
            $accounts[0] = [
                'iban' => $options['iban'],
                'bic' => $options['bic'],
                'name' => $options['name'],
                'entries' => $payment_orders,
            ];
        } elseif ($options['mode'] === "auto") {
            $accounts = $this->groupByBankAccounts($payment_orders);
        } else {
            throw new \RuntimeException("Unknown mode");
        }

        foreach($accounts as $account_info) {
            // A single payment info where all PaymentOrders are added as transactions
            $payment = new PaymentInformation(
                static::PAYMENT_PREFIX.' '.uniqid('', false),
                $account_info['iban'],
                $account_info['bic'],
                $account_info['name']
            );

            $this->addPaymentOrderTransactions($payment, $account_info['entries']);
            $payment->setBatchBooking(false);
            $sepaFile->addPaymentInformation($payment);
        }

        // Or if you want to use the format 'pain.001.001.03' instead
        $domBuilder = DomBuilderFactory::createDomBuilder($sepaFile, 'pain.001.001.03');

        return $domBuilder->asXml();
    }

    /**
     * Add the given PaymentOrders as transactions to the SEPA PaymentInformation group
     * @param  PaymentInformation  $paymentInformation
     * @param  PaymentOrder[]  $payment_orderss
     */
    protected function addPaymentOrderTransactions(PaymentInformation $payment, array $payment_orders): void
    {
        //We only have one SEPA-Payment but it contains multiple transactions (each for one PaymentOrder)
        foreach($payment_orders as $payment_order) {
            /** @var PaymentOrder $payment_order */

            $transfer = new CustomerCreditTransferInformation(
                $payment_order->getAmountString(),
                //We need a IBAN without spaces
                str_replace(' ', '',$payment_order->getBankInfo()->getIban()),
                $payment_order->getBankInfo()->getAccountOwner()
            );
            if (!empty($payment_order->getBankInfo()->getBic())) {
                $transfer->setBic($payment_order->getBankInfo()->getBic());
            }
            $transfer->setRemittanceInformation($payment_order->getBankInfo()->getReference());
            $payment->addTransfer($transfer);
        }
    }

    /**
     * This function groups the paymentOrders by bank accounts.
     * @param  PaymentOrder[]  $payment_orders
     * @return array
     */
    protected function groupByBankAccounts(array $payment_orders): array
    {
        $tmp = [];

        foreach ($payment_orders as $payment_order) {
            //Throw an error if auto mode is not possible (as bank account definitions are missing)
            if ($payment_order->getDepartment()->getBankAccount() === null) {
                throw new SEPAExportAutoModeNotPossible($payment_order->getDepartment());
            }

            $bank_account = $payment_order->getDepartment()->getBankAccount();

            //Create entry for bank account if not existing yet
            if (!isset($tmp[$bank_account->getId()])) {
                $tmp[$bank_account->getId()] = [
                    'iban' => str_replace(' ', '', $bank_account->getIban()),
                    'bic' => $bank_account->getBic(),
                    'name' => $bank_account->getExportAccountName(),
                    'entries' => []
                ];
            }

            //Add the current payment order to list
            $tmp[$bank_account->getId()]['entries'][] = $payment_order;
        }

        return $tmp;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
                                   'iban', //The IBAN of the sender
                                   'bic', //The BIC of the sender
                                   'name', //The name of the sender
                               ]);

        $resolver->setAllowedTypes('iban', ['string', 'null']);
        $resolver->setAllowedTypes('bic', ['string', 'null']);
        $resolver->setAllowedTypes('name', ['string', 'null']);

        /* Two different modes, in "manual" all transactions are put in a single payment from the given account data,
           the accounts for the payment order departments are used automatically and are put in (if needed) multiple
           payments from different accounts */
        $resolver->setDefault('mode', 'manual');
        $resolver->setAllowedValues('mode', ['auto', 'manual']);

        $resolver->setNormalizer('iban', function(Options  $options, $value) {
            if ($value === null ){
                return $value;
            }
            //Return spaces from IBAN
            return str_replace(' ', '', $value);
        });
    }
}