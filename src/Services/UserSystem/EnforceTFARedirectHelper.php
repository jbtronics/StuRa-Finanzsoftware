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

namespace App\Services\UserSystem;

use App\Entity\User;
use LogicException;

class EnforceTFARedirectHelper
{
    /** @var bool */
    private $enforce_tfa;

    /** @var string[] */
    private $risky_roles;

    public function __construct(bool $enforce_tfa, array $risky_roles)
    {
        foreach ($risky_roles as $role) {
            if (!is_string($role)) {
                throw new LogicException('All Roles must be an string!');
            }
        }

        $this->enforce_tfa = $enforce_tfa;
        $this->risky_roles = $risky_roles;
    }

    /**
     * Check if the enforcement of TFA is enabled.
     * @return bool
     */
    public function isTFAEnforcementEnabled(): bool
    {
        return $this->enforce_tfa;
    }

    /**
     * Check if the given user has roles that are considered risky.
     * @param  User  $user
     * @return bool
     */
    public function checkIfUserHasRiskyRoles(User $user): bool
    {
        return $this->checkifRolesAreRisky($user->getRoles());
    }

    /**
     * Check if the given user needs a redirect to settings, because TFA enforcement is enabled and the user has risky roles.
     * @param  User  $user
     * @return bool
     */
    public function doesUserNeedRedirectForTFAEnforcement(User $user): bool
    {
        return $this->isTFAEnforcementEnabled()
            && !$user->isTFAEnabled()
            && $this->checkIfUserHasRiskyRoles($user);
    }

    /**
     * Check if one of the given roles is considered risky
     * @param  array  $roles
     * @return void
     */
    public function checkifRolesAreRisky(array $roles, ?array $risky_roles = null): bool
    {
        if ($risky_roles === null) {
            $risky_roles = $this->risky_roles;
        }

        foreach ($roles as $role) {
            foreach ($risky_roles as $risky_role) {
                if (preg_match('/'. $risky_role . '/', $role)) {
                    return true;
                }
            }
        }

        return false;
    }
}