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

use App\Entity\PaymentOrder;
use App\Repository\PaymentOrderRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExportControllerTest extends WebTestCase
{
    public function testExportAutoSingleMode(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => '1234',
        ]);
        $client->catchExceptions(false);

        /** @var AdminUrlGenerator $adminURL */
        $adminURLGenerator = self::$container->get(AdminUrlGenerator::class);
        $url = $adminURLGenerator->setRoute('payment_order_export')
            ->set('ids', '1,3')
            ->generateUrl();

        $crawler = $client->request('GET', $url);
        self::assertResponseIsSuccessful();

        $client->submitForm('Download', [
            'sepa_export[mode]' => 'auto_single',
        ]);

        self::assertResponseIsSuccessful();
        //If multiple payment orders are generated the exported files are in a ZIP
        self::assertResponseHeaderSame('content-type', 'application/zip');

        //Assume that the payment orders got the exported flag set
        $repo = self::$container->get(PaymentOrderRepository::class);
        /** @var PaymentOrder $payment_order1 */
        $payment_order1 = $repo->find(1);
        self::assertTrue($payment_order1->isExported());
        /** @var PaymentOrder $payment_order3 */
        $payment_order3 = $repo->find(3);
        self::assertTrue($payment_order3->isExported());
    }

    public function testExportAutoSingleModeOneExport(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => '1234',
        ]);
        $client->catchExceptions(false);

        /** @var AdminUrlGenerator $adminURL */
        $adminURLGenerator = self::$container->get(AdminUrlGenerator::class);
        $url = $adminURLGenerator->setRoute('payment_order_export')
            ->set('ids', '1')
            ->generateUrl();

        $crawler = $client->request('GET', $url);
        self::assertResponseIsSuccessful();

        $client->submitForm('Download', [
            'sepa_export[mode]' => 'auto_single',
        ]);

        self::assertResponseIsSuccessful();
        //If one payment order is exported a single XML file is generated
        self::assertResponseHeaderSame('content-type', 'application/xml');

        //Assume that the payment orders got the exported flag set
        $repo = self::$container->get(PaymentOrderRepository::class);
        /** @var PaymentOrder $payment_order1 */
        $payment_order1 = $repo->find(1);
        self::assertTrue($payment_order1->isExported());
    }
}
