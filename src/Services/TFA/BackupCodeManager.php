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

declare(strict_types=1);

namespace App\Services\TFA;

use App\Entity\User;

/**
 * This services offers methods to manage backup codes for two factor authentication.
 */
class BackupCodeManager
{
    protected $backupCodeGenerator;

    public function __construct(BackupCodeGenerator $backupCodeGenerator)
    {
        $this->backupCodeGenerator = $backupCodeGenerator;
    }

    /**
     * Enable backup codes for the given user, by generating a set of backup codes.
     * If the backup codes were already enabled before, they a.
     */
    public function enableBackupCodes(User $user): void
    {
        if (empty($user->getBackupCodes())) {
            $this->regenerateBackupCodes($user);
        }
    }

    /**
     * Disable (remove) the backup codes when no other 2 factor authentication methods are enabled.
     */
    public function disableBackupCodesIfUnused(User $user): void
    {
        if ($user->isGoogleAuthenticatorEnabled()) {
            return;
        }

        $user->setBackupCodes([]);
    }

    /**
     * Generates a new set of backup codes for the user. If no backup codes were available before, new ones are
     * generated.
     *
     * @param User $user The user for which the backup codes should be regenerated
     */
    public function regenerateBackupCodes(User $user): void
    {
        $codes = $this->backupCodeGenerator->generateCodeSet();
        $user->setBackupCodes($codes);
    }
}
