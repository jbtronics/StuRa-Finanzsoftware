<?php


namespace App\Entity\Embeddable;

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
    private $account_owner;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $street;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $zip_code;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $city;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\Iban()
     */
    private $iban;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\Bic(ibanPropertyPath="iban")
     */
    private $bic;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $bank_name;

    /**
     * @var string
     * @ORM\Column("string")
     */
    private $reference = "";

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
}