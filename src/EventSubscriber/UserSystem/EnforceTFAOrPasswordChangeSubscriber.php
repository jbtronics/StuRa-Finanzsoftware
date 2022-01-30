<?php
/*
 * Copyright (C)  2020-2022  Jan Böhmer
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

namespace App\EventSubscriber\UserSystem;

use App\Entity\User;
use App\Services\UserSystem\EnforceTFARedirectHelper;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\HttpUtils;

class EnforceTFAOrPasswordChangeSubscriber implements EventSubscriberInterface
{
    /**
     * @var string[] The routes the user is allowed to access without being redirected.
     *               This should be only routes related to login/logout and user settings
     */
    public const ALLOWED_ROUTES = [
        'user_settings',
        'app_login',
        '2fa_login',
        '2fa_login_check',
        'logout',
    ];

    /**
     * @var string The route the user will redirected to, if he needs to change this password
     */
    public const REDIRECT_TARGET = 'user_settings';
    private $security;
    private $flashBag;
    private $httpUtils;
    private $TFARedirectHelper;
    private $adminUrlGenerator;

    public function __construct(Security $security, SessionInterface $session, HttpUtils $httpUtils, EnforceTFARedirectHelper $TFARedirectHelper, AdminUrlGenerator $adminUrlGenerator)
    {
        /** @var Session $session */
        $this->security = $security;
        $this->flashBag = $session->getFlashBag();
        $this->httpUtils = $httpUtils;
        $this->TFARedirectHelper = $TFARedirectHelper;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    /**
     * This function is called when the kernel encounters a request.
     * It checks if the user must change its password or add an 2FA mehtod and redirect it to the user settings page,
     * if needed.
     */
    public function redirectToSettingsIfNeeded(RequestEvent $event): void
    {
        $user = $this->security->getUser();
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        //If the user is not an User entity skip this handler
        if (!$user instanceof User) {
            return;
        }

        $tfa_redirect_needed = $this->TFARedirectHelper->doesUserNeedRedirectForTFAEnforcement($user);

        //Abort if we dont need to redirect the user.
        if (!$user->isPasswordChangeNeeded() && !$tfa_redirect_needed) {
            return;
        }

        //Check for a whitelisted URL
        if ($this->checkIfAllowedPath($request)) {
            return;
        }

        //Show appropriate message to user about the reason he was redirected
        if ($user->isPasswordChangeNeeded()) {
            $this->flashBag->add('warning', 'user.pw_change_needed.flash');
        }

        if ($tfa_redirect_needed) {
            $this->flashBag->add('warning', 'user.2fa_needed.flash');
        }

        $event->setResponse($this->httpUtils->createRedirectResponse($request,
            $this->adminUrlGenerator->setRoute(static::REDIRECT_TARGET)->generateUrl()
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'redirectToSettingsIfNeeded',
        ];
    }

    private function checkIfAllowedPath(Request $request): bool
    {
        foreach (static::ALLOWED_ROUTES as $route) {
            //Check for "normal" (not ea admin routes)
            if ( $this->httpUtils->checkRequestPath($request, $route)) {
                return true;
            }

            //Check for routes accessed using admin context
            if ($this->httpUtils->checkRequestPath($request, 'admin_dashboard')
                && $request->query->get('routeName', '') === $route) {
                return true;
            }


        }

        return false;
    }
}