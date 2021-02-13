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

use DH\Auditor\Provider\Doctrine\Configuration;
use DH\Auditor\User\User;
use DH\Auditor\User\UserInterface as AuditorUserInterface;
use DH\Auditor\User\UserProviderInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProvider implements UserProviderInterface, EventSubscriber
{
    public const CLI_USER_IDENTIFER = '$cli';
    public const INTERNAL_USER_IDENTIFIER = '$internal';

    /**
     * @var string|null
     */
    private $username;

    /**
     * @var string|null
     */
    private $identifier;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Security $security, Configuration $configuration)
    {
        $this->security = $security;
        $this->configuration = $configuration;
    }

    public function setManualUsername(?string $username, ?string $identifier): void
    {
        $this->username = $username;
        $this->identifier = $identifier;
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        //Reset manual set username and identifier after flush
        $this->username = null;
        $this->identifier = null;
    }

    public function __invoke(): ?AuditorUserInterface
    {
        $tokenUser = $this->getTokenUser();
        $impersonatorUser = $this->getImpersonatorUser();

        $identifier = null;
        $username = null;

        if (null !== $tokenUser && $tokenUser instanceof UserInterface) {
            //Use full name of the user if possible
            if ($tokenUser instanceof \App\Entity\User) {
                $identifier = $tokenUser->getUsername();
                $username = (string) $tokenUser;
            } else {
                if (method_exists($tokenUser, 'getId')) {
                    $identifier = $tokenUser->getId();
                }

                $username = $tokenUser->getUsername();
            }
        }

        if (null !== $impersonatorUser && $impersonatorUser instanceof UserInterface) {
            $username .= sprintf('[impersonator %s]', $impersonatorUser->getUsername());
        }

        //Check if a username and identifier were manually provided
        if (!empty($this->username) && !empty($this->identifier)) {
            $username = $this->username;
            $identifier = $this->identifier;
        } elseif ($this->is_cli()) { //Check if we are on command line, then use the username of the user
            $identifier = self::CLI_USER_IDENTIFER;
            $username = 'CLI';
            if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
                $username = sprintf('CLI [%s]', posix_getpwuid(posix_geteuid())['name']);
            }
        }

        if (null === $identifier && null === $username) {
            return null;
        }

        return new User($identifier, $username);
    }

    private function is_cli(): bool
    {
        if (defined('STDIN')) {
            return true;
        }

        if ('cli' === php_sapi_name()) {
            return true;
        }

        if (array_key_exists('SHELL', $_ENV)) {
            return true;
        }

        if (empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) {
            return true;
        }

        if (!array_key_exists('REQUEST_METHOD', $_SERVER)) {
            return true;
        }

        return false;
    }

    /**
     * @return UserInterface|null
     */
    private function getTokenUser()
    {
        try {
            $token = $this->security->getToken();
        } catch (Exception $e) {
            $token = null;
        }

        if (null === $token) {
            return null;
        }

        $tokenUser = $token->getUser();
        if ($tokenUser instanceof UserInterface) {
            return $tokenUser;
        }

        return null;
    }

    /**
     * @return string|UserInterface|null
     */
    private function getImpersonatorUser()
    {
        $token = $this->security->getToken();

        // Symfony >= 5
        if (class_exists(SwitchUserToken::class) && $token instanceof SwitchUserToken) {
            return $token->getOriginalToken()
                ->getUser();
        }

        return null;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::postFlush,
        ];
    }
}
