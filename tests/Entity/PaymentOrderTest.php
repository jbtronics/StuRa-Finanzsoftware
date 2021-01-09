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
use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PaymentOrderTest extends TestCase
{
    /**
     * @dataProvider GetIDStringDataProvider
     */
    public function testGetIDString(string $expected, int $id): void
    {
        $payment_order = new PaymentOrder();
        $reflection = new ReflectionClass(PaymentOrder::class);
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
        $payment_order->setFirstName($first_name)
            ->setLastName($last_name);
        static::assertSame($expected, $payment_order->getFullName());
    }

    public function fullNameDataProvider(): array
    {
        return [
            ['John Doe', 'John', 'Doe'],
            ['Admin', 'Admin', ''],
            ['Admin', '', 'Admin'],
            ['John Jane Doe', 'John Jane', 'Doe'],
        ];
    }

    /**
     * @dataProvider getAmountStringDataProvider
     */
    public function testGetAmountStringNull(?string $expected, ?int $cent_amount): void
    {
        $payment_order = new PaymentOrder();
        static::assertNull($payment_order->getAmountString());
    }

    /**
     * @dataProvider getAmountStringDataProvider
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
        $payment_order->setConfirm1Timestamp(new DateTime());
        static::assertFalse($payment_order->isConfirmed());

        //With both timestamps set the payment order is confirmed
        $payment_order->setConfirm2Timestamp(new DateTime());
        static::assertTrue($payment_order->isConfirmed());

        //Test the case with the other timestamp missing
        $payment_order->setConfirm1Timestamp(null);
        static::assertFalse($payment_order->isConfirmed());
    }

    public function testSetFactuallyCorrect(): void
    {
        $payment_order = new PaymentOrder();

        static::assertNull($payment_order->getBookingDate());

        //If a payment order is factually checked, booking date must be set
        $payment_order->setFactuallyCorrect(true);
        static::assertNotNull($payment_order->getBookingDate());

        //If factually correct is revoked, then the booking was not done yet.
        $payment_order->setFactuallyCorrect(false);
        static::assertNull($payment_order->getBookingDate());
    }

    /**
     * @dataProvider fundingIDRegexDataProvider
     */
    public function testFundingIDRegex(bool $expected, string $funding_id): void
    {
        if ($expected) {
            self::assertRegExp(PaymentOrder::FUNDING_ID_REGEX, $funding_id);
        } else {
            self::assertNotRegExp(PaymentOrder::FUNDING_ID_REGEX, $funding_id);
        }
    }

    public function fundingIDRegexDataProvider(): array
    {
        return [
            //Simple cases
            [true, 'M-001-2020'],
            [true, 'M-123-2020'],
            [true, 'FA-001-2020'],
            [true, 'FA-123-2020'],
            //Higher years must be allowed
            [true, 'FA-123-2022'],
            [true, 'FA-123-2099'],
            //Number must have 3 digits
            [false, 'FA-1-2020'],
            [false, 'M-01-2020'],
            //4 digits are allowed too (though we will not need it)
            [true, 'M-1234-2020'],
            [true, 'FA-1234-2020'],
            //Other prefixes are not allowed
            [false, 'PA-123-2020'],
            //Additonal chars are not allowed
            [false, 'FA--123-2020'],
            [false, 'FA-1a2-2020'],
            [false, 'FA-123-20a20'],
        ];
    }
}
