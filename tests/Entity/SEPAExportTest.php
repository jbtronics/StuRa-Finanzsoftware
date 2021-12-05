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

namespace App\Tests\Entity;

use App\Entity\SEPAExport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class SEPAExportTest extends TestCase
{

    public function testSetIsBooked(): void
    {
        $export = new SEPAExport();

        //By default booking date should be null
        self::assertNull($export->getBookingDate());

        //If we set booking state to true, the value must not be null
        $export->setIsBooked(true);
        self::assertNotNull($export->getBookingDate());

        //We can reset it by setting it to false
        $export->setIsBooked(false);
        self::assertNull($export->getBookingDate());

        //We can call function without argument too
        $export->setIsBooked();
        self::assertNotNull($export->getBookingDate());
    }

    public function testIsOpen(): void
    {
        $export = new SEPAExport();
        //By default export should be open
        self::assertTrue($export->isOpen());

        //If we book it, it should not be open
        $export->setIsBooked(true);
        self::assertFalse($export->isOpen());
    }

    public function testUpdateOnSetFile(): void
    {
        $export = new SEPAExport();

        //Values should be empty for a new object
        self::assertNull($export->getTotalSum());
        self::assertNull($export->getNumberOfPayments());
        self::assertNull($export->getInitiatorBic());
        self::assertNull($export->getInitiatorIban());
        self::assertNull($export->getSepaMessageId());
        self::assertNull($export->getXmlFile());

        $file = new File(__DIR__.'/../data/sepa-xml/export_manual_multiple_payments.xml');
        $export->setXmlFile($file);

        //Values should now be filled
        self::assertSame(12473, $export->getTotalSum());
        self::assertSame(3, $export->getNumberOfPayments());
        self::assertSame('KARSDE66XXX', $export->getInitiatorBic());
        self::assertSame('DE97660501010000123456', $export->getInitiatorIban());
        self::assertSame('StuRa Export 5fed0a4111595', $export->getSepaMessageId());
        self::assertSame($file, $export->getXmlFile());

        //If we change file, the values should be updated
        $file2 = new File(__DIR__.'/../data/sepa-xml/export_manual_single_payment.xml');
        $export->setXmlFile($file2);

        //Values should now be filled
        self::assertSame(12340, $export->getTotalSum());
        self::assertSame(1, $export->getNumberOfPayments());
        self::assertSame('KARSDE66XXX', $export->getInitiatorBic());
        self::assertSame('DE97660501010000123456', $export->getInitiatorIban());
        self::assertSame('StuRa Export 5feb3462a3fbf', $export->getSepaMessageId());
        self::assertSame($file2, $export->getXmlFile());

    }
}
