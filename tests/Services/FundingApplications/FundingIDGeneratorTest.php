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

namespace App\Tests\Services\FundingApplications;

use App\Services\FundingApplications\FundingIDGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FundingIDGeneratorTest extends WebTestCase
{

    /**
     * @var FundingIDGenerator
     */
    protected $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::$container->get(FundingIDGenerator::class);
    }

    /**
     * @dataProvider budgetYearPartDataProvider
     * @param  string  $expected
     * @param  string  $input_date
     */
    public function testGetBudgetYearPart(string $expected, string $input_date)
    {
        $datetime = \DateTime::createFromFormat('Y-m-d', $input_date);
        self::assertSame($expected, $this->service->getBudgetYearPart($datetime));
    }

    /**
     * @group DB
     */
    public function testGetNextAvailableFundingIDGeneratorDBEmpty()
    {
        $datetime1 = new \DateTime();
        $datetime1->setDate(2050, 6,1);

        $datetime2 = new \DateTime();
        $datetime2->setDate(2051, 6,1);

        $funding_id_m1 = $this->service->getNextAvailableFundingID(false, $datetime1);
        self::assertSame('M-001-50/51', (string) $funding_id_m1);

        //The next funding ID must have a higher number
        $funding_id_m2 = $this->service->getNextAvailableFundingID(false, $datetime1);
        self::assertSame('M-002-50/51', (string) $funding_id_m2);

        //But for other years and extern funding applications it must start from 1
        $funding_id_m3 = $this->service->getNextAvailableFundingID(false, $datetime2);
        self::assertSame('M-001-51/52', (string) $funding_id_m3);

        $funding_id_f1 = $this->service->getNextAvailableFundingID(true, $datetime1);
        self::assertSame('FA-001-50/51', (string) $funding_id_f1);

        //The next funding ID must have a higher number
        $funding_id_m4 = $this->service->getNextAvailableFundingID(false, $datetime1);
        self::assertSame('M-003-50/51', (string) $funding_id_m4);
    }

    public function budgetYearPartDataProvider(): array
    {
        return [
            ['20/21', '2021-01-18',],
            ['21/22', '2021-04-01',],
            ['21/22', '2021-05-23'],
            ['21/22', '2022-03-31'],
            ['49/50', '2050-01-05'],
            ['90/91', '2090-04-05'],
        ];
    }
}
