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

namespace App\Tests\Controller\Admin;

use App\Tests\LoginHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PDFGeneratorControllerTest extends WebTestCase
{
    public function testPdfAdminAccess(): void
    {
        $client = static::createClient();
        LoginHelper::loginAsAdmin($client);
        $client->catchExceptions(false);

        $client->request('GET', '/admin/pdf/payment_order/1');

        //Process must be successful
        self::assertStringStartsWith('%PDF', $client->getResponse()->getContent());
        self::assertResponseIsSuccessful();
    }

    public function testPdfNotAuthorized(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        //This line must fail
        $client->request('GET', '/admin/pdf/payment_order/1');
    }
}
