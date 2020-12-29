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
use App\Services\PaymentOrderMailLinkGenerator;
use App\Services\PaymentReferenceGenerator;
use App\Tests\PaymentOrderTestingHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentOrderMailLinkGeneratorTest extends WebTestCase
{
    /**
     * @var PaymentOrderMailLinkGenerator
     */
    protected $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::$container->get(PaymentOrderMailLinkGenerator::class);
    }

    public function testGetHHVMailLink(): void
    {
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder();
        $department = new Department();
        $department->setName('Physik');
        $payment_order->setDepartment($department)->setProjectName('Test project');

        $hhv_link = $this->service->getHHVMailLink($payment_order);

        self::assertStringStartsWith("mailto:hhv@invalid.com?subject=R%C3%BCckfrage%20Zahlungsauftrag%20-%20Physik%3A%20Test%20project%20%5BZA0001%5D&body=", $hhv_link);
    }

    public function testGenerateContactMailLinkWithContactMail(): void
    {
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder();
        $department = new Department();
        $department->setName('Physik');
        $payment_order->setDepartment($department)->setProjectName('Test project');
        $payment_order->setContactEmail("test@invalid.com");

        $contact_link = $this->service->generateContactMailLink($payment_order);
        self::assertSame("mailto:test@invalid.com?subject=R%C3%BCckfrage%20Zahlungsauftrag%20-%20Physik%3A%20Test%20project%20%5BZA0001%5D", $contact_link);
    }

    public function testGenerateContactMailLinkWithoutContactMail(): void
    {
        //Test the fallback to the department contact addresses

        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder();
        $department = new Department();
        $department->setContactEmails(['test1@invalid.com', 'test2@invalid.com']);
        $department->setName('Physik');
        $payment_order->setDepartment($department)->setProjectName('Test project');
        $payment_order->setContactEmail("");

        $contact_link = $this->service->generateContactMailLink($payment_order);
        self::assertSame("mailto:test1@invalid.com,test2@invalid.com?subject=R%C3%BCckfrage%20Zahlungsauftrag%20-%20Physik%3A%20Test%20project%20%5BZA0001%5D", $contact_link);

    }
}
