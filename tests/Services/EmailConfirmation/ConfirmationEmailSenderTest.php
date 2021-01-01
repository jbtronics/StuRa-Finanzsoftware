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
use App\Services\EmailConfirmation\ConfirmationEmailSender;
use App\Tests\PaymentOrderTestingHelper;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group slow
 */
class ConfirmationEmailSenderTest extends WebTestCase
{
    /**
     * @var ConfirmationEmailSender
     */
    protected $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::$container->get(ConfirmationEmailSender::class);
    }

    public function testSendConfirmation1SendEmail(): void
    {
        $department = new Department();
        $department->setEmailHhv(['test@invalid.com', 'test2@invalid.com']);
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder()->setDepartment($department);

        $this->service->sendConfirmation1($payment_order);

        //It is important that a token was set and no timestamp
        self::assertNotEmpty($payment_order->getConfirm1Token());
        self::assertNull($payment_order->getConfirm1Timestamp());

        //Ensure that an email was sent
        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);

        //Email addresses are sent as BCC, and all emails in array must be present
        self::assertEmailAddressContains($email, 'bcc', 'test@invalid.com');
        self::assertEmailAddressContains($email, 'bcc', 'test2@invalid.com');

        //The from email is the one configured in .env
        self::assertEmailAddressContains($email, 'from', 'from@invalid.com');
        //Reply to is FSB email
        self::assertEmailAddressContains($email, 'reply-to', 'fsb@invalid.com');
    }

    public function testSendConfirmation1NoEmail(): void
    {
        $department = new Department();
        $department->setEmailHhv([]);
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder()->setDepartment($department);

        $this->service->sendConfirmation1($payment_order);

        //If no emails are present the token must be set and the confirm timestamp is set
        self::assertNotEmpty($payment_order->getConfirm1Token());
        self::assertNotNull($payment_order->getConfirm1Timestamp());

        //Ensure that no email is sent
        self::assertEmailCount(0);
    }

    public function testSendConfirmation2SendEmail(): void
    {
        $department = new Department();
        $department->setEmailTreasurer(['test@invalid.com', 'test2@invalid.com']);
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder()->setDepartment($department);

        $this->service->sendConfirmation2($payment_order);

        //It is important that a token was set and no timestamp
        self::assertNotEmpty($payment_order->getConfirm2Token());
        self::assertNull($payment_order->getConfirm2Timestamp());

        //Ensure that an email was sent
        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);

        //Email addresses are sent as BCC, and all emails in array must be present
        self::assertEmailAddressContains($email, 'bcc', 'test@invalid.com');
        self::assertEmailAddressContains($email, 'bcc', 'test2@invalid.com');

        //The from email is the one configured in .env
        self::assertEmailAddressContains($email, 'from', 'from@invalid.com');
        //Reply to is FSB email
        self::assertEmailAddressContains($email, 'reply-to', 'fsb@invalid.com');
    }

    public function testSendConfirmation2NoEmail(): void
    {
        $department = new Department();
        $department->setEmailHhv([]);
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder()->setDepartment($department);

        $this->service->sendConfirmation2($payment_order);

        //If no emails are present the token must be set and the confirm timestamp is set
        self::assertNotEmpty($payment_order->getConfirm2Token());
        self::assertNotNull($payment_order->getConfirm2Timestamp());

        //Ensure that no email is sent
        self::assertEmailCount(0);
    }

    public function testResendConfirmationsAlreadyConfirmed(): void
    {
        $department = new Department();
        $department
            ->setEmailTreasurer(['test@invalid.com', 'test2@invalid.com'])
            ->setEmailHhv(['test@invalid.com', 'test2@invalid.com']);
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder()->setDepartment($department);

        //Confirm payment order and set tokens
        $payment_order->setConfirm1Timestamp(new DateTime())
            ->setConfirm2Timestamp(new DateTime());
        $payment_order->setConfirm1Token('test')
            ->setConfirm2Token('test');

        $this->service->resendConfirmations($payment_order);

        //Ensure that no emails was sent
        self::assertEmailCount(0);
        //Ensure that tokens did not change
        self::assertSame('test', $payment_order->getConfirm1Token());
        self::assertSame('test', $payment_order->getConfirm2Token());
    }

    public function testResendConfirmationsSend2Emails(): void
    {
        $department = new Department();
        $department
            ->setEmailTreasurer(['test@invalid.com', 'test2@invalid.com'])
            ->setEmailHhv(['test@invalid.com', 'test2@invalid.com']);
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder()->setDepartment($department);

        $this->service->resendConfirmations($payment_order);

        //Ensure that 2 emails was sent
        self::assertEmailCount(2);
    }

    public function testResendConfirmationsSend1Email(): void
    {
        $department = new Department();
        $department
            ->setEmailTreasurer(['test@invalid.com', 'test2@invalid.com'])
            ->setEmailHhv(['test@invalid.com', 'test2@invalid.com']);
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder()->setDepartment($department);

        //Confirm payment order and set tokens
        $payment_order->setConfirm1Timestamp(new DateTime());
        $payment_order->setConfirm1Token('test')
            ->setConfirm2Token('test');

        $this->service->resendConfirmations($payment_order);

        //Ensure that no emails was sent
        self::assertEmailCount(1);
        //Ensure that tokens did not change
        self::assertSame('test', $payment_order->getConfirm1Token());
        //Token must change as a new one is generated
        self::assertNotSame('test', $payment_order->getConfirm2Token());
    }
}
