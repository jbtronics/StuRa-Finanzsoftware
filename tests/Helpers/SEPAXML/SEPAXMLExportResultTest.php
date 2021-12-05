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

namespace App\Tests\Helpers\SEPAXML;

use App\Entity\SEPAExport;
use App\Helpers\SEPAXML\SEPAXMLExportResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;

class SEPAXMLExportResultTest extends TestCase
{
    private $xml_file1;
    private $sepa_export1;
    private $xml_file2;
    private $sepa_export2;
    private $xml_file3;
    private $sepa_export3;

    public function setUp(): void
    {
        $this->xml_file1 = new File(__DIR__.'/../../data/sepa-xml/export_manual_multiple_payments.xml');
        $this->xml_file2 = new File(__DIR__.'/../../data/sepa-xml/export_manual_single_payment.xml');
        $this->xml_file3 = new File(__DIR__.'/../../data/sepa-xml/export_auto_single_ZA0001_fsrkom.xml');

        $this->sepa_export1 = (new SEPAExport())->setXmlFile($this->xml_file1);
        $this->sepa_export2 = (new SEPAExport())->setXmlFile($this->xml_file2);
        $this->sepa_export3 = (new SEPAExport())->setXmlFile($this->xml_file3);

    }

    public function testConstructEmptySEPAExports(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $result = new SEPAXMLExportResult([], []);
    }

    public function testConstructInvalidTypes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $result = new SEPAXMLExportResult([1, 2], []);
    }

    public function testConstructNoXMLFiles(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $result = new SEPAXMLExportResult([
            new SEPAExport()
        ], []);
    }

    public function testGetXMLExports(): void
    {
        $result = new SEPAXMLExportResult([$this->sepa_export1, $this->sepa_export2, $this->sepa_export3], []);

        self::assertSame([$this->sepa_export1, $this->sepa_export2, $this->sepa_export3], $result->getSEPAExports());
    }

    public function testGetCount(): void
    {
        $result1 = new SEPAXMLExportResult([$this->sepa_export1], []);
        self::assertSame(1, $result1->count());
        self::assertCount(1, $result1);

        $result2 = new SEPAXMLExportResult([$this->sepa_export1, $this->sepa_export2, $this->sepa_export3], []);
        self::assertSame(3, $result2->count());
        self::assertCount(3, $result2);
    }

    public function testGetXMLFiles(): void
    {
        $result = new SEPAXMLExportResult([$this->sepa_export1, $this->sepa_export2, $this->sepa_export3], []);
        self::assertEqualsCanonicalizing([$this->xml_file1, $this->xml_file2, $this->xml_file3], $result->getXMLFiles());
    }

    public function testGetXMLString(): void
    {
        $result = new SEPAXMLExportResult([$this->sepa_export1, $this->sepa_export2, $this->sepa_export3], []);
        self::assertCount(3, $result->getXMLString());
        self::assertContainsOnly('string', $result->getXMLString());
    }

    public function testGetDownloadResponse(): void
    {
        $result = new SEPAXMLExportResult([$this->sepa_export1, $this->sepa_export2, $this->sepa_export3], []);
        $response = $result->getDownloadResponse('filename.zip');
        self::assertTrue($response->isOk());
    }
}
