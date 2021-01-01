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

namespace App\Entity;

use App\Repository\UserRepository;
use App\Validator\NoLockout;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This entity describes a user that can login to the backend system (it needs an ROLE_ADMIN role however).
 * The login is done with the username and a user choosable password. It is possible to configure two factor authentication
 * methods for additional security.
 *
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"username"})
 * @NoLockout(groups={"perm_edit"})
 */
class User implements UserInterface, TwoFactorInterface, BackupCodeInterface, TrustedDeviceInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $role_description = "";

    /**
     * @var string
     * @Assert\Email()
     * @ORM\Column(type="string")
     */
    private $email = "";

    /**
     * @ORM\Column(type="json")
     */
    private $roles = ['ROLE_ADMIN'];

    /**
     * @ORM\Column(type="string")
     */
    private $first_name = "";

    /**
     * @ORM\Column(type="string")
     */
    private $last_name = "";

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @var string|null
     * @Assert\Length(min=6)
     */
    private $plain_password = null;

    /**
     * @ORM\Column(name="googleAuthenticatorSecret", type="string", nullable=true)
     */
    private $googleAuthenticatorSecret;

    /**
     * @ORM\Column(type="integer")
     */
    private $trustedVersion = 0;

    /**
     * @ORM\Column(type="json")
     */
    private $backupCodes = [];

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $backupCodesDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * The name that is used to internally identify this user. Also used as login name.
     * Must be unique for all users.
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    /**
     * Sets the username for this user.
     * @param  string  $username
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Returns all roles for this user.
     * Every user has at least the ROLE_USER role.
     * @return string[]
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Add the given role to this user.
     * @param  string  $new_role
     * @return $this
     */
    public function addRole(string $new_role): self
    {
        $this->roles[] = $new_role;
        $this->roles = array_unique($this->roles);
        return $this;
    }

    /**
     * Sets all roles for this user.
     * @param  string[]  $roles
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Returns the (hashed) password for this user.
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    /**
     * Sets the (hashed) password for this user.
     * Should be generated with UserPasswordEncryptorInterface
     * @param  string  $password
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Not used
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plain_password = null;
    }

    /**
     * Returns the description of what this user does or why he needs an account (the function of the user).
     * @return string
     */
    public function getRoleDescription(): string
    {
        return $this->role_description;
    }

    /**
     * Sets the description of what this user does or why he needs an account (the function of the user).
     * @param  string  $role_description
     * @return User
     */
    public function setRoleDescription(string $role_description): User
    {
        $this->role_description = $role_description;
        return $this;
    }

    /**
     * Returns the email of this user.
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Sets the email of this user.
     * @param  string  $email
     * @return User
     */
    public function setEmail(string $email): User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Return the first name of this user.
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    /**
     * Sets the first name of this user.
     * @param string  $first_name
     * @return User
     */
    public function setFirstName(string $first_name): User
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * Returns the last name of this user.
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    /**
     * Sets the last name of this user.
     * @param  string  $last_name
     * @return User
     */
    public function setLastName(string $last_name)
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * Returns the full name of this user (in the format "first_name last_name")
     * @return string
     */
    public function getFullName(): string
    {
        if (empty($this->getFirstName())) {
            return $this->getLastName();
        }
        if (empty($this->getLastName())) {
            return $this->getFirstName();
        }
        return $this->getFirstName().' '.$this->getLastName();
    }

    /**
     * Returns the temporary saved plain password. We need this to set a password via EasyAdmin interface.
     * A value is only available shortly after it was set via setPlainPassword() and is deleted by eraseCredentials().
     * @return string
     */
    public function getPlainPassword(): ?string
    {
        return $this->plain_password;
    }

    /**
     * Sets the temporary saved plain password. We need this to set a password via EasyAdmin interface.
     * The value is deleted by eraseCredentials().
     * @param  string  $plainPassword
     * @return User
     */
    public function setPlainPassword(?string $plainPassword): User
    {
        $this->plain_password = $plainPassword;
        return $this;
    }

    /**
     * Returns true if this user has any Two-Factor method enabled
     * @return bool
     */
    public function isTFAEnabled(): bool
    {
        return $this->isGoogleAuthenticatorEnabled();
    }

    /**
     * Returns true if this user has google authentication 2FA enabled.
     * @return bool
     */
    public function isGoogleAuthenticatorEnabled(): bool
    {
        return $this->googleAuthenticatorSecret ? true : false;
    }

    /**
     * Returns the username that should be shown to the user for this service, when using google authenticator.
     * Here the standard username is used.
     * @return string
     */
    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->username;
    }

    /**
     * Returns the secret used for google authenticator 2FA.
     * @return string|null
     */
    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    /**
     * Sets the secred used for google authenticator 2FA.
     * @param  string|null  $googleAuthenticatorSecret
     * @return $this
     */
    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): self
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
        return $this;
    }

    /**
     * Returns the trusted token version used to implement trusted device 2FA.
     * @return int
     */
    public function getTrustedTokenVersion(): int
    {
        return $this->trustedVersion;
    }

    /**
     * Invalidate all trusted devices used by this user
     * @return $this
     */
    public function invalidateTrustedDevices(): self
    {
        $this->trustedVersion++;
        return $this;
    }

    /**
     * Check if it is a valid backup code.
     *
     * @param string $code
     *
     * @return bool
     */
    public function isBackupCode(string $code): bool
    {
        //Don't check if no backup codes are defined.
        if (empty($this->backupCodes)) {
            return false;
        }

        return in_array($code, $this->backupCodes, true);
    }

    /**
     * Invalidate a backup code
     *
     * @param string $code
     */
    public function invalidateBackupCode(string $code): void
    {
        $key = array_search($code, $this->backupCodes, true);
        if ($key !== false){
            unset($this->backupCodes[$key]);
        }
    }

    /**
     * Set all backup codes of this user. BackupCodeDate will be updated
     * @param  array  $codes
     * @return $this
     */
    public function setBackupCodes(array $codes): self
    {
        $this->backupCodes = $codes;
        $this->backupCodesDate = new \DateTime();
        return $this;
    }

    /**
     * Returns the date when the backup codes where generated.
     * @return \DateTime
     */
    public function getBackupCodesDate(): ?\DateTime
    {
        return $this->backupCodesDate;
    }

    /**
     * Returns all backup codes of this user
     * @return array
     */
    public function getBackupCodes(): array
    {
        return $this->backupCodes ?? [];
    }

    public function __toString(): string
    {
        return $this->getFullName() . ' (' . $this->username . ')';
    }
}
