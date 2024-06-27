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

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks()
 */
trait TimestampTrait
{
    /**
     * @var DateTime|null the date when this element was modified the last time
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    protected ?\DateTime $last_modified = null;

    /**
     * @var DateTime|null the date when this element was created
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    protected ?\DateTime $creation_date = null;

    /**
     * Helper for updating the timestamp. It is automatically called by doctrine before persisting.
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->last_modified = new DateTime('now');
        if (null === $this->creation_date) {
            $this->creation_date = new DateTime('now');
        }
    }

    /**
     * Returns the datetime this element was created. Returns null, if it was not persisted yet.
     */
    public function getCreationDate(): ?DateTime
    {
        return $this->creation_date;
    }

    /**
     * Returns the datetime this element was last time modified. Returns null, if it was not persisted yet.
     */
    public function getLastModified(): ?DateTime
    {
        return $this->last_modified;
    }
}
