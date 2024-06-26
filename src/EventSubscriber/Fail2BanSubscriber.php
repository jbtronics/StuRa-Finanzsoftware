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

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

/**
 * @TODO: Fix this subscriber
 */
class Fail2BanSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $fail2banLogger, private readonly RequestStack $request)
    {
    }

    public function logFail2Ban(/*AuthenticationFailureEvent $event*/): void
    {
        $ipAddress = $this->request->getCurrentRequest()
            ->getClientIp();
        $this->fail2banLogger->error('Authentication failed for IP: '.$ipAddress);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            //TODO: Fix again
            /*AuthenticationEvents:: => [
                'logFail2Ban',
            ],*/
        ];
    }
}
