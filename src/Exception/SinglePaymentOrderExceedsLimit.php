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

namespace App\Exception;

use App\Entity\PaymentOrder;

class SinglePaymentOrderExceedsLimit extends \RuntimeException
{
    private $payment_order;
    private $limit_max_amount;

    public function __construct(PaymentOrder $paymentOrder, int $limit_max_amount)
    {
        parent::__construct("The paymentOrder amount exceeds the limit for a single transaction!");
        $this->payment_order = $paymentOrder;
        $this->limit_max_amount = $limit_max_amount;
    }

    /**
     * Returns the payment order which caused this exception.
     * @return PaymentOrder
     */
    public function getPaymentOrder(): PaymentOrder
    {
        return $this->payment_order;
    }

    /**
     * Returns the limit that was exceeded.
     * @return int
     */
    public function getLimitMaxAmount(): int
    {
        return $this->limit_max_amount;
    }

}