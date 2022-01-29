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

namespace App\Tests\Services\SEPAExport;
use App\Entity\BankAccount;
use App\Services\SEPAExport\GroupHeaderHelper;
use App\Services\SEPAExport\PaymentOrderSEPAExporter;
use App\Services\SEPAExport\SEPAExportGroupAndSplitHelper;
use DOMElement;
use PHPUnit\Util\Xml\Loader as XmlLoader;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentOrderSEPAExporterTest extends WebTestCase
{
    use PaymentOrderSEPAExporterTestHelperTrait;

    /**
     * @var PaymentOrderSEPAExporter
     */
    protected $service;

    /**
     * @var string The folder where the reference data files are living
     */
    protected $data_dir;
    /** @var string */
    protected $app_version;

    private $fsr_kom_bank_account;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->app_version = self::getContainer()->getParameter('app.version');

        $this->fsr_kom_bank_account = new BankAccount();
        $this->fsr_kom_bank_account->setName('FSR Kom')
            ->setIban('DE84 6605 0101 0000 1299 95')
            ->setBic('KARSDE66XXX');


        $splitHelper = new SEPAExportGroupAndSplitHelper(10, 1000000, null, null, $this->fsr_kom_bank_account);

        $this->service = new PaymentOrderSEPAExporter(
            $this->getContainer()->get(GroupHeaderHelper::class),
            $splitHelper
        );

        $this->data_dir = realpath(__DIR__.'/../../data/sepa-xml');
    }

    public function testMessageAndPaymentID(): void
    {
        [$payment_order,] = $this->getTestPaymentOrders();

        $options = [
            'iban' => 'DE97 6605 0101 0000 1234 56',
            'bic' => 'KARSDE66XXX',
            'name' => 'Max Mustermann',
        ];

        $sepa_export = $this->service->exportUsingGivenIBAN([$payment_order], $options['iban'], $options['bic'], $options['name']);
        $xml = $sepa_export->getXMLContent();

        $dom = (new XmlLoader())->load($xml);

        //Extract message ID from DOM and assert its contents
        /** @var DOMElement $msg_id */
        $msg_id = $dom->getElementsByTagName('MsgId')[0];
        //MsgId must start with BIC, then stura, and a random hex string
        static::assertMatchesRegularExpression('/KARSDE66XXXstura[0-9a-f]{11}/', $msg_id->nodeValue);

        //Extract payment ID from DOM and assert its contents
        $msg_id = $dom->getElementsByTagName('PmtInfId')[0];
        //32 chars hex string
        static::assertMatchesRegularExpression('/[0-9a-f]{32}/', $msg_id->nodeValue);

        //Extract initiator name from DOM and assert its contents
        $msg_id = $dom->getElementsByTagName('InitgPty')[0]->firstChild;
        //32 chars hex string
        static::assertSame('NOT LOGGED IN via StuRa-Zahlungssystem v' . $this->app_version, $msg_id->nodeValue);
    }

    public function testExportAutoSingle(): void
    {
        $payment_orders = $this->getTestPaymentOrders();

        $result = $this->service->exportAutoSingle($payment_orders);

        //Array must contain 3 entries / XML files (one for each payment order)
        static::assertCount(3, $result);

        $xml_array =  $result->getXMLString();
        $filenames = array_keys($xml_array);
        $xml = array_values($xml_array);

        $this->assertSEPAXMLSchema($xml[0]);
        $this->assertStringStartsWith('ZA0001_', $filenames[0]);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir.'/export_auto_single_ZA0001.xml', $xml[0]);

        $this->assertSEPAXMLSchema($xml[1]);
        $this->assertStringStartsWith('ZA0002_', $filenames[1]);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir.'/export_auto_single_ZA0002.xml', $xml[1]);

        $this->assertSEPAXMLSchema($xml[2]);
        $this->assertStringStartsWith('ZA0003_', $filenames[2]);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir.'/export_auto_single_ZA0003.xml', $xml[2]);

    }

    public function testExportAutoSingleFSRKom(): void
    {
        $payment_orders = $this->getTestPaymentOrders();
        $payment_orders[0]->setFsrKomResolution(true);

        $result = $this->service->exportAutoSingle($payment_orders);

        //Array must contain 3 entries / XML files (one for each payment order)
        static::assertCount(3, $result);

        $xml_array =  $result->getXMLString();
        $filenames = array_keys($xml_array);
        $xml = array_values($xml_array);

        $this->assertSEPAXMLSchema($xml[0]);
        $this->assertStringStartsWith('ZA0001_', $filenames[0]);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir.'/export_auto_single_ZA0001_fsrkom.xml', $xml[0]);

        $this->assertSEPAXMLSchema($xml[1]);
        $this->assertStringStartsWith('ZA0002_', $filenames[1]);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir.'/export_auto_single_ZA0002.xml', $xml[1]);

        $this->assertSEPAXMLSchema($xml[2]);
        $this->assertStringStartsWith('ZA0003_', $filenames[2]);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir.'/export_auto_single_ZA0003.xml', $xml[2]);

    }

    public function testExportAuto(): void
    {
        $payment_orders = $this->getTestPaymentOrders();

        $result = $this->service->exportAuto($payment_orders);

        //Array must contain 3 entries / XML files (one for each payment order)
        static::assertCount(2, $result);

        $xml_array =  $result->getXMLString();
        $filenames = array_keys($xml_array);
        $xml = array_values($xml_array);

        $this->assertSEPAXMLSchema($xml[0]);
        $this->assertStringStartsWith('Max Mustermann_', $filenames[0]);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir.'/export_auto_max_mustermann.xml', $xml[0]);

        $this->assertSEPAXMLSchema($xml[1]);
        $this->assertStringStartsWith('Bank Account 2_', $filenames[1]);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir.'/export_auto_bank_account_2.xml', $xml[1]);
    }


    public function testExportUsingGivenIBANSEPAExport(): void
    {
        [$payment_order,] = $this->getTestPaymentOrders();

        $options = [
            'iban' => 'DE97 6605 0101 0000 1234 56',
            'bic' => 'KARSDE66XXX',
            'name' => 'Max Mustermann',
            'mode' => 'manual',
        ];

        $sepa_export = $this->service->exportUsingGivenIBAN([$payment_order], $options['iban'], $options['bic'], $options['name']);

        //Check that the file exists
        static::assertFileExists($sepa_export->getXmlFile()->getPathname());

        //Assert that message id and others are filled
        static::assertNotEmpty($sepa_export->getSepaMessageId());
        static::assertSame(12340, $sepa_export->getTotalSum());
        static::assertSame(1, $sepa_export->getNumberOfPayments());

        //Assert that the export is not booked yet
        static::assertTrue($sepa_export->isOpen());

    }

    public function testExportUsingGivenIBANSinglePaymentOrder(): void
    {
        [$payment_order,] = $this->getTestPaymentOrders();

        $options = [
            'iban' => 'DE97 6605 0101 0000 1234 56',
            'bic' => 'KARSDE66XXX',
            'name' => 'Max Mustermann',
            'mode' => 'manual',
        ];

        $sepa_export = $this->service->exportUsingGivenIBAN([$payment_order], $options['iban'], $options['bic'], $options['name']);
        $xml = $sepa_export->getXMLContent();

        $this->assertSEPAXMLSchema($xml);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir.'/export_manual_single_payment.xml', $xml);

    }

    public function testExportUsingGivenIBANMultiplePaymentOrders(): void
    {
        [$payment_order1, $payment_order2, $payment_order3] = $this->getTestPaymentOrders();

        //It must also work if a payment order has a department without bank account
        $payment_order1->getDepartment()
            ->setBankAccount(null);

        $options = [
            'iban' => 'DE97 6605 0101 0000 1234 56',
            'bic' => 'KARSDE66XXX',
            'name' => 'Max Mustermann',
            'mode' => 'manual',
        ];

        $sepa_export = $this->service->exportUsingGivenIBAN(
            [$payment_order1, $payment_order2, $payment_order3],
            $options['iban'],
            $options['bic'],
            $options['name']
        );
        $xml = $sepa_export->getXMLContent();

        $this->assertSEPAXMLSchema($xml);
        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir.'/export_manual_multiple_payments.xml', $xml);

    }

    protected function assertSEPAXMLSchema(string $actualXml): void
    {
        $actual = (new XmlLoader())->load($actualXml);
        static::assertTrue(
            $actual->schemaValidate($this->data_dir.'/pain.001.001.03.xsd'),
            'Generated output does not match pain.001.001.03 schema!'
        );
    }

    protected static function assertSEPAXMLStringEqualsXMLFile(string $expectedFile, string $actualXml, string $message = ''): void
    {
        $expected = (new XmlLoader())->loadFile($expectedFile);
        $actual = (new XmlLoader())->load($actualXml);

        self::normalizeSEPAXML($expected);
        self::normalizeSEPAXML($actual);

        static::assertEquals($expected, $actual, $message);
    }


}
