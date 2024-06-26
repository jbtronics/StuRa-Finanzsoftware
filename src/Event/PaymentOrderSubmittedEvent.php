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

namespace App\Event;

use App\Entity\PaymentOrder;
use Symfony\Contracts\EventDispatcher\Event;

final class PaymentOrderSubmittedEvent extends Event
{
    public const NAME = 'payment_order.submitted';

    public function __construct(private readonly PaymentOrder $payment_order)
    {
    }

    public function getPaymentOrder(): PaymentOrder
    {
        return $this->payment_order;
    }
}
