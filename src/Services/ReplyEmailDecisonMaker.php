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

use App\Entity\Department;
use App\Entity\PaymentOrder;

final readonly class ReplyEmailDecisonMaker
{
    public function __construct(private string $fsb_email, private string $hhv_email)
    {
    }

    /**
     * Returns the reply-to email address for the given department
     * @param  Department  $department
     * @return string
     */
    public function getReplyToMailForDepartment(Department $department): string
    {
        return $department->isFSR() ? $this->fsb_email : $this->hhv_email;
    }

    /**
     * Returns the reply-to email address for the given payment order
     * @param  PaymentOrder  $paymentOrder
     * @return string
     */
    public function getReplyToMailForPaymentOrder(PaymentOrder $paymentOrder): string
    {
        if ($paymentOrder->getDepartment() === null) {
            throw new \RuntimeException('$paymentOrder must have an department defined!');
        }

        return $this->getReplyToMailForDepartment($paymentOrder->getDepartment());
    }

}