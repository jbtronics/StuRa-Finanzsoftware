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
use App\Entity\PaymentOrder;
use App\Services\EmailConfirmation\ConfirmationEmailSender;
use Doctrine\ORM\EntityManagerInterface;
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

    protected ?Confirmer $confirmer1 = null;
    protected ?Confirmer $confirmer2 = null;
    protected ?Department $department = null;

    protected ?PaymentOrder $paymentOrder = null;

    protected ?EntityManagerInterface $em = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(ConfirmationEmailSender::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->department = new Department();
        $this->department->setName('Test Department');
        $this->em->persist($this->department);

        $this->confirmer1 = new Confirmer();
        $this->confirmer1->setEmail('test@invalid.com')->setName('Test 1');
        $this->em->persist($this->confirmer1);

        $this->confirmer2 = new Confirmer();
        $this->confirmer2->setEmail('test2@invalid.com')->setName('Test 2');
        $this->em->persist($this->confirmer2);

        $this->department->getConfirmers()->add($this->confirmer1);
        $this->department->getConfirmers()->add($this->confirmer2);

        $this->payment_order = (new PaymentOrder())->setDepartment($this->department);
        $this->payment_order->setAmount(1234);
        $this->em->persist($this->payment_order);
    }

    public function testGenerateAndSendConfirmationEmail(): void
    {
        $this->service->generateAndSendConfirmationEmail($this->payment_order, $this->confirmer1);

        //It is important that a token was set and no timestamp
        self::assertNotEmpty($this->payment_order->getConfirmationTokens()[0]->getHashedToken());
        self::assertFalse($this->payment_order->getConfirmation1()->isConfirmed());

        //Ensure that an email was sent
        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);

        //Email addresses are sent as BCC, and all emails in array must be present
        self::assertEmailAddressContains($email, 'to', 'test@invalid.com');

        //The from email is the one configured in .env
        self::assertEmailAddressContains($email, 'from', 'from@invalid.com');
        //Reply to is FSB email
        self::assertEmailAddressContains($email, 'reply-to', 'fsb@invalid.com');
    }

    public function testSendAllConfirmationEmails(): void
    {
        $this->service->sendAllConfirmationEmails($this->payment_order);

        //Ensure that 2 emails was sent
        self::assertEmailCount(2);
    }
}
