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

namespace App\Exception;

use App\Entity\Department;
use RuntimeException;

final class SEPAExportAutoModeNotPossible extends RuntimeException
{
    public function __construct(private readonly ?Department $wrong_department = null)
    {
        parent::__construct('Auto Mode not possible as a department is missing a default bank account');
    }

    /**
     * Returns the Department which is missing the default account definition.
     */
    public function getWrongDepartment(): ?Department
    {
        return $this->wrong_department;
    }
}
