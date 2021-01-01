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

namespace App\Tests\Services;

use App\Entity\Department;
use App\Services\PaymentReferenceGenerator;
use App\Tests\PaymentOrderTestingHelper;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentReferenceGeneratorTest extends WebTestCase
{
    /**
     * @var PaymentReferenceGenerator
     */
    protected $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::$container->get(PaymentReferenceGenerator::class);
    }

    public function testSetPaymentReference()
    {
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder(1);
        $department = new Department();
        $department->setName('Test');
        $payment_order->setProjectName('Test')
            ->setDepartment($department)
            ->setFundingId('FA-123-2020');

        $this->service->setPaymentReference($payment_order);
        static::assertSame('Test Test FA-123-2020 ZA0001', $payment_order->getBankInfo()->getReference());
    }

    /**
     * @dataProvider generatePaymentReferenceDataProvider
     */
    public function testGeneratePaymentReference(string $expected, string $project_name, string $department_name, string $funding_id, int $id): void
    {
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder($id);
        $department = new Department();
        $department->setName($department_name);
        $payment_order->setProjectName($project_name)
            ->setDepartment($department)
            ->setFundingId($funding_id);

        $tmp = $this->service->generatePaymentReference($payment_order);
        static::assertSame($expected, $tmp);
    }

    public function generatePaymentReferenceDataProvider(): ?Generator
    {
        //Simple tests
        yield ['Test Physik ZA0001', 'Test', 'Physik', '', 1];
        yield ['Test Physik FA-123-2020 ZA0001', 'Test', 'Physik', 'FA-123-2020', 1];
        //Test project name padding
        yield ['veryveryveryveryverylonglonglonglonglonglonglonglonglonglong1234567890 Physik FA-123-2020 ZA0001', 'veryveryveryveryverylonglonglonglonglonglonglonglonglonglong1234567890123456', 'Physik', 'FA-123-2020', 1];
        //Test project FSR name padding
        yield ['Test PhysikLongLongLongLongLongLongLongLongLongLon FA-123-2020 ZA0001', 'Test', 'PhysikLongLongLongLongLongLongLongLongLongLong', 'FA-123-2020', 1];
        //Even long funding IDs should not be padded
        yield ['Test Physik FA-999-2020 ZA0001', 'Test', 'Physik', 'FA-999-2020', 1];
        yield ['Test Physik M-9999-2020 ZA0001', 'Test', 'Physik', 'M-9999-2020', 1];
        //Test everything very long
        yield ['veryveryveryveryverylonglonglonglonglonglonglonglonglonglong1234567890 PhysikLongLongLongLongLongLongLongLongLongLon FA-999-2020 ZA123456789',
            'veryveryveryveryverylonglonglonglonglonglonglonglonglonglong1234567890123456',
            'PhysikLongLongLongLongLongLongLongLongLongLong',
            'FA-999-2020',
            123456789,
        ];
    }
}
