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

use App\Entity\Confirmer;
use App\Entity\Department;
use App\Entity\User;
use App\Services\EmailConfirmation\ManualConfirmationHelper;
use App\Tests\PaymentOrderTestingHelper;
use Doctrine\Common\Collections\ArrayCollection;
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
        $this->service = self::getContainer()->get(ManualConfirmationHelper::class);
    }

    public function testConfirmManuallyAlreadyConfirmed(): void
    {
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder();

        $payment_order->getConfirmation1()->setTimestamp(new \DateTime());
        $payment_order->getConfirmation2()->setTimestamp(new \DateTime());

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
        $confirmer = new Confirmer();
        $confirmer->setEmail('confirmer1@invalid.com');
        $payment_order->setDepartment((new Department())->setConfirmers(new ArrayCollection([$confirmer])));
        self::assertFalse($payment_order->isConfirmed());

        $this->service->confirmManually($payment_order, 'Test Reason', $user);

        //Assume that two notification email was sent (one for the manual confirmation, and the second one for the confirmation with the form)
        self::assertEmailCount(2);

        //Assert that the payment order is now confirmed
        self::assertTrue($payment_order->isConfirmed());

        //Assert that the reason is included in comment
        self::assertStringContainsString('Test Reason', $payment_order->getConfirmation1()->getRemark());
        self::assertTrue($payment_order->getConfirmation1()->isConfirmationOverriden());
    }
}
