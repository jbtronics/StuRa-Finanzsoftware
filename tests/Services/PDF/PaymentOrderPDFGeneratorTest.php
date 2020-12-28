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

namespace App\Tests\Services\PDF;

use App\Entity\Department;
use App\Entity\PaymentOrder;
use App\Services\EmailConfirmation\ConfirmationEmailSender;
use App\Services\PDF\PaymentOrderPDFGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentOrderPDFGeneratorTest extends WebTestCase
{
    /**
     * @var PaymentOrderPDFGenerator
     */
    protected $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::$container->get(PaymentOrderPDFGenerator::class);
    }

    public function testGeneratePDF(): void
    {
        $payment_order = new PaymentOrder();
        $department = new Department();
        $department->setName('Test');
        $payment_order->setDepartment($department);

        $pdf = $this->service->generatePDF($payment_order);
        // Just a little test to ensure that something PDF-like is generated
        self::assertStringStartsWith('%PDF-', $pdf);
    }

    public function arrayToPaymentOrder(array $data): PaymentOrder
    {
        $payment_order = new class($data['id']) extends PaymentOrder {
            public function __construct(int $id)
            {
                $this->id2 = $id;
                parent::__construct();
            }

            public function getId(): ?int
            {
                return $this->id2;
            }
        };

        $payment_order->setFirstName($data['first_name'])
            ->setLastName($data['last_name'])
            ->setContactEmail($data['contact_email'])
            ->setAmount($data['amount'])
            ->setProjectName($data['project_name'])
            ->setFundingId($data['funding_id'] ?? '')
            ->setResolutionDate($data['resolution_date'] ?? null)
            ->setComment($data['comment'] ?? null)
            ->setDepartment($data['department'])
            ->getBankInfo()->setAccountOwner($data['account_owner'])
            ->setStreet($data['street'])
            ->setCity($data['city'])
            ->setZipCode($data['zip'])
            ->setIban($data['iban'])
            ->setBic($data['bic'])
            ->setReference($data['reference']);

        return $payment_order;
    }
}
