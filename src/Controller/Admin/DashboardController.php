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
use App\Entity\Confirmer;
use App\Entity\Contracts\DBElementInterface;
use App\Entity\Department;
use App\Entity\PaymentOrder;
use App\Entity\User;
use App\Services\GitVersionInfo;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\CrudMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

final class DashboardController extends AbstractDashboardController
{
    private const FILTER_DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    public function __construct(private readonly string $app_version, private readonly GitVersionInfo $gitVersionInfo)
    {
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('StuRa Finanzen');
    }

    #[Route(path: '/admin', name: 'admin_dashboard')]
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
            if (is_array($value)) {
                foreach ($value as $subfilter => $subvalue) {
                    $menuItem->setQueryParameter('filters['.$filter.']['.$subfilter.']', $subvalue);
                }
            } else {
                $menuItem->setQueryParameter('filters['.$filter.']', $value);
            }
        }

        $menuItem->setQueryParameter('crudAction', 'index');

        //Add a unique (not parsed) query parameter, so that the correct menu item is selected
        $hash = substr(md5(serialize($filters)), 0, 8);
        $menuItem->setQueryParameter('menuHash', $hash);

        return $menuItem;
    }

    public function configureActions(): Actions
    {
        $actions = parent::configureActions();

        $showLog = Action::new('showLog', 'action.show_logs', 'fas fa-binoculars')
            ->displayIf(fn(DBElementInterface $entity): bool => $this->isGranted('ROLE_VIEW_AUDITS'))
            ->setCssClass('btn btn-secondary')
            ->linkToRoute('dh_auditor_show_entity_history', fn(DBElementInterface $entity): array => [
                'entity' => str_replace('\\', '-', $entity::class),
                'id' => $entity->getId(),
            ]);

        return $actions
            ->add(Crud::PAGE_DETAIL, $showLog)
            ->add(Crud::PAGE_EDIT, $showLog);
    }

    public function configureMenuItems(): iterable
    {
        /* Menu items for payment orders menu */

        $mathematically_checking = MenuItem::linkToCrud('payment_order.mathematically_checking_needed', '', PaymentOrder::class)
            ->setDefaultSort([
                'creation_date' => 'ASC',
            ]);
        $this->addFiltersToMenuItem($mathematically_checking, [
            'mathematically_correct' => 0,
            'confirmed' => 1,
        ]);

        $ready_for_export_section = MenuItem::linkToCrud('payment_order.ready_for_export.section', '', PaymentOrder::class)
            ->setDefaultSort([
                'creation_date' => 'ASC',
            ]);
        $this->addFiltersToMenuItem($ready_for_export_section, [
            'mathematically_correct' => 1,
            'exported' => 0,
            'confirmed' => 1,
        ]);

        $factually_checking_fsr = MenuItem::linkToCrud('payment_order.factually_checking_needed.fsr', '', PaymentOrder::class)
            ->setDefaultSort([
                'creation_date' => 'ASC',
            ]);
        $this->addFiltersToMenuItem($factually_checking_fsr, [
            'factually_correct' => 0,
            'department_type' => 'fsr',
            'exported' => 1,
            'confirmed' => 1,
        ]);

        $factually_checking_section = MenuItem::linkToCrud('payment_order.factually_checking_needed.section', '', PaymentOrder::class)
            ->setDefaultSort([
                'creation_date' => 'ASC',
            ]);
        $this->addFiltersToMenuItem($factually_checking_section, [
            'factually_correct' => 0,
            'department_type' => 'section_misc',
            'exported' => 1,
            'confirmed' => 1,
        ]);

        $finished = MenuItem::linkToCrud('payment_order.finished', '', PaymentOrder::class)
            ->setDefaultSort([
                'creation_date' => 'DESC',
            ]);
        $this->addFiltersToMenuItem($finished, [
            'factually_correct' => 1,
            'mathematically_correct' => 1,
            'exported' => 1,
            'confirmed' => 1,
        ]);

        $unconfirmed = MenuItem::linkToCrud('payment_order.unconfirmed', '', PaymentOrder::class)
            ->setDefaultSort([
                'creation_date' => 'ASC',
            ]);
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

        /* Menu items for accountancy firm menu */
        $accountancy_exported_this_month = MenuItem::linkToCrud('accountancy_firm_menu.exported_this_month', '', PaymentOrder::class);
        $this->addFiltersToMenuItem($accountancy_exported_this_month, [
            'references_exported' => false,
            'factually_correct' => true,
            'booking_date' => [
                'comparison' => 'between',
                'value' => (new \DateTime('first day of this month'))->setTime(0, 0, 0)
                    ->format(self::FILTER_DATETIME_FORMAT),
                'value2' => (new \DateTime('last day of this month'))->setTime(23, 59, 59)
                    ->format(self::FILTER_DATETIME_FORMAT),
            ],
        ]);

        /* Menu items for accountancy firm menu */
        $accountancy_exported_last_month = MenuItem::linkToCrud('accountancy_firm_menu.exported_last_month', '', PaymentOrder::class);
        $this->addFiltersToMenuItem($accountancy_exported_last_month, [
            'references_exported' => false,
            'factually_correct' => true,
            'booking_date' => [
                'comparison' => 'between',
                'value' => (new \DateTime('first day of last month'))->setTime(0, 0, 0)
                    ->format(self::FILTER_DATETIME_FORMAT),
                'value2' => (new \DateTime('last day of last month'))->setTime(23, 59, 59)
                    ->format(self::FILTER_DATETIME_FORMAT),
            ],
        ]);

        $accountancy_exported = MenuItem::linkToCrud('accountancy_firm_menu.exported', '', PaymentOrder::class);
        $this->addFiltersToMenuItem($accountancy_exported, [
            'references_exported' => true,
            'factually_correct' => true,
        ]);

        $accountancy_not_exported_all = MenuItem::linkToCrud('accountancy_firm_menu.not_exported.all', '', PaymentOrder::class);
        $this->addFiltersToMenuItem($accountancy_not_exported_all, [
            'references_exported' => false,
        ]);

        yield MenuItem::subMenu('accountancy_firm_menu.label', 'fas fa-balance-scale')
            ->setPermission('ROLE_EXPORT_REFERENCES')
            ->setSubItems([
                MenuItem::section('accountancy_firm_menu.references', 'fas fa-file-download'),
                $accountancy_exported_this_month,
                $accountancy_exported_last_month,
                $accountancy_not_exported_all,
                $accountancy_exported,
            ]);

        yield MenuItem::subMenu('payment_order.labelp', 'fas fa-file-invoice-dollar')
            ->setPermission('ROLE_SHOW_PAYMENT_ORDERS')
            ->setSubItems($items);

        yield MenuItem::linkToCrud('department.labelp', 'fas fa-sitemap', Department::class)
            ->setPermission('ROLE_READ_ORGANISATIONS');
        yield MenuItem::linkToCrud('confirmer.labelp', 'fas fa-user-check', Confirmer::class)
            ->setPermission('ROLE_READ_ORGANISATIONS');
        yield MenuItem::linkToCrud('bank_account.labelp', 'fas fa-university', BankAccount::class)
            ->setPermission('ROLE_READ_BANK_ACCOUNTS');
        yield MenuItem::linkToCrud('user.labelp', 'fas fa-user', User::class)
            ->setPermission('ROLE_READ_USER');

        $version = $this->app_version.'-'.$this->gitVersionInfo->getGitCommitHash() ?? '';
        yield MenuItem::section('Version '.$version, $this->infoIconEasterEgg());
        yield MenuItem::linktoRoute('dashboard.menu.audits', 'fas fa-binoculars', 'dh_auditor_list_audits')
            ->setPermission('ROLE_VIEW_AUDITS');
        yield MenuItem::linktoRoute('dashboard.menu.homepage', 'fas fa-home', 'homepage');
        yield MenuItem::linkToUrl('dashboard.menu.stura', 'fab fa-rebel', 'https://www.stura.uni-jena.de/');
        yield MenuItem::linkToUrl('dashboard.menu.github', 'fab fa-github', 'https://github.com/jbtronics/StuRa-Finanzsoftware');
    }

    /**
     * A little easteregg, that show different icons for the version info, depending on the date
     * @param  \DateTimeInterface|null  $dateTime
     * @return string
     */
    private function infoIconEasterEgg(?\DateTimeInterface $dateTime = null): string
    {
        if ($dateTime === null) {
            $dateTime = new \DateTimeImmutable();
        }

        $day = (int) $dateTime->format('d');
        $month = (int) $dateTime->format('m');

        return 'fas ' . match ([$day, $month]) {
                [31, 10] => 'fa-ghost',
                [24, 12] => 'fa-gifts',
                [25, 12] => 'fa-sleigh',
                [31, 12] => 'fa-champagne-glasses',
                [1, 1] => 'fa-clover',
                [29, 2] => 'fa-frog',
                [13, 4] => 'fa-cake-candles',
                [4, 5] => 'fa-jedi',
                [14, 3] => 'fa-chart-pie',
                [20, 9] => 'fa-children',
                [3, 10] => 'fa-flag',
                [9, 5] => 'fa-earth-europe',

                [1, 10] => 'fa-snowflake',
                [1, 4] => 'fa-sun',

                default => 'fa-info'
            };
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        /** @var User $user */

        return parent::configureUserMenu($user)
            ->setName((string) $user)
            ->displayUserName(true)
            ->addMenuItems([
                MenuItem::linktoRoute('user.settings.title', 'fas fa-user-cog', 'user_settings'),
            ]);
    }

    public function configureCrud(): Crud
    {
        return parent::configureCrud()
            ->setPaginatorPageSize(40);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            //->addJsFile('configurable-date-input-polyfill.dist.js')
            ->addJsFile('assets/js/jquery.slim.js')
            ->addJsFile('assets/js/select2.full.min.js')
            ->addCssFile('assets/css/select2.min.css')
            ->addCssFile('admin_styles.css');
    }
}
