<?php

namespace App\Entity;

use App\Entity\Contracts\DBElementInterface;
use App\Entity\Contracts\NamedElementInterface;
use App\Entity\Contracts\TimestampedElementInterface;
use App\Repository\BankAccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This entity describes an bank account that the StuRa has control over.
 * It can be associated with an department.
 *
 *
 * @see \App\Tests\Entity\BankAccountTest
 */
#[ORM\Entity(repositoryClass: BankAccountRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['name'])]
#[UniqueEntity(fields: ['iban'])]
#[ORM\Table('bank_accounts')]
class BankAccount implements DBElementInterface, NamedElementInterface, TimestampedElementInterface, \Stringable
{
    use TimestampTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Iban]
    private string $iban = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Bic(ibanPropertyPath: 'iban')]
    #[Assert\NotBlank]
    private string $bic = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $comment = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $account_name = '';

    /**
     * @var Collection
     */
    #[ORM\OneToMany(mappedBy: 'bank_account', targetEntity: Department::class)]
    private Collection $associated_departments;

    public function __construct()
    {
        $this->associated_departments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the name with which the bank account is referred in the system.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the name with which the bank account is referred in the system.
     * Must be unique for all bank accounts.
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the IBAN associated with this bank account and which will be used in XML exports.
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * Returns the IBAN of the bank account without spaces.
     * This is useful for SEPA exporter and other places where a IBAN in a machine-readable form is needed.
     */
    public function getIbanWithoutSpaces(): string
    {
        return str_replace(' ', '', $this->getIban());
    }

    /**
     * Sets the IBAN associated with this bank account and which will be used in XML exports.
     * Must be unique for all bank accounts.
     *
     * @return $this
     */
    public function setIban(string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Gets the BIC associated with this bank account and which will be used in XML exports.
     */
    public function getBic(): string
    {
        return $this->bic;
    }

    /**
     * Sets the BIC associated with this bank account and which will be used in XML exports.
     *
     * @return $this
     */
    public function setBic(string $bic): self
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * Return the name of the bank account that can be used in XML exports.
     * Can return a empty string.
     */
    public function getAccountName(): string
    {
        return $this->account_name;
    }

    /**
     * Sets the name of the bank account that can be used in XML exports.
     * Can be an empty string.
     *
     * @return $this
     */
    public function setAccountName(string $account_name): self
    {
        $this->account_name = $account_name;

        return $this;
    }

    /**
     * Returns the name that will be used in SEPA XML export for the "Account owner field".
     * This is Account name if defined, otherwise the normal name field is used.
     */
    public function getExportAccountName(): string
    {
        if (trim((string) $this->account_name) !== '') {
            return $this->account_name;
        }

        return $this->name;
    }

    /**
     * Return a comment that can be used to describe this bank account more detailed.
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * Return a comment that can be used to describe this bank account more detailed.
     *
     * @return $this
     */
    public function setComment(string $new_comment): BankAccount
    {
        $this->comment = $new_comment;

        return $this;
    }

    /**
     * A __toString() function which is used to generate a user-friendly representation of this object in drop-downs.
     */
    public function __toString(): string
    {
        return ($this->name ?? 'unknown').' ['.$this->iban.']';
    }
}
