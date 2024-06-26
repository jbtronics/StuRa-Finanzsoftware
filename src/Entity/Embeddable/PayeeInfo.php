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
 * This embeddable contains all information about the person which should receive a payment of a payment order.
 *
 * @see \App\Tests\Entity\Embeddable\PayeeInfoTest
 */
#[ORM\Embeddable]
class PayeeInfo
{
    
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string')]
    private string $account_owner = '';

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string')]
    private string $street = '';

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string')]
    private string $zip_code = '';

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string')]
    private string $city = '';

    #[ORM\Column(type: 'string')]
    #[Assert\Iban]
    private string $iban = '';

    #[ORM\Column(type: 'string')]
    #[Assert\Bic(ibanPropertyPath: 'iban')]
    private string $bic = '';

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string')]
    private string $bank_name = '';

    #[ORM\Column(type: 'string')]
    #[Assert\Length(max: '140')]
    private ?string $reference = '';

    public function __construct()
    {
    }

    /**
     * Returns the name of the account owner / payment receiver.
     *
     * @return string
     */
    public function getAccountOwner(): ?string
    {
        return $this->account_owner;
    }

    /**
     * Sets the name of account owner / payment receiver.
     */
    public function setAccountOwner(string $account_owner): PayeeInfo
    {
        $this->account_owner = $account_owner;

        return $this;
    }

    /**
     * Returns the street and house no. where the payment receiver lives.
     *
     * @return string
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * Sets the street and house no. where the payment receiver lives.
     */
    public function setStreet(string $street): PayeeInfo
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Returns the zip code where the payment receiver lives.
     *
     * @return string
     */
    public function getZipCode(): ?string
    {
        return $this->zip_code;
    }

    /**
     * Sets the zip code where the payment receiver lives.
     */
    public function setZipCode(string $zip_code): PayeeInfo
    {
        $this->zip_code = $zip_code;

        return $this;
    }

    /**
     * Returns the city name where the payment receiver lives.
     *
     * @return string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Sets the city name where the payment receiver lives.
     */
    public function setCity(string $city): PayeeInfo
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Returns the IBAN of the receivers bank account.
     * The IBAN is formatted with spaces after it was validated by IBAN constraint, so the returned value containes
     * spaces.
     *
     * @return string
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * Returns the IBAN of the receivers bank account without spaces.
     * This is useful for SEPA exporter and other places where a IBAN in a machine-readable form is needed.
     */
    public function getIbanWithoutSpaces(): string
    {
        return str_replace(' ', '', $this->getIban());
    }

    /**
     * Sets the IBAN of the receivers bank account.
     * The IBAN will be formatted with spaces after it was validated by IBAN constraint.
     */
    public function setIban(string $iban): PayeeInfo
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Returns the BIC of the receivers bank account.
     * Can be left empty for national payments (IBAN-only transaction).
     *
     * @return string
     */
    public function getBic(): ?string
    {
        return $this->bic;
    }

    /**
     * Sets the BIC of the receivers bank account.
     * Can be left empty for national payments (IBAN-only transaction).
     */
    public function setBic(string $bic): PayeeInfo
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * Returns the name of the receivers bank.
     *
     * @return string
     */
    public function getBankName(): ?string
    {
        return $this->bank_name;
    }

    /**
     * Sets the name of the receivers bank.
     */
    public function setBankName(string $bank_name): PayeeInfo
    {
        $this->bank_name = $bank_name;

        return $this;
    }

    /**
     * Returns the transaction reference that is used for the payment.
     *
     * @return string
     */
    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * Sets the transaction reference that is used for the payment.
     *
     * @param string $reference
     */
    public function setReference(?string $reference): PayeeInfo
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Returns the whole address of the payment receiver as a single line (in the format "Street 1, 12345 City".
     */
    public function getAddress(): string
    {
        return $this->getStreet().', '.$this->getZipCode().' '.$this->getCity();
    }
}
