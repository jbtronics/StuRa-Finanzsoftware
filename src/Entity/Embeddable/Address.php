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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This embeddable contains informations about a postal address.
 * @ORM\Embeddable
 * @package App\Entity\Embeddable
 */
class Address
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $street_number = '';

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $zip_code = '';

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $city = '';

    /**
     * Returns the street and house no. of this address.
     *
     * @return string
     */
    public function getStreetNumber(): string
    {
        return $this->street_number;
    }

    /**
     * Sets the street and house no. of this address
     */
    public function setStreetNumber(string $street): Address
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Returns the zip code of this address.
     *
     * @return string
     */
    public function getZipCode(): string
    {
        return $this->zip_code;
    }

    /**
     * Sets the zip code of this address
     */
    public function setZipCode(string $zip_code): Address
    {
        $this->zip_code = $zip_code;

        return $this;
    }

    /**
     * Returns the city name of this address
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Sets the city name of this address
     */
    public function setCity(string $city): Address
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Returns the address formatted in a single line (e.g. "Street 1, 12345 City")
     * @return string
     */
    public function formatAddressSingleLine(): string
    {
        return sprintf("%s, %s %s", $this->getStreetNumber(), $this->getZipCode(), $this->getCity());
    }

    /**
     * Returns the address formatted in multiple lines:
     * "Street 1
     * 12345 City"
     * @return string
     */
    public function formatAddressMultiLine(): string
    {
        return sprintf("%s\n%s %s", $this->getStreetNumber(), $this->getZipCode(), $this->getCity());
    }
}