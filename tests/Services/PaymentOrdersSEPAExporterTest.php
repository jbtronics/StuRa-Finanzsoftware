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

use App\Entity\BankAccount;
use App\Entity\Department;
use App\Entity\PaymentOrder;
use App\Exception\SEPAExportAutoModeNotPossible;
use App\Services\PaymentOrderMailLinkGenerator;
use App\Services\PaymentOrdersSEPAExporter;
use App\Tests\PaymentOrderTestingHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Xml;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentOrdersSEPAExporterTest extends WebTestCase
{
    /**
     * @var PaymentOrdersSEPAExporter
     */
    protected $service;

    /**
     * @var string The folder where the reference data files are living
     */
    protected $data_dir;

    protected function setUp(): void
    {
        self::bootKernel();

        $em = self::$container->get(EntityManagerInterface::class);

        //Create a exporter with a fake FSRKom bank account, so we dont need to rely on database
        $this->service = new class(1, $em) extends PaymentOrdersSEPAExporter
        {
            public function getFSRKomBankAccount(): BankAccount
            {
                $bank_account = new BankAccount();

                $bank_account->setName('FSR Kom')
                ->setIban("DE84 6605 0101 0000 1299 95")
                ->setBic('KARSDE66XXX');

                return $bank_account;
            }
        };


        $this->data_dir = realpath(__DIR__ . '/../data/sepa-xml');
    }

    public function testMessageAndPaymentID(): void
    {
        [$payment_order,] = $this->getTestPaymentOrders();

        $options = ['iban' => 'DE97 6605 0101 0000 1234 56', 'bic' => 'KARSDE66XXX', 'name' => 'Max Mustermann'];

        $xml_array = $this->service->export([$payment_order], $options);
        $xml = $xml_array['Max Mustermann'];

        $dom = XML::load($xml);

        //Extract message ID from DOM and assert its contents
        /** @var \DOMElement $msg_id */
        $msg_id = $dom->getElementsByTagName('MsgId')[0];
        static::assertRegExp('/StuRa Export \w{13}/', $msg_id->nodeValue);

        //Extract payment ID from DOM and assert its contents
        $msg_id = $dom->getElementsByTagName('PmtInfId')[0];
        static::assertRegExp('/Payment \w{13}/', $msg_id->nodeValue);
    }

    public function testExportManualSinglePayment(): void
    {
        [$payment_order,] = $this->getTestPaymentOrders();

        $options = [
            'iban' => 'DE97 6605 0101 0000 1234 56',
            'bic' => 'KARSDE66XXX',
            'name' => 'Max Mustermann',
            'mode' => 'manual'
        ];

        $xml_array = $this->service->export([$payment_order], $options);
        //Exactly 1 XML file should be generated
        static::assertCount(1, $xml_array);
        $xml = $xml_array['Max Mustermann'];

        $this->assertSEPAXMLSchema($xml);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_manual_single_payment.xml', $xml);
    }

    public function testExportManualMultiplePayments(): void
    {
        [$payment_order1, $payment_order2, $payment_order3] = $this->getTestPaymentOrders();

        $options = [
            'iban' => 'DE97 6605 0101 0000 1234 56',
            'bic' => 'KARSDE66XXX',
            'name' => 'Max Mustermann',
            'mode' => 'manual'
        ];

        $payment_orders = [$payment_order1, $payment_order2, $payment_order3];

        //It must also work if a payment order has a department without bank account
        $payment_order1->getDepartment()->setBankAccount(null);

        $xml_array = $this->service->export($payment_orders, $options);
        //Exactly 1 XML file should be generated
        static::assertCount(1, $xml_array);
        $xml = $xml_array['Max Mustermann'];

        $this->assertSEPAXMLSchema($xml);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_manual_multiple_payments.xml', $xml);
    }

    public function testAutoModeNotPossibleException(): void
    {
        //Generate multiple payment orders
        [$payment_order,] = $this->getTestPaymentOrders();

        $department = new Department();
        $department->setName("Test Department");
        //No default bank account is defined
        $department->setBankAccount(null);
        $payment_order->setDepartment($department);

        $options = [
            'iban' => null,
            'bic' => null,
            'name' => 'Test',
            'mode' => 'auto'
        ];

        //The export attempt must throw an exception
        $this->expectException(SEPAExportAutoModeNotPossible::class);
        //This line must throw
        $this->service->export([$payment_order], $options);
    }

    public function testAutoSingleMode(): void
    {
        $payment_orders = $this->getTestPaymentOrders();

        $options = [
            'iban' => null,
            'bic' => null,
            'name' => 'Test',
            'mode' => 'auto_single'
        ];

        $xml_array = $this->service->export($payment_orders, $options);

        //Array must contain 3 entries / XML files (one for each payment order)
        static::assertCount(3, $xml_array);

        $this->assertSEPAXMLSchema($xml_array['ZA0001']);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_auto_single_ZA0001.xml', $xml_array['ZA0001']);

        $this->assertSEPAXMLSchema($xml_array['ZA0002']);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_auto_single_ZA0002.xml', $xml_array['ZA0002']);

        $this->assertSEPAXMLSchema($xml_array['ZA0003']);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_auto_single_ZA0003.xml', $xml_array['ZA0003']);
    }

    public function testAutoSingleModeFSRKom(): void
    {
        $payment_orders = $this->getTestPaymentOrders();

        //Same as testAutoSingleMode but one payment order is from the FSR Kom
        $payment_orders[0]->setFsrKomResolution(true);

        $options = [
            'iban' => null,
            'bic' => null,
            'name' => 'Test',
            'mode' => 'auto_single'
        ];

        $xml_array = $this->service->export($payment_orders, $options);

        //Array must contain 3 entries / XML files (one for each payment order)
        static::assertCount(3, $xml_array);

        $this->assertSEPAXMLSchema($xml_array['ZA0001']);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_auto_single_ZA0001_fsrkom.xml', $xml_array['ZA0001']);

        $this->assertSEPAXMLSchema($xml_array['ZA0002']);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_auto_single_ZA0002.xml', $xml_array['ZA0002']);

        $this->assertSEPAXMLSchema($xml_array['ZA0003']);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_auto_single_ZA0003.xml', $xml_array['ZA0003']);
    }

    public function testAutoMode(): void
    {
        $payment_orders = $this->getTestPaymentOrders();

        $options = [
            'iban' => null,
            'bic' => null,
            'name' => 'Test',
            'mode' => 'auto'
        ];

        $xml_array = $this->service->export($payment_orders, $options);

        //Array must contain 2 entries / XML files (one for each bank account)
        static::assertCount(2, $xml_array);

        $this->assertSEPAXMLSchema($xml_array['Max Mustermann']);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_auto_max_mustermann.xml', $xml_array['Max Mustermann']);

        $this->assertSEPAXMLSchema($xml_array['Bank Account 2']);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_auto_bank_account_2.xml', $xml_array['Bank Account 2']);
    }

    public function testExportSinglePaymentOrderManual(): void
    {
        [$payment_order,] = $this->getTestPaymentOrders();

        $xml = $this->service->exportSinglePaymentOrder($payment_order,
                                                        'Max Mustermann',
                                                        'DE97 6605 0101 0000 1234 56',
                                                        'KARSDE66XXX'
        );

        //We use the same input data as in testManualSinglePayment() so it must produce the same data
        $this->assertSEPAXMLSchema($xml);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_manual_single_payment.xml', $xml);
    }

    public function testExportSinglePaymentOrderAuto(): void
    {
        [$payment_order,] = $this->getTestPaymentOrders();
        $xml = $this->service->exportSinglePaymentOrder($payment_order);

        //We use the same input data as in testAutoSingleMode() so it must produce the same data
        $this->assertSEPAXMLSchema($xml);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_auto_single_ZA0001.xml', $xml);
    }

    public function testExportSinglePaymentOrderAutoFSRKom(): void
    {
        [$payment_order,] = $this->getTestPaymentOrders();

        $payment_order->setFsrKomResolution(true);

        $xml = $this->service->exportSinglePaymentOrder($payment_order);

        //We use the same input data as in testAutoSingleMode() so it must produce the same data
        $this->assertSEPAXMLSchema($xml);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/export_auto_single_ZA0001_fsrkom.xml', $xml);
    }

    /**
     * Returns an array of 3 PaymentOrders that can be used for testing.
     * They have associated departments and bank accounts.
     * @return PaymentOrder[]
     */
    private function getTestPaymentOrders(): array
    {
        [$department1, $department2, $department3] = $this->getTestDepartments();

        //Generate multiple payment orders
        $payment_order1 = PaymentOrderTestingHelper::getDummyPaymentOrder(1);
        $payment_order1->setAmount(12340);
        $payment_order1->setDepartment($department1);
        $payment_order1->getBankInfo()
            ->setAccountOwner('John Doe')
            ->setIban('DE09 8200 0000 0083 0015 03')
            ->setBic('MARKDEF1820')
            ->setReference('Test payment #1');

        $payment_order2 = PaymentOrderTestingHelper::getDummyPaymentOrder(2);
        $payment_order2->setAmount(10); //10 cents
        $payment_order2->setDepartment($department2);
        $payment_order2->getBankInfo()
            ->setAccountOwner('Ümläute Teßt') //Test special chars
            //Without spaces
            ->setIban('DE09500105175649523697')
            //Empty BIC
            ->setBic('')
            //Special chars
            ->setReference('Test payment #2 My party@$Place%!&§"()=+*-:. /\?');

        //Generate multiple payment orders
        $payment_order3 = PaymentOrderTestingHelper::getDummyPaymentOrder(3);
        $payment_order3->setAmount(123); //12.30€
        $payment_order3->setDepartment($department3);
        $payment_order3->getBankInfo()
            ->setAccountOwner('Max Musterman')
            //Test foreign IBAN
            ->setIban('AT77 3400 0246 4353 5933')
            ->setBic('')
            ->setReference('Test payment #3');

        return [$payment_order1, $payment_order2, $payment_order3];
    }

    /**
     * Returns an array of 3 Departments that have an bankAccount associated.
     * @return Department[]
     */
    private function getTestDepartments(): array
    {
        [$bank_account1, $bank_account2] = $this->getTestBankAccounts();

        $department1 = new Department();
        $department1->setName('Department1')
            ->setBankAccount($bank_account1);

        $department2 = new Department();
        $department2->setName('Department2')
            ->setBankAccount($bank_account2);

        $department3 = new Department();
        $department3->setName('Department3')
            ->setBankAccount($bank_account1);

        return [$department1, $department2, $department3];
    }

    /**
     * Returns an array of 3 BankAccounts that can be used for testing.
     * @return BankAccount[]
     */
    private function getTestBankAccounts(): array
    {
        //Bank accounts must have an ID or grouping will not work...
        $bank_account1 = new class extends BankAccount {
            public function getId(): ?int
            {
                return 1;
            }
        };
        $bank_account1->setName('Bank Account 1')
            ->setIban('DE97 6605 0101 0000 1234 56')
            ->setBic('KARSDE66XXX')
            ->setAccountName('Max Mustermann');

        $bank_account2 = new class extends BankAccount {
            public function getId(): ?int
            {
                return 2;
            }
        };
        $bank_account2->setName('Bank Account 2')
            ->setIban('DE84 6605 0101 0000 1299 95')
            ->setBic('KARSDE66XXX')
            //Empty account name
            ->setAccountName('');

        return [$bank_account1, $bank_account2];
    }

    protected function assertSEPAXMLSchema(string $actualXml): void
    {
        $actual = Xml::load($actualXml);
        static::assertTrue(
            $actual->schemaValidate($this->data_dir . '/pain.001.001.03.xsd'),
            "Generated output does not match pain.001.001.03 schema!"
        );
    }

    protected static function assertSEPAXMLStringEqualsXMLFile(string $expectedFile, string $actualXml, string $message = ''): void
    {
        $expected = Xml::loadFile($expectedFile);
        $actual   = Xml::load($actualXml);

        self::normalizeSEPAXML($expected);
        self::normalizeSEPAXML($actual);

        static::assertEquals($expected, $actual, $message);
    }

    /**
     * Normalizes the given SEPA-XML by changing MsgID, PmtInfId and ReqdExctnDt to a common value.
     * This way SEPA XML files can be compared.
     * @param  \DOMDocument  $sepaXML
     */
    protected static function normalizeSEPAXML(\DOMDocument $sepaXML): void
    {
        //Normalize MessageID
        $msg_ids = $sepaXML->getElementsByTagName("MsgId");
        foreach ($msg_ids as $msg_id) {
            /** @var \DOMNode $msg_id */
            $msg_id->nodeValue = "Message ID";
        }

        //Normalize Creation date
        $dates = $sepaXML->getElementsByTagName("CreDtTm");
        foreach ($dates as $date) {
            /** @var \DOMNode $date */
            $date->nodeValue = "2020-12-29T14:15:09Z";
        }

        //Normalize execution date
        $dates = $sepaXML->getElementsByTagName("ReqdExctnDt");
        foreach ($dates as $date) {
            /** @var \DOMNode $date */
            $date->nodeValue = "2020-12-29";
        }

        //Payment Info ID
        $pmts = $sepaXML->getElementsByTagName('PmtInfId');
        foreach ($pmts as $pmt) {
            /** @var \DOMNode $pmt */
            $pmt->nodeValue = 'Payment';
        }
    }
}
