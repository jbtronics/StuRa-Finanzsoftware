<?php

namespace App\Entity;

use App\Entity\Contracts\DBElementInterface;
use App\Entity\Contracts\NamedElementInterface;
use App\Entity\Contracts\TimestampedElementInterface;
use App\Repository\BankAccountRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=BankAccountRepository::class)
 * @ORM\Table("bank_accounts")
 * @ORM\HasLifecycleCallbacks()
 *
 * @UniqueEntity(fields={"name"})
 * @UniqueEntity(fields={"iban"})
 */
class BankAccount implements DBElementInterface, NamedElementInterface, TimestampedElementInterface
{
    use TimestampTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Iban()
     */
    private $iban;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Bic(ibanPropertyPath="iban")
     */
    private $bic;

    /**
     * @ORM\Column(type="text")
     */
    private $comment = "";

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $account_name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the IBAN associated with this bank account.
     * @return string|null
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Gets the BIC associated with this bank account.
     * @return string|null
     */
    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(string $bic): self
    {
        $this->bic = $bic;

        return $this;
    }

    public function getAccountName(): ?string
    {
        return $this->account_name;
    }

    public function setAccountName(string $account_name): self
    {
        $this->account_name = $account_name;

        return $this;
    }

    /**
     * Returns the name that will be used in SEPA XML export for the "Account owner field".
     * This is Account name if defined, otherwise the normal name field is used.
     * @return string
     */
    public function getExportAccountName(): string
    {
        if(!empty($this->account_name)) {
            return $this->account_name;
        }

        return $this->name;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(?string $new_comment): BankAccount
    {
        $this->comment = $new_comment;
        return $this;
    }

    public function __toString(): string
    {
        return ($this->name ?? 'unknown') . ' [' . $this->iban . ']';
    }
}
