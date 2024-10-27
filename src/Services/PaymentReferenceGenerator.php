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
 * @see \App\Tests\Services\PaymentReferenceGeneratorTest
 */
final class PaymentReferenceGenerator
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
        //Format: R.-Nr. [Invoice-Number 20] Kd.-Nr. [Customer-Number 20]
        //Format: [ProjectName 70] [FSR Name 35] [?Funding ID 20] ZA[PaymentOrder ID 9]

        if (!empty($paymentOrder->getInvoiceNumber()) || !empty($paymentOrder->getCustomerNumber())) {
            $tmp = $this->paymentReferenceWithInvoiceAndCustomerNr($paymentOrder);
        } else {
            $tmp = $this->paymentReferenceWithoutInvoiceOrCustomerNr($paymentOrder);
        }

        if (mb_strlen($tmp) > 140) {
            return new RuntimeException('Generated Payment reference exceeds 140 characters! This should not happen unless you have a very long ID...');
        }

        return $tmp;
    }

    private function paymentReferenceWithInvoiceAndCustomerNr(PaymentOrder $paymentOrder): string
    {
        //Max 140 chars are allowed for a payment reference
        //Format: R.-Nr. [Invoice-Number 23] Kd.-Nr. [Customer-Number 23] [ProjectName 45] [FSR Name 19] [?Funding ID 20] ZA[PaymentOrder ID 9] = 139

        $tmp = '';
        if (!empty($paymentOrder->getInvoiceNumber())) {
            $tmp .= 'R.-Nr. ' . mb_strimwidth($paymentOrder->getInvoiceNumber(), 0, 23, '');
            $tmp .= ' ';
        }
        if (!empty($paymentOrder->getCustomerNumber())) {
            $tmp .= 'Kd.-Nr. ' . mb_strimwidth($paymentOrder->getCustomerNumber(), 0, 23, '');
            $tmp .= ' ';
        }

        //Project name
        $tmp .= mb_strimwidth($paymentOrder->getProjectName(), 0, 45, '');
        $tmp .= ' ';
        //FSR Name
        $tmp .= mb_strimwidth((string) $paymentOrder->getDepartment()->getName(), 0, 15, '');
        $tmp .= ' ';

        //Funding ID if existing
        if ($paymentOrder->getFundingId() !== '' && $paymentOrder->getFundingId() !== '0') {
            $tmp .= mb_strimwidth($paymentOrder->getFundingId(), 0, 20, '');
            $tmp .= ' ';
        }

        //ZA + ID
        if (null === $paymentOrder->getId()) {
            throw new RuntimeException('ID is null. You have to persist the PaymentOrder before using this function!');
        }
        $tmp .= $paymentOrder->getIDString();

        return $tmp;
    }

    private function paymentReferenceWithoutInvoiceOrCustomerNr(PaymentOrder $paymentOrder): string
    {
        //Max 140 chars are allowed for a payment reference
        //Format: [ProjectName 70] [FSR Name 35] [?Funding ID 20] ZA[PaymentOrder ID 9]  = 139

        //Project name
        $tmp = mb_strimwidth($paymentOrder->getProjectName(), 0, 70, '');
        $tmp .= ' ';
        //FSR Name
        $tmp .= mb_strimwidth((string) $paymentOrder->getDepartment()->getName(), 0, 35, '');
        $tmp .= ' ';
        //Funding ID if existing
        if ($paymentOrder->getFundingId() !== '' && $paymentOrder->getFundingId() !== '0') {
            $tmp .= mb_strimwidth($paymentOrder->getFundingId(), 0, 20, '');
            $tmp .= ' ';
        }

        //ZA + ID
        if (null === $paymentOrder->getId()) {
            throw new RuntimeException('ID is null. You have to persist the PaymentOrder before using this function!');
        }
        $tmp .= $paymentOrder->getIDString();

        return $tmp;
    }
}
