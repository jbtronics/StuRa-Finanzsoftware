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

use App\Services\SEPAExport\GroupHeaderHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GroupHeaderHelperTest extends WebTestCase
{

    /**
     * @var GroupHeaderHelper
     */
    protected $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::$container->get(GroupHeaderHelper::class);
    }

    public function testGetMessageID()
    {
        //Ensure that the message ID starts with the BIC
        $this->assertStringStartsWith('BELADEBE', $this->service->getMessageID('BELADEBE'));
        $this->assertStringStartsWith('BELADEBEXXX', $this->service->getMessageID('BELADEBEXXX'));

        //Ensure that message ID is 27 chars long
        self::assertSame(27, strlen( $this->service->getMessageID('BELADEBE')));
        self::assertSame(27, strlen( $this->service->getMessageID('BELADEBEXXX')));
    }
}
