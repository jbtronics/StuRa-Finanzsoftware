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
use App\Controller\Admin\DepartmentCrudController;
use App\Controller\Admin\PaymentOrderCrudController;
use App\Controller\Admin\UserCrudController;
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
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => '1234',
        ]);
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

    public function publicPagesProvider(): ?Generator
    {
        //Homepage
        yield ['/payment_order/new'];
        yield ['/'];

        yield from $this->adminPagesProvider();
    }

    public function adminPagesProvider(): ?Generator
    {
        //We need access to AdminUrlGenerator, so we have to boot kernel... This is a bit hacky...
        self::bootKernel();
        /** @var AdminUrlGenerator $adminURL */
        $adminURL = self::$container->get(AdminUrlGenerator::class);

        yield ['/admin'];
        yield ['/admin/audit'];
        yield ['/admin/audit/App-Entity-PaymentOrder'];
        yield ['/admin/audit/App-Entity-PaymentOrder/1'];

        //User admin pages
        yield [$adminURL->setController(UserCrudController::class)->setAction(Action::INDEX)->generateUrl()];
        yield [$adminURL->setController(UserCrudController::class)->setAction(Action::NEW)->generateUrl()];
        yield [$adminURL->setController(UserCrudController::class)->setAction(Action::EDIT)->setEntityId(1)->generateUrl()];
        yield [$adminURL->setController(UserCrudController::class)->setAction(Action::DETAIL)->setEntityId(1)->generateUrl()];

        //BankAccount admin pages
        yield [$adminURL->setController(BankAccountCrudController::class)->setAction(Action::INDEX)->generateUrl()];
        yield [$adminURL->setController(BankAccountCrudController::class)->setAction(Action::NEW)->generateUrl()];
        yield [$adminURL->setController(BankAccountCrudController::class)->setAction(Action::EDIT)->setEntityId(1)->generateUrl()];
        yield [$adminURL->setController(BankAccountCrudController::class)->setAction(Action::DETAIL)->setEntityId(1)->generateUrl()];

        //Department admin pages
        yield [$adminURL->setController(DepartmentCrudController::class)->setAction(Action::INDEX)->generateUrl()];
        yield [$adminURL->setController(DepartmentCrudController::class)->setAction(Action::NEW)->generateUrl()];
        yield [$adminURL->setController(DepartmentCrudController::class)->setAction(Action::EDIT)->setEntityId(1)->generateUrl()];
        yield [$adminURL->setController(DepartmentCrudController::class)->setAction(Action::DETAIL)->setEntityId(1)->generateUrl()];

        //Payment order admin pages
        yield [$adminURL->setController(PaymentOrderCrudController::class)->setAction(Action::INDEX)->generateUrl()];
        yield [$adminURL->setController(PaymentOrderCrudController::class)->setAction(Action::EDIT)->setEntityId(1)->generateUrl()];
        yield [$adminURL->setController(PaymentOrderCrudController::class)->setAction(Action::DETAIL)->setEntityId(1)->generateUrl()];

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
