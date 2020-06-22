<?php

namespace App\Controller\Admin;

use App\Entity\Department;
use App\Entity\PaymentOrder;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
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

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('payment_order.labelp', 'fas fa-file-invoice-dollar', PaymentOrder::class);
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
