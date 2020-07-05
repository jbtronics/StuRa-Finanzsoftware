<?php

namespace App\Controller\Admin;

use App\Entity\Department;
use App\Entity\PaymentOrder;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\CrudMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractDashboardController
{
    private $app_version;

    public function __construct(string $app_version)
    {
        $this->app_version = $app_version;
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

    public function configureCrud(): Crud
    {
        return Crud::new();
    }

    private function addFiltersToMenuItem(CrudMenuItem $menuItem, array $filters): CrudMenuItem
    {
        //Set referrer or we encounter errrors...
        $menuItem->setQueryParameter('referrer', '');

        foreach ($filters as $filter => $value) {
            $menuItem->setQueryParameter('filters[' . $filter . ']', $value);
        }

        return $menuItem;
    }

    public function configureMenuItems(): iterable
    {

        //Incoming entry must be have both fields not set.
        $incoming = MenuItem::linkToCrud('payment_order.incoming', '', PaymentOrder::class);
        $this->addFiltersToMenuItem($incoming, [
            'factually_correct' => 0,
            'mathematically_correct' => 0,
        ]);

        $factually_checking = MenuItem::linkToCrud('payment_order.factually_checking_needed', '',PaymentOrder::class);
        $this->addFiltersToMenuItem($factually_checking, [
           'factually_correct' => 0,
        ]);

        $mathematically_checking = MenuItem::linkToCrud('payment_order.mathematically_checking_needed', '', PaymentOrder::class);
        $this->addFiltersToMenuItem($mathematically_checking, [
            'mathematically_correct' => 0,
        ]);

        $finished = MenuItem::linkToCrud('payment_order.finished', '', PaymentOrder::class);
        $this->addFiltersToMenuItem($finished, [
            'factually_correct' => 1,
            'mathematically_correct' => 1,
        ]);



        $items = [
            $incoming,
            $mathematically_checking,
            $factually_checking,
            $finished,
            MenuItem::linkToCrud('payment_order.all', '', PaymentOrder::class),
            ];

        yield MenuItem::subMenu('payment_order.labelp', 'fas fa-file-invoice-dollar')
            ->setPermission('ROLE_SHOW_PAYMENT_ORDERS')
            ->setSubItems($items);

        yield MenuItem::linkToCrud('department.labelp', 'fas fa-sitemap', Department::class);
        yield MenuItem::linkToCrud('user.labelp', 'fas fa-user', User::class);

        yield MenuItem::section('Version ' . $this->app_version, 'fas fa-info');
        yield MenuItem::linktoRoute('dashboard.menu.homepage', 'fas fa-home', 'homepage');
        yield MenuItem::linkToUrl('dashboard.menu.stura', 'fab fa-rebel', 'https://www.stura.uni-jena.de/');
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
}
