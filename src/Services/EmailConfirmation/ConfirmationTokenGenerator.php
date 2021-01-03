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

namespace App\Services\EmailConfirmation;

use InvalidArgumentException;

/**
 * A service to generate a verification token.
 */
class ConfirmationTokenGenerator
{
    private $bytes_length;

    public function __construct(int $bytes_length = 16)
    {
        if ($bytes_length < 10) {
            throw new InvalidArgumentException('$bytes_length must be greater than 10 to be secure!');
        }

        $this->bytes_length = $bytes_length;
    }

    /**
     * Returns a truly random token with a configured length.
     * It returns a hex encoded 16 random bytes.
     */
    public function getToken(): string
    {
        $bytes = random_bytes($this->bytes_length);

        return bin2hex($bytes);
    }
}
