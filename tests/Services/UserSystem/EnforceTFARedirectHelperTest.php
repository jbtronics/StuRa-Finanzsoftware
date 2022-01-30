<?php
/*
 * Copyright (C)  2020-2022  Jan Böhmer
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

namespace App\Tests\Services\UserSystem;

use App\Services\SEPAExport\GroupHeaderHelper;
use App\Services\UserSystem\EnforceTFARedirectHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EnforceTFARedirectHelperTest extends WebTestCase
{

    /**
     * @var EnforceTFARedirectHelper
     */
    protected $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(EnforceTFARedirectHelper::class);
    }

    public function testCheckifRolesAreRisky()
    {
        $this->assertFalse($this->service->checkifRolesAreRisky(
            ['ROLE_ADMIN'],
            []
        ));

        $this->assertFalse($this->service->checkifRolesAreRisky(
            ['ROLE_ADMIN'],
            ['Test234']
        ));

        $this->assertFalse($this->service->checkifRolesAreRisky(
            ['ROLE_ADMIN', 'Test'],
            ['Test234']
        ));

        $this->assertTrue($this->service->checkifRolesAreRisky(
            ['ROLE_ADMIN', 'Test234'],
            ['ROLE_ADMIN', 'ROLE_TEST']
        ));

        $this->assertTrue($this->service->checkifRolesAreRisky(
            ['ROLE_SHOW_PAYMENT_ORDERS', 'ROLE_EDIT_PAYMENT_ORDERS'],
            ['ROLE_ADMIN', 'ROLE_EDIT_.*']
        ));

        $this->assertTrue($this->service->checkifRolesAreRisky(
            ['ROLE_SHOW_PAYMENT_ORDERS', 'ROLE_EDIT_PAYMENT_ORDERS'],
            ['ROLE_ADMIN', 'ROLE_EDIT_.*']
        ));

        $this->assertTrue($this->service->checkifRolesAreRisky(
            ['ROLE_SHOW_PAYMENT_ORDERS', 'ROLE_SHOW_ORGANISATIONS'],
            ['ROLE_ADMIN', 'ROLE_.*_PAYMENT_ORDERS']
        ));




    }
}
