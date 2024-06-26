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

namespace App\Tests\Services\EmailConfirmation;

use App\Entity\Department;
use App\Entity\User;
use App\Services\EmailConfirmation\ManualConfirmationHelper;
use App\Tests\PaymentOrderTestingHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ManualConfirmationHelperTest extends WebTestCase
{
    /**
     * @var ManualConfirmationHelper
     */
    protected $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::$container->get(ManualConfirmationHelper::class);
    }

    public function testConfirmManuallyAlreadyConfirmed(): void
    {
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder();
        $payment_order->setConfirm1Timestamp(new \DateTime());
        $payment_order->setConfirm2Timestamp(new \DateTime());

        $this->expectException(\RuntimeException::class);
        //This line must fail
        $this->service->confirmManually($payment_order, 'Test Reason');
    }

    public function testConfirmManually(): void
    {
        //Create a mocked user
        $user = new User();
        $user->setFirstName('Test')
            ->setLastName('User')
            ->setUsername('test');

        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder();
        $payment_order->setDepartment(new Department());
        self::assertFalse($payment_order->isConfirmed());

        $this->service->confirmManually($payment_order, 'Test Reason', $user);

        //Assume that a notification email was sent
        $this->assertEmailCount(1);

        //Assert that the payment order is now confirmed
        self::assertTrue($payment_order->isConfirmed());

        //Assert that the reason is included in comment
        self::assertStringContainsString('Test Reason', $payment_order->getComment());
    }
}
