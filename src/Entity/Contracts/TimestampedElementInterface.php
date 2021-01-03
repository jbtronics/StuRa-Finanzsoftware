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

namespace App\Entity\Contracts;

use DateTime;

interface TimestampedElementInterface
{
    /**
     * Returns the datetime this element was created. Returns null, if it was not persisted yet.
     */
    public function getCreationDate(): ?DateTime;

    /**
     * Returns the datetime this element was last time modified. Returns null, if it was not persisted yet.
     */
    public function getLastModified(): ?DateTime;
}
