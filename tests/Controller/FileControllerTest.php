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

namespace App\Tests\Controller;

use App\Tests\LoginHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class FileControllerTest extends WebTestCase
{
    public function testPaymentOrderFormAdminAccess(): void
    {
        $client = static::createClient();
        LoginHelper::loginAsAdmin($client);
        $client->catchExceptions(false);

        $client->request('GET', '/file/payment_order/1/form');

        //As the response is streamed, we need to use this to get the correct content
        $contents = $client->getInternalResponse()->getContent();

        //Process must be successful
        self::assertStringStartsWith('%PDF', $contents);
        self::assertResponseIsSuccessful();
    }

    public function testPaymentOrderFormTokenAccess(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $client->request('GET', '/file/payment_order/1/form?token=1&secret=token1');

        //As the response is streamed, we need to use this to get the correct content
        $contents = $client->getInternalResponse()->getContent();

        //Process must be successful
        self::assertStringStartsWith('%PDF', $contents);
        self::assertResponseIsSuccessful();
    }

    public function testPaymentOrderFormNotAuthorized(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        //We must wrap the request into output buffering, as a StreamedResponse is returned which is otherwise outputed to stdout
        //This must fail
        $client->request('GET', '/file/payment_order/1/form');
    }

    public function testPaymentOrderFormNotAuthorizedInvalidToken(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        //We must wrap the request into output buffering, as a StreamedResponse is returned which is otherwise outputed to stdout
        //This must fail
        $client->request('GET', '/file/payment_order/1/form?token=1&secret=token2');
    }

    public function testPaymentOrderReferencesAdminAccess(): void
    {
        $client = static::createClient();
        LoginHelper::loginAsAdmin($client);
        $client->catchExceptions(false);

        //We must wrap the request into output buffering, as a StreamedResponse is returned which is otherwise outputed to stdout
        $client->request('GET', '/file/payment_order/1/references');

        //As the response is streamed, we need to use this to get the correct content
        $contents = $client->getInternalResponse()->getContent();

        //Process must be successful
        self::assertStringStartsWith('%PDF', $contents);
        self::assertResponseIsSuccessful();
    }

    public function testPaymentOrderReferencesNotAuthorized(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        //We must wrap the request into output buffering, as a StreamedResponse is returned which is otherwise outputed to stdout
        //This must fail
        $client->request('GET', '/file/payment_order/1/references');
    }

    public function testPaymentOrderReferencesNotAuthorizedInvalidToken(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        //We must wrap the request into output buffering, as a StreamedResponse is returned which is otherwise outputed to stdout
        //This must fail
        $client->request('GET', '/file/payment_order/1/references?confirm=1&token=invalid');
    }

    public function testPaymentOrderReferencesTokenAccess(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $client->request('GET', '/file/payment_order/1/references?token=1&secret=token1');

        //As the response is streamed, we need to use this to get the correct content
        $contents = $client->getInternalResponse()->getContent();

        //Process must be successful
        self::assertStringStartsWith('%PDF', $contents);
        self::assertResponseIsSuccessful();
    }
}
