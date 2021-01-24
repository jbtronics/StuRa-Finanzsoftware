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

namespace App\Tests\Entity\Embeddable;

use App\Entity\Embeddable\FundingID;
use PHPUnit\Framework\TestCase;

class FundingIDTest extends TestCase
{

    public function testGetPrefix(): void
    {
        $external = new FundingID(true, 1, "19/20");
        self::assertSame("FA", $external->getPrefix());

        $internal = new FundingID(false, 1, "19/20");
        self::assertSame('M', $internal->getPrefix());
    }

    /**
     * @dataProvider formatDataProvider
     */
    public function testFormat(string $expected, bool $external, int $number, string $year_part): void
    {
        $fundingID = new FundingID($external, $number, $year_part);
        self::assertSame($expected, $fundingID->format());
        self::assertSame($expected, (string) $fundingID);
    }

    public function testEquals(): void
    {
        $f1 = new FundingID(true, 123, '19/20');
        $f1b = new FundingID(true, 123, '19/20');
        $f2 = new FundingID(true, 124, '19/20');
        $f3 = new FundingID(false, 123, '19/20');

        self::assertTrue($f1->equals($f1b));
        //Equality must be symmetric
        self::assertTrue($f1b->equals($f1));

        self::assertFalse($f1->equals($f2));
        self::assertFalse($f2->equals($f1));

        self::assertFalse($f1->equals($f3));
    }

    /**
     * @dataProvider fromStringDataProvider
     */
    public function testFromString(?string $expected, string $input, bool $caseSensitive = false): void
    {
        if ($expected === null) {
            $this->expectException(\InvalidArgumentException::class);
        }

        self::assertSame($expected, (string) FundingID::fromString($input, $caseSensitive));
    }

    /**
     * @dataProvider equalsStringDataProvider
     */
    public function testEqualsString(bool $expected, string $input1, string $input2): void
    {
        $fundingID = FundingID::fromString($input1);
        self::assertSame($expected, $fundingID->equalsString($input2));
    }

    public function equalsStringDataProvider(): array
    {
        return [
            [true, 'M-123-20/21', 'M-123-20/21'],
            [true, 'M-123-20/21', 'M-0123-20/21'],
            [true, 'FA-001-20/21', 'fa-1-20/21'],

            [false, 'M-123-20/21', 'M-1234-20/21'],
            [false, 'M-123-20/21', 'FA-123-20/21'],
        ];
    }

    public function fromStringDataProvider(): array
    {
        //First argument null means the given funding ID is invalid
        return [
            ['M-123-20/21', 'M-123-20/21'],
            ['M-123-20/21', 'm-123-20/21', false],
            ['FA-123-20/21', 'fA-123-20/21', false],
            ['M-001-20/21', 'M-1-20/21'],
            ['M-010-20/21', 'M-0010-20/21'],

            //Invalid funding ID numbers
            [null, 'N-123-20/21'],
            [null, 'blaBla-123-20/21'],
            [null, 'm-123-20/21', true],
            [null, 'fa-123-20/21', true],
            [null, 'M-1a3-20/21']
        ];
    }

    public function formatDataProvider(): array
    {
        return [
            ['M-001-20/21', false, 1, '20/21'],
            ['M-020-20/21', false, 20, '20/21'],
            ['M-123-22/23', false, 123, '22/23'],
            ['M-12345-22/23', false, 12345, '22/23'],

            ['FA-001-20/21', true, 1, '20/21'],
            ['FA-123-20/21', true, 123, '20/21'],
        ];
    }
}
