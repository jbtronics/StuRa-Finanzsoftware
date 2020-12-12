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

namespace App\Controller\Admin;

use App\Entity\BankAccount;
use App\Entity\Department;
use App\Entity\PaymentOrder;
use App\Entity\User;
use App\Services\GitVersionInfo;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\CrudMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractDashboardController
{
    private $app_version;
    private $crud_url_generator;
    private $gitVersionInfo;

    public function __construct(string $app_version, CrudUrlGenerator $crudUrlGenerator, GitVersionInfo $gitVersionInfo)
    {
        $this->app_version = $app_version;
        $this->crud_url_generator = $crudUrlGenerator;
        $this->gitVersionInfo = $gitVersionInfo;
    }

    public function configureDashboard(): Dashboard
    {
        $dashboard = Dashboard::new()
            ->setTitle('StuRa Finanzen');

        return $dashboard;
    }

    /**
     * @Route("/admin", name="admin_dashboard", )
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }


    private function addFiltersToMenuItem(CrudMenuItem $menuItem, array $filters): CrudMenuItem
    {
        //Set referrer or we encounter errrors... (not needed in JB custom version))

        //$referrer = $this->crud_url_generator->build()->currentPageReferrer;
        //$menuItem->setQueryParameter('referrer', $referrer);

        foreach ($filters as $filter => $value) {
            $menuItem->setQueryParameter('filters[' . $filter . ']', $value);
        }

        $menuItem->setQueryParameter('crudAction', 'index');

        return $menuItem;
    }

    public function configureMenuItems(): iterable
    {

        $mathematically_checking = MenuItem::linkToCrud('payment_order.mathematically_checking_needed', '', PaymentOrder::class)
            ->setDefaultSort(['creation_date' => 'DESC']);
        $this->addFiltersToMenuItem($mathematically_checking, [
            'mathematically_correct' => 0,
            'confirmed' => 1,
        ]);

        $ready_for_export_section = MenuItem::linkToCrud('payment_order.ready_for_export.section', '',PaymentOrder::class)
            ->setDefaultSort(['creation_date' => 'DESC']);
        $this->addFiltersToMenuItem($ready_for_export_section, [
            'mathematically_correct' => 1,
            'exported' => 0,
            'confirmed' => 1,
        ]);


        $factually_checking_fsr = MenuItem::linkToCrud('payment_order.factually_checking_needed.fsr', '',PaymentOrder::class)
            ->setDefaultSort(['creation_date' => 'DESC']);
        $this->addFiltersToMenuItem($factually_checking_fsr, [
            'factually_correct' => 0,
            'department_type' => 'fsr',
            'exported' => 1,
            'confirmed' => 1,
        ]);

        $factually_checking_section = MenuItem::linkToCrud('payment_order.factually_checking_needed.section', '',PaymentOrder::class)
            ->setDefaultSort(['creation_date' => 'DESC']);
        $this->addFiltersToMenuItem($factually_checking_section, [
            'factually_correct' => 0,
            'department_type' => 'section_misc',
            'exported' => 1,
            'confirmed' => 1,
        ]);

        $finished = MenuItem::linkToCrud('payment_order.finished', '', PaymentOrder::class)
            ->setDefaultSort(['creation_date' => 'DESC']);
        $this->addFiltersToMenuItem($finished, [
            'factually_correct' => 1,
            'mathematically_correct' => 1,
            'exported' => 1,
            'confirmed' => 1,
        ]);

        $unconfirmed = MenuItem::linkToCrud('payment_order.unconfirmed', '', PaymentOrder::class)
            ->setDefaultSort(['creation_date' => 'DESC']);
        $this->addFiltersToMenuItem($unconfirmed, [
            'confirmed' => 0,
        ]);


        $items = [
            $mathematically_checking,
            $ready_for_export_section,
            $factually_checking_fsr,
            $factually_checking_section,
            $finished,
            $unconfirmed,
            MenuItem::linkToCrud('payment_order.all', '', PaymentOrder::class),
            ];

        yield MenuItem::subMenu('payment_order.labelp', 'fas fa-file-invoice-dollar')
            ->setPermission('ROLE_SHOW_PAYMENT_ORDERS')
            ->setSubItems($items);

        yield MenuItem::linkToCrud('department.labelp', 'fas fa-sitemap', Department::class)
            ->setPermission('ROLE_READ_ORGANISATIONS');
        yield MenuItem::linkToCrud('bank_account.labelp', 'fas fa-university', BankAccount::class)
            ->setPermission('ROLE_READ_BANK_ACCOUNTS');
        yield MenuItem::linkToCrud('user.labelp', 'fas fa-user', User::class)
            ->setPermission('ROLE_READ_USER');

        $version = $this->app_version . '-' . $this->gitVersionInfo->getGitCommitHash() ?? '';
        yield MenuItem::section('Version ' . $version, 'fas fa-info');
        yield MenuItem::linktoRoute('dashboard.menu.homepage', 'fas fa-home', 'homepage');
        yield MenuItem::linkToUrl('dashboard.menu.stura', 'fab fa-rebel', 'https://www.stura.uni-jena.de/');
        yield MenuItem::linkToUrl('dashboard.menu.github', 'fab fa-github', 'https://github.com/jbtronics/StuRa-Finanzsoftware');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        /** @var User $user */

        return parent::configureUserMenu($user)
            ->setName((string) $user)
            ->displayUserName(true)
            ->addMenuItems([
                               MenuItem::linktoRoute('user.settings.title', 'fas fa-user-cog', 'user_settings'),
                               MenuItem::linktoRoute(Languages::getName('de', 'de') . ' (DE)', '', 'admin_dashboard.de'),
                               MenuItem::linktoRoute(Languages::getName('en', 'en') . ' (EN)', '', 'admin_dashboard.en'),
                           ]);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addCssFile('admin_styles.css');
    }
}
