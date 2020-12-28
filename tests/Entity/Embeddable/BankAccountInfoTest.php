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

use App\Entity\Embeddable\BankAccountInfo;
use PHPUnit\Framework\TestCase;

class BankAccountInfoTest extends TestCase
{

    public function testGetAddress()
    {
        $payout_info = new BankAccountInfo();
        $payout_info->setStreet('Test street 1');
        $payout_info->setZipCode('1234');
        $payout_info->setCity('City');

        static::assertSame('Test street 1, 1234 City', $payout_info->getAddress());
    }
}
