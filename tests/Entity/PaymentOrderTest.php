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

namespace App\Tests\Entity;

use App\Entity\PaymentOrder;
use App\EventSubscriber\PaymentOrderNotificationSubscriber;
use PHPUnit\Framework\TestCase;

class PaymentOrderTest extends TestCase
{

    /**
     * @dataProvider GetIDStringDataProvider
     * @param  string  $expected
     * @param  int  $id
     */
    public function testGetIDString(string $expected, int $id): void
    {
        $payment_order = new PaymentOrder();
        $reflection = new \ReflectionClass(PaymentOrder::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($payment_order, $id);

        static::assertSame($expected, $payment_order->getIDString());
    }

    public function GetIDStringDataProvider(): array
    {
        return [
            ['ZA0001', 1],
            ['ZA0123', 123],
            ['ZA1234', 1234],
            ['ZA123456', 123456],
        ];
    }

    /**
     * @dataProvider fullNameDataProvider
     */
    public function testGetFullName(string $expected, string $first_name, string $last_name): void
    {
        $payment_order = new PaymentOrder();
        $payment_order->setFirstName($first_name)->setLastName($last_name);
        static::assertSame($expected, $payment_order->getFullName());
    }

    public function fullNameDataProvider(): array
    {
        return [
            ['John Doe', 'John', 'Doe'],
            ['Admin', 'Admin', ''],
            ['Admin', '', 'Admin'],
            ['John Jane Doe', 'John Jane', 'Doe']
        ];
    }

    /**
     * @dataProvider getAmountStringDataProvider
     * @param  string|null  $expected
     * @param  int|null  $cent_amount
     */
    public function testGetAmountStringNull(?string $expected, ?int $cent_amount): void
    {
        $payment_order = new PaymentOrder();
        static::assertNull($payment_order->getAmountString());
    }

    /**
     * @dataProvider getAmountStringDataProvider
     * @param  string|null  $expected
     * @param  int|null  $cent_amount
     */
    public function testGetAmountString(?string $expected, ?int $cent_amount): void
    {
        $payment_order = new PaymentOrder();
        $payment_order->setAmount($cent_amount);
        static::assertSame($expected, $payment_order->getAmountString());
    }

    public function getAmountStringDataProvider(): array
    {
        return [
            ['123.45', 12345],
            ['0.12', 12],
            ['0.01', 1],
            ['123.00', 12300],
            ['12345.00', 1234500],
            ['123456.00', 12345600],
        ];
    }

    public function testIsConfirmed(): void
    {
        $payment_order = new PaymentOrder();
        //By default a payment order must not be confirmed
        static::assertFalse($payment_order->isConfirmed());

        //A single confirmation must not be sufficient
        $payment_order->setConfirm1Timestamp(new \DateTime());
        static::assertFalse($payment_order->isConfirmed());

        //With both timestamps set the payment order is confirmed
        $payment_order->setConfirm2Timestamp(new \DateTime());
        static::assertTrue($payment_order->isConfirmed());

        //Test the case with the other timestamp missing
        $payment_order->setConfirm1Timestamp(null);
        static::assertFalse($payment_order->isConfirmed());
    }
}
