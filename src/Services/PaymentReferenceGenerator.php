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
use RuntimeException;

/**
 * This service generates a payment reference for payment orders.
 */
class PaymentReferenceGenerator
{
    /**
     * Generates a payment Reference (via generatePaymentReference) and sets the reference value in the payment order.
     * Database is NOT flushed yet.
     *
     * @see PaymentReferenceGenerator::generatePaymentReference()
     */
    public function setPaymentReference(PaymentOrder $paymentOrder): void
    {
        $paymentOrder->getBankInfo()
            ->setReference($this->generatePaymentReference($paymentOrder));
    }

    /**
     * Returns a payment reference for the given payment order.
     * It contains the project name, the FSR name, and the funding ID (if existing) and the ZA-ID.
     * The length of all values are cut the way, that the reference does not exceed 140 chars.
     */
    public function generatePaymentReference(PaymentOrder $paymentOrder): string
    {
        //Max 140 chars are allowed for a payment reference
        //Format: [ProjectName 70] [FSR Name 45] [?Funding ID 11] ZA[PaymentOrder ID]

        //Project name
        $tmp = mb_strimwidth($paymentOrder->getProjectName(), 0, 70, '');
        $tmp .= ' ';
        //FSR Name
        $tmp .= mb_strimwidth($paymentOrder->getDepartment()->getName(), 0, 45, '');
        $tmp .= ' ';
        //Funding ID if existing
        if (!empty($paymentOrder->getFundingId())) {
            $tmp .= mb_strimwidth($paymentOrder->getFundingId(), 0, 11, '');
            $tmp .= ' ';
        }

        //ZA + ID
        if (null === $paymentOrder->getId()) {
            throw new RuntimeException('ID is null. You have to persist the PaymentOrder before using this function!');
        }
        $tmp .= $paymentOrder->getIDString();

        if (mb_strlen($tmp) > 140) {
            return new RuntimeException('Generated Payment reference exceeds 140 characters! This should not happen unless you have a very long ID...');
        }

        return $tmp;
    }
}
