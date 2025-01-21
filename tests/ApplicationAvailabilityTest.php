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

use App\Controller\Admin\BankAccountCrudController;
use App\Controller\Admin\DashboardController;
use App\Controller\Admin\DepartmentCrudController;
use App\Controller\Admin\PaymentOrderCrudController;
use App\Controller\Admin\UserCrudController;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
    public function testPages(string $url): void
    {
        self::ensureKernelShutdown();

        //Try to access pages with admin, because he should be able to view every page!
        $client = static::createClient();
        LoginHelper::loginAsAdmin($client);
        $client->catchExceptions(false);

        $client->request('GET', $url);

        self::assertTrue($client->getResponse()->isSuccessful(), 'Request not successful. Status code is '.$client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider adminPagesProvider
     */
    public function testEnsureAdminProtection(string $url): void
    {
        self::ensureKernelShutdown();

        //Ensure that admin backendend can not be accessed without protections

        $client = static::createClient();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        //This line must throw an exception
        $client->request('GET', $url);
    }

    public function publicPagesProvider(): \Generator
    {
        //Homepage
        yield ['/payment_order/new'];
        yield ['/'];

        yield from $this->adminPagesProvider();
    }

    public function adminPagesProvider(): \Generator
    {
        //We need access to AdminUrlGenerator, so we have to boot kernel... This is a bit hacky...
        self::bootKernel();
        /** @var AdminUrlGenerator $adminURL */
        $adminURL = self::getContainer()->get(AdminUrlGenerator::class);
        $adminURL->setDashboard(DashboardController::class);

        yield ['/admin'];

        yield ['/admin?routeName=user_settings'];

        yield ['/admin/audit'];
        yield ['/admin/audit/App-Entity-PaymentOrder'];
        yield ['/admin/audit/App-Entity-PaymentOrder/1'];

        //User admin pages
        yield ['/admin/user'];
        yield ['/admin/user/new'];
        yield ['/admin/user/1'];
        yield ['/admin/user/1/edit'];

        //BankAccount admin pages
        yield ['/admin/bank-account'];
        yield ['/admin/bank-account/new'];
        yield ['/admin/bank-account/1'];
        yield ['/admin/bank-account/1/edit'];

        //Department admin pages
        yield ['/admin/department'];
        yield ['/admin/department/new'];
        yield ['/admin/department/1'];
        yield ['/admin/department/1/edit'];

        //Confirmer admin pages
        yield ['/admin/confirmer'];
        yield ['/admin/confirmer/new'];
        yield ['/admin/confirmer/1'];
        yield ['/admin/confirmer/1/edit'];

        //Payment order admin pages
        yield ['/admin/payment_order'];
        yield ['/admin/payment_order/1'];
        yield ['/admin/payment_order/1/edit'];

        //Manually confirm page
        yield [$adminURL->setRoute('payment_order_manual_confirm', [
            'id' => 1,
        ])->generateUrl()];

        //User settings
        yield [$adminURL->setRoute('user_settings')->generateUrl()];

        //Export page
        yield [$adminURL->setRoute('payment_order_export')->set('ids', '1,2,4')->generateUrl()];
    }
}
