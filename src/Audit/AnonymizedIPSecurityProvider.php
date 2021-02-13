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

namespace App\Audit;

use DH\Auditor\Security\SecurityProviderInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\RequestStack;

class AnonymizedIPSecurityProvider implements SecurityProviderInterface
{
    private $requestStack;
    private $firewallMap;

    public function __construct(RequestStack $requestStack, FirewallMap $firewallMap)
    {
        $this->requestStack = $requestStack;
        $this->firewallMap = $firewallMap;
    }

    public function __invoke(): array
    {
        $clientIp = null;
        $firewallName = null;

        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
            $firewallConfig = $this->firewallMap->getFirewallConfig($request);

            $clientIp = $request->getClientIp();
            //Censor clientIP to be GPDR conform
            $clientIp = IpUtils::anonymize($clientIp);

            $firewallName = null === $firewallConfig ? null : $firewallConfig->getName();
        }

        return [$clientIp, $firewallName];
    }
}
