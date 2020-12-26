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

    /**
     * @var Collection All departments that use this bank account
     * @ORM\OneToMany(targetEntity="App\Entity\Department", mappedBy="bank_account")
     */
    private $associated_departments;

    public function __construct()
    {
        $this->associated_departments = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getAccountOwner(): ?string
    {
        return $this->account_owner;
    }

    /**
     * @param  string  $account_owner
     * @return BankAccountInfo
     */
    public function setAccountOwner(string $account_owner): BankAccountInfo
    {
        $this->account_owner = $account_owner;
        return $this;
    }

    /**
     * @return string
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @param  string  $street
     * @return BankAccountInfo
     */
    public function setStreet(string $street): BankAccountInfo
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return string
     */
    public function getZipCode(): ?string
    {
        return $this->zip_code;
    }

    /**
     * @param  string  $zip_code
     * @return BankAccountInfo
     */
    public function setZipCode(string $zip_code): BankAccountInfo
    {
        $this->zip_code = $zip_code;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param  string  $city
     * @return BankAccountInfo
     */
    public function setCity(string $city): BankAccountInfo
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * @param  string  $iban
     * @return BankAccountInfo
     */
    public function setIban(string $iban): BankAccountInfo
    {
        $this->iban = $iban;
        return $this;
    }

    /**
     * @return string
     */
    public function getBic(): ?string
    {
        return $this->bic;
    }

    /**
     * @param  string  $bic
     * @return BankAccountInfo
     */
    public function setBic(string $bic): BankAccountInfo
    {
        $this->bic = $bic;
        return $this;
    }

    /**
     * @return string
     */
    public function getBankName(): ?string
    {
        return $this->bank_name;
    }

    /**
     * @param  string  $bank_name
     * @return BankAccountInfo
     */
    public function setBankName(string $bank_name): BankAccountInfo
    {
        $this->bank_name = $bank_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * @param  string  $reference
     * @return BankAccountInfo
     */
    public function setReference(?string $reference): BankAccountInfo
    {
        $this->reference = $reference;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->getStreet() . ', ' . $this->getZipCode() . ' ' . $this->getCity();
    }
}