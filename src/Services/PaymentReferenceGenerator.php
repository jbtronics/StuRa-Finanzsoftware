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

/**
 * This service generates a payment reference for payment orders
 * @package App\Services
 */
class PaymentReferenceGenerator
{
    public function setPaymentReference(PaymentOrder $paymentOrder): void
    {
        $paymentOrder->getBankInfo()->setReference($this->generatePaymentReference($paymentOrder));
    }

    /**
     * Returns a payment reference for the given payment order.
     * It contains the project name, the FSR name, and the funding ID (if existing) and the ZA-ID.
     * The length of all values are cut the way, that the reference does not exceed 140 chars.
     * @param  PaymentOrder  $paymentOrder
     * @return string
     */
    public function generatePaymentReference(PaymentOrder $paymentOrder): string
    {
        //Max 140 chars are allowed for a payment reference
        //Format: [ProjectName 70] [FSR Name 45] [?Funding ID 10] ZA[PaymentOrder ID]

        //Project name
        $tmp = mb_strimwidth($paymentOrder->getProjectName(), 0, 70, '');
        $tmp .= ' ';
        //FSR Name
        $tmp .= mb_strimwidth($paymentOrder->getDepartment()->getName(), 0, 45, '');
        $tmp .= ' ';
        //Funding ID if existing
        if (!empty($paymentOrder->getFundingId())) {
            $tmp .= $paymentOrder->getFundingId();
            $tmp .= ' ';
        }

        //ZA + ID
        if ($paymentOrder->getId() === null) {
            throw new \RuntimeException('ID is null. You have to persist the PaymentOrder before using this function!');
        }
        $tmp .= $paymentOrder->getIDString();

        return $tmp;

    }
}