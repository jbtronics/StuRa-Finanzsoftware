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

use App\Services\PaymentOrderMailLinkGenerator;
use App\Services\PaymentOrdersSEPAExporter;
use App\Tests\PaymentOrderTestingHelper;
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
        $this->service = self::$container->get(PaymentOrdersSEPAExporter::class);

        $this->data_dir = realpath(__DIR__ . '/../data/sepa-xml');
    }

    public function testExport1(): void
    {
        $payment_order = PaymentOrderTestingHelper::getDummyPaymentOrder();
        $payment_order->setAmount(12340);
        $payment_order->getBankInfo()->setAccountOwner('John Doe');
        $payment_order->getBankInfo()->setIban('DE09 8200 0000 0083 0015 03');
        $payment_order->getBankInfo()->setBic('MARKDEF1820');
        $payment_order->getBankInfo()->setReference('Payment reference');

        $options = ['iban' => 'DE97 6605 0101 0000 1234 56', 'bic' => 'KARSDE66XXX', 'name' => 'Max Mustermann'];

        $xml_array = $this->service->export([$payment_order], $options);
        //Exactly 1 XML file should be generated
        static::assertCount(1, $xml_array);
        $xml = $xml_array['Max Mustermann'];

        self::assertSEPAXMLStringEqualsXMLFile($this->data_dir . '/test1.xml', $xml);
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
