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

namespace App\Services\SEPAExport;

use App\Entity\User;
use Digitick\Sepa\GroupHeader;
use Symfony\Component\Security\Core\Security;

final class GroupHeaderHelper
{
    //A prefix that should be used for message IDs
    private const MSG_ID_PREFIX = "stura";

    //The maximum message id length
    private const MSG_ID_MAX_LENGTH = 27;

    private $current_user;
    private $app_version;

    public function __construct(Security $security, string $app_version)
    {
        //Ensure the entropy of random variables is long enough (we assume the worst case of a 11 chars BIC)
        $random_length = self::MSG_ID_MAX_LENGTH - strlen(self::MSG_ID_PREFIX) - 11;
        if ($random_length < 8) {
            throw new \RuntimeException('MSG_ID_PREFIX is too long! Message ID entropy would be too low!');
        }

        $this->current_user = $security->getUser();
        $this->app_version = $app_version;
    }

    /**
     * Generates a new random Message ID for the given initiating BIC
     * @param  string  $initiating_bic
     * @return string
     */
    public function getMessageID(string $initiating_bic): string
    {
        if (!$this->checkIsValidBIC($initiating_bic)) {
            throw new \InvalidArgumentException('The given initiating_bic is not a valid BIC!');
        }

        $random_length = self::MSG_ID_MAX_LENGTH - strlen(self::MSG_ID_PREFIX) - strlen($initiating_bic);
        $random = $this->getRandomString($random_length);

        return $initiating_bic . self::MSG_ID_PREFIX . $random;
    }

    /**
     * Returns the name of the initiating party used for SEPA Exports
     * @return string
     */
    public function getInitiatingPartyName(): string
    {
        $username = "NOT LOGGED IN";
        if ($this->current_user instanceof User) {
            $username = $this->current_user->getFullName();
        }

        return sprintf("%s via StuRa-Zahlungssystem v%s", $username, $this->app_version);
    }

    /**
     * Returns a group header with random message id for use with a new export.
     * @param  string  $initiating_bic
     * @return GroupHeader
     */
    public function getGroupHeader(string $initiating_bic): GroupHeader
    {
        return new GroupHeader(
            $this->getMessageID($initiating_bic),
            $this->getInitiatingPartyName()
        );
    }

    /**
     * Check if the given BIC is valid. Currently we just check the length of the BIC.
     * @param  string  $bic
     * @return bool
     */
    private function checkIsValidBIC(string $bic): bool
    {
        $len = strlen($bic);
        return $len === 8 || $len === 11;
    }

    private function getRandomString(int $length): string
    {
        if ($length > 32) {
            throw new \InvalidArgumentException('This function can not generate random strings longer than 32 characters!');
        }
        $bytes = random_bytes(40);
        return substr(md5($bytes), 0, $length);
    }
}