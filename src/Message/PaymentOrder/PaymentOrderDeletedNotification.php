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

namespace App\Message\PaymentOrder;

use App\Entity\PaymentOrder;

class PaymentOrderDeletedNotification
{
    public const DELETED_WHERE_FRONTEND = "frontend";
    public const DELETED_WHERE_BACKEND = "backend";

    private const DELETED_WHERE = [self::DELETED_WHERE_FRONTEND, self::DELETED_WHERE_BACKEND];

    /** @var PaymentOrder The payment order that was deleted. We need to pass the full payment order, so that we can use it even after it was removed */
    private $payment_order;

    /** @var string The user who did the deletion */
    private $blame_user;

    /** @var string Whether the payment order was deleted in backend or frontend */
    private $deleted_where;

    public function __construct(PaymentOrder $payment_order, string $blame_user, string $deleted_where)
    {
        if (!in_array($deleted_where, self::DELETED_WHERE)) {
            throw new \InvalidArgumentException('$deleted_where has an value that is not allowed!');
        }

        $this->payment_order = $payment_order;
        $this->blame_user = $blame_user;
        $this->deleted_where = $deleted_where;
    }

    public function getPaymentOrder(): PaymentOrder
    {
        return $this->payment_order;
    }

    public function getBlameUser(): string
    {
        return $this->blame_user;
    }

    public function getDeletedWhere(): string
    {
        return $this->deleted_where;
    }

}