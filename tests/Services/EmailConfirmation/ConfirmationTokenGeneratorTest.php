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

namespace App\Tests\Services\EmailConfirmation;

use App\Services\EmailConfirmation\ConfirmationTokenGenerator;
use PHPUnit\Framework\TestCase;

class ConfirmationTokenGeneratorTest extends TestCase
{
    public function testLengthCheck(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $service = new ConfirmationTokenGenerator(5);
    }

    public function testGetToken(): void
    {
        $service = new ConfirmationTokenGenerator();
        //Ensure that only hex strings are returned
        self::assertRegExp("/[a-f0-9]+/", $service->getToken());
        //Ensure length
        self::assertSame(32, strlen($service->getToken()));

        //Test if we can change the length
        $service = new ConfirmationTokenGenerator(10);
        self::assertSame(20, strlen($service->getToken()));
    }
}
