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

class PaymentReferenceGenerator
{
    public function setPaymentReference(PaymentOrder $paymentOrder): void
    {
        $paymentOrder->getBankInfo()->setReference($this->generatePaymentReference($paymentOrder));
    }

    public function generatePaymentReference(PaymentOrder $paymentOrder): string
    {
        //Max 140 chars are allowed for a payment reference
        //Format: [ProjectName 60] [FSR Name 50] [?Funding ID 10] ZA[PaymentOrder ID]

        //Project name
        $tmp = mb_strimwidth($paymentOrder->getProjectName(), 0, 60, '');
        $tmp .= ' ';
        //FSR Name
        $tmp .= mb_strimwidth($paymentOrder->getDepartment()->getName(), 0, 50, '');
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
        $tmp .= sprintf("ZA%04d", $paymentOrder->getId());

        return $tmp;

    }
}