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

use Exception;
use RuntimeException;

/**
 * This class generates random backup codes for two factor authentication.
 */
class BackupCodeGenerator
{
    protected $code_length;
    protected $code_count;

    /**
     * BackupCodeGenerator constructor.
     *
     * @param int $code_length How many characters a single code should have.
     * @param int $code_count  How many codes are generated for a whole backup set.
     */
    public function __construct(int $code_length, int $code_count)
    {
        if ($code_length > 32) {
            throw new RuntimeException('Backup code can have maximum 32 digits!');
        }
        if ($code_length < 6) {
            throw new RuntimeException('Code must have at least 6 digits to ensure security!');
        }

        $this->code_count = $code_count;
        $this->code_length = $code_length;
    }

    /**
     * Generates a single backup code.
     * It is a random hexadecimal value with the digit count configured in constructor.
     *
     * @return string The generated backup code (e.g. 1f3870be2)
     *
     * @throws Exception If no entropy source is available.
     */
    public function generateSingleCode(): string
    {
        $bytes = random_bytes(32);

        return substr(md5($bytes), 0, $this->code_length);
    }

    /**
     * Returns a full backup code set. The code count can be configured in the constructor.
     *
     * @return string[] An array containing different backup codes.
     */
    public function generateCodeSet(): array
    {
        $array = [];
        for ($n = 0; $n < $this->code_count; ++$n) {
            $array[] = $this->generateSingleCode();
        }

        return $array;
    }
}
