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

namespace App\Tests;


use App\Entity\PaymentOrder;

class PaymentOrderTestingHelper
{

    /**
     * Generates an PaymentOrder from the given array data
     * @param  array  $data
     * @return PaymentOrder
     */
    public static function arrayToPaymentOrder(array $data): PaymentOrder
    {
        $payment_order = static::getDummyPaymentOrder($data['id'] ?? 1);
        $payment_order->setFirstName($data['first_name'])
            ->setLastName($data['last_name'])
            ->setContactEmail($data['contact_email'])
            ->setAmount($data['amount'])
            ->setProjectName($data['project_name'])
            ->setFundingId($data['funding_id'] ?? '')
            ->setResolutionDate($data['resolution_date'] ?? null)
            ->setComment($data['comment'] ?? null)
            ->setDepartment($data['department'])
            ->getBankInfo()->setAccountOwner($data['account_owner'])
            ->setStreet($data['street'])
            ->setCity($data['city'])
            ->setZipCode($data['zip'])
            ->setIban($data['iban'])
            ->setBic($data['bic'])
            ->setReference($data['reference']);

        return $payment_order;
    }

    /**
     * Returns a PaymentOrder that has the given ID (that should behave like a DB registered one in many cases).
     * @param  int  $id
     * @return PaymentOrder
     */
    public static function getDummyPaymentOrder(int $id = 1): PaymentOrder
    {
        return new class($id) extends PaymentOrder {
            public function __construct(int $id)
            {
                $this->id2 = $id;
                parent::__construct();
            }

            public function getId(): ?int
            {
                return $this->id2;
            }
        };
    }
}