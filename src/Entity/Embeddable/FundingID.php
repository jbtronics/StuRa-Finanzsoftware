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

namespace App\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 * @package App\Entity\Embeddable
 */
class FundingID
{
    /**
     * @var bool True if this is an external funding application, false if otherwise.
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $external_funding = false;

    /**
     * @var string|null The year part of the funding ID (e.g. "19/20")
     * @ORM\Column(type="string", nullable=false)
     */
    private $year_part;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    private $number;

    public function __construct(bool $is_external_funding, int $number, string $year_part)
    {
        $this->external_funding = $is_external_funding;
        $this->number = $number;
        $this->year_part = $year_part;
    }

    /**
     * Returns whether this is an application of funding of an external organisation.
     * @return bool
     */
    public function isExternalFunding(): bool
    {
        return $this->external_funding;
    }

    /**
     * Returns "M" for internal funding applications and "FA" for external funding applications.
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->external_funding ? "FA" : "M";
    }

    /**
     * Returns the year part of the funding ID (e.g. "19/20")
     * @return string
     */
    public function getYearPart(): string
    {
        return $this->year_part;
    }

    /**
     * Returns the number part of the funding ID (e.g. 123)
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * Returns the funding ID as formatted string. E.g. "FA-123-19/20"
     * @return string
     */
    public function format(): string
    {
        return sprintf("%s-%03d-%s", $this->getPrefix(), $this->getNumber(), $this->getYearPart());
    }

    public function __toString(): string
    {
        return $this->format();
    }
}