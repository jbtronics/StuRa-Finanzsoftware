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

namespace App\Tests;

use Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Just a simple test to ensure that different pages are available (and do not throw an exception).
 *
 * @group DB
 */
class ApplicationAvailabilityTest extends WebTestCase
{
    /**
     * @dataProvider publicPagesProvider
     */
    public function testPublicPages(string $url): void
    {
        //Try to access pages with admin, because he should be able to view every page!
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->catchExceptions(false);

        $client->request('GET', $url);

        self::assertTrue($client->getResponse()->isSuccessful(), 'Request not successful. Status code is '.$client->getResponse()->getStatusCode());
    }

    public function publicPagesProvider(): ?Generator
    {
        //Homepage
        yield ['/payment_order/new'];
        yield ['/'];
    }
}
