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

namespace App\Tests\Entity;

use App\Entity\Department;
use PHPUnit\Framework\TestCase;

class DepartmentTest extends TestCase
{

    public function testIsFSR()
    {
        $department = new Department();
        $department->setType(Department::TYPE_FSR);
        self::assertTrue($department->isFSR());
        $department->setType(Department::TYPE_SECTION);
        self::assertFalse($department->isFSR());
        $department->setType(Department::TYPE_ADMINISTRATIVE);
        self::assertFalse($department->isFSR());
        $department->setType("invalid");
        self::assertFalse($department->isFSR());
    }

    public function testIsAdministrative()
    {
        $department = new Department();
        $department->setType(Department::TYPE_FSR);
        self::assertFalse($department->isAdministrative());
        $department->setType(Department::TYPE_SECTION);
        self::assertFalse($department->isAdministrative());
        $department->setType(Department::TYPE_ADMINISTRATIVE);
        self::assertTrue($department->isAdministrative());
        $department->setType("invalid");
        self::assertFalse($department->isAdministrative());
    }

    public function testIsSection()
    {
        $department = new Department();
        $department->setType(Department::TYPE_FSR);
        self::assertFalse($department->isSection());
        $department->setType(Department::TYPE_SECTION);
        self::assertTrue($department->isSection());
        $department->setType(Department::TYPE_ADMINISTRATIVE);
        self::assertFalse($department->isSection());
        $department->setType("invalid");
        self::assertFalse($department->isSection());
    }
}
