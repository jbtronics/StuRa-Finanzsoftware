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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This embeddable contains all information about the person which should receive a payment of a payment order.
 *
 * @ORM\Embeddable()
 */
class BankAccountInfo
{
    /**
     * @Assert\NotBlank()
     * @var string
     * @ORM\Column(type="string")
     */
    private $account_owner = "";

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $street = "";

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $zip_code = "";

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $city = "";

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\Iban()
     */
    private $iban = "";

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\Bic(ibanPropertyPath="iban")
     */
    private $bic = "";

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $bank_name = "";

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $reference = "";

    public function __construct()
    {

    }

    /**
     * Returns the name of the account owner / payment receiver
     * @return string
     */
    public function getAccountOwner(): ?string
    {
        return $this->account_owner;
    }

    /**
     * Sets the name of account owner / payment receiver
     * @param  string  $account_owner
     * @return BankAccountInfo
     */
    public function setAccountOwner(string $account_owner): BankAccountInfo
    {
        $this->account_owner = $account_owner;
        return $this;
    }

    /**
     * Returns the street and house no. where the payment receiver lives.
     * @return string
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * Sets the street and house no. where the payment receiver lives.
     * @param  string  $street
     * @return BankAccountInfo
     */
    public function setStreet(string $street): BankAccountInfo
    {
        $this->street = $street;
        return $this;
    }

    /**
     * Returns the zip code where the payment receiver lives.
     * @return string
     */
    public function getZipCode(): ?string
    {
        return $this->zip_code;
    }

    /**
     * Sets the zip code where the payment receiver lives.
     * @param  string  $zip_code
     * @return BankAccountInfo
     */
    public function setZipCode(string $zip_code): BankAccountInfo
    {
        $this->zip_code = $zip_code;
        return $this;
    }

    /**
     * Returns the city name where the payment receiver lives.
     * @return string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Sets the city name where the payment receiver lives.
     * @param  string  $city
     * @return BankAccountInfo
     */
    public function setCity(string $city): BankAccountInfo
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Returns the IBAN of the receivers bank account.
     * The IBAN is formatted with spaces after it was validated by IBAN constraint, so the returned value containes
     * spaces.
     * @return string
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * Sets the IBAN of the receivers bank account.
     * The IBAN will be formatted with spaces after it was validated by IBAN constraint.
     * @param  string  $iban
     * @return BankAccountInfo
     */
    public function setIban(string $iban): BankAccountInfo
    {
        $this->iban = $iban;
        return $this;
    }

    /**
     * Returns the BIC of the receivers bank account.
     * Can be left empty for national payments (IBAN-only transaction)
     * @return string
     */
    public function getBic(): ?string
    {
        return $this->bic;
    }

    /**
     * Sets the BIC of the receivers bank account.
     * Can be left empty for national payments (IBAN-only transaction)
     * @param  string  $bic
     * @return BankAccountInfo
     */
    public function setBic(string $bic): BankAccountInfo
    {
        $this->bic = $bic;
        return $this;
    }

    /**
     * Returns the name of the receivers bank.
     * @return string
     */
    public function getBankName(): ?string
    {
        return $this->bank_name;
    }

    /**
     * Sets the name of the receivers bank.
     * @param  string  $bank_name
     * @return BankAccountInfo
     */
    public function setBankName(string $bank_name): BankAccountInfo
    {
        $this->bank_name = $bank_name;
        return $this;
    }

    /**
     * Returns the transaction reference that is used for the payment.
     * @return string
     */
    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * Sets the transaction reference that is used for the payment.
     * @param  string  $reference
     * @return BankAccountInfo
     */
    public function setReference(?string $reference): BankAccountInfo
    {
        $this->reference = $reference;
        return $this;
    }

    /**
     * Returns the whole address of the payment receiver as a single line (in the format "Street 1, 12345 City"
     * @return string
     */
    public function getAddress(): string
    {
        return $this->getStreet() . ', ' . $this->getZipCode() . ' ' . $this->getCity();
    }
}