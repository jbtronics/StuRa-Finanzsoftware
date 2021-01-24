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
 * This class describes an funding ID like "M-123-20/21".
 * It consists of three parts:
 * * Prefix: M ("Mittelfreigabe") for internal funding applications, FA ("FinanzAntrag") for external funding applications
 * * Number part: The index of the funding application in the budget year. It always has minimum 3 digits
 * * Budget year part: The budget year which we are in (e.g. "19/20")
 * @ORM\Embeddable
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
        if ($number < 1)
        {
            throw new \InvalidArgumentException('Number part must be greater than zero!');
        }

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
     * Checks if this funding ID is equal to the given one
     * @param  FundingID  $other
     * @return bool
     */
    public function equals(FundingID $other): bool
    {
        //Funding IDs are the same if their string representation is the same
        return (string) $this === (string) $other;
    }

    /**
     * Checks if the given string equals the given funding ID string.
     * @param  string  $input
     * @return bool
     * @throws \InvalidArgumentException If the given input is not a valid funding ID.
     */
    public function equalsString(string $input): bool
    {
        return $this->equals(self::fromString($input));
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

    /**
     * Creates a Funding ID from the given string.
     * @param  string  $formatted_fundingID
     * @param  bool  $caseSensitive
     * @return FundingID
     * @throws \InvalidArgumentException If the given ID is not an valid funding ID
     */
    public static function fromString(string $formatted_fundingID, bool $caseSensitive = false): FundingID
    {
        //First split the funding ID on the dashes
        $parts = explode('-', $formatted_fundingID);

        if (count($parts) !== 3) {
            throw new \InvalidArgumentException("A funding ID must consist of exactly 3 parts separated by a dash!");
        }

        [$prefix, $number, $year_part] = $parts;

        //Normalize prefix if needed
        if (!$caseSensitive) {
            $prefix = strtoupper($prefix);
        }

        if ($prefix === 'M') {
            $external = false;
        } else if ($prefix === 'FA') {
            $external = true;
        } else {
            throw new \InvalidArgumentException('The prefix must be either FA or M!');
        }

        if (!ctype_digit($number)) {
            throw new \InvalidArgumentException('The number part must only consists of numbers!');
        }

        return new self($external, (int) $number, $year_part);
    }
}