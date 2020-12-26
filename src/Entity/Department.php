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

use App\Entity\Contracts\DBElementInterface;
use App\Entity\Contracts\NamedElementInterface;
use App\Entity\Contracts\TimestampedElementInterface;
use App\Repository\DepartmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A Department represents a structural unit like "FSR Physik" or "Referat für Inneres"
 * @ORM\Entity(repositoryClass=DepartmentRepository::class)
 * @ORM\Table("departments")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"name"})
 */
class Department implements DBElementInterface, NamedElementInterface, TimestampedElementInterface
{
    public const ALLOWED_TYPES = ["fsr", "section", "misc"];

    use TimestampTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name = "";

    /**
     * @ORM\Column(type="string")
     * @Assert\Choice(choices=Department::ALLOWED_TYPES)
     * @var string|null
     */
    private $type = "fsr";

    /**
     * @var bool If an FSR is blocked it can not submit new payment orders
     * @ORM\Column(type="boolean")
     */
    private $blocked = false;

    /**
     * @ORM\Column(type="text")
     */
    private $comment = "";

    /**
     * @var string[]
     * @ORM\Column(type="json")
     * @Assert\Unique()
     * @Assert\All({
     *     @Assert\Email()
     * })
     */
    private $contact_emails = [];

    /**
     * @var BankAccount|null
     * @ORM\ManyToOne(targetEntity="App\Entity\BankAccount", inversedBy="associated_departments")
     * @ORM\JoinColumn(name="bank_account_id", referencedColumnName="id", nullable=true)
     */
    private $bank_account = null;

    /**
     * @var string[]
     * @ORM\Column(type="simple_array", nullable=true)
     * @Assert\Unique()
     * @Assert\Expression("!(value === null || value === []) || this.gettype() !== 'fsr'", message="validator.fsr_email_must_not_be_empty")
     * @Assert\All({
     *     @Assert\Email(),
     *     @Assert\Expression("(value == null || value == '') || value not in this.getEmailTreasurer()", message="validator.fsr_emails_must_not_be_the_same")
     * })
     */
    private $email_hhv = [];

    /**
     * @var string[]
     * @ORM\Column(type="simple_array", nullable=true)
     * @Assert\Unique()
     * @Assert\Expression("!(value === null || value === []) || this.gettype() !== 'fsr'", message="validator.fsr_email_must_not_be_empty")
     * @Assert\All({
     *     @Assert\Email()
     * })
     */
    private $email_treasurer = [];

    /**
     * Returns the type of this department (whether it is an FSR, an section or something else)
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     *
     * @param  string  $type
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Checks if this department is blocked. If it is blocked it can not create new PaymentOrders...
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    /**
     * @param  bool  $is_blocked
     */
    public function setBlocked(bool $is_blocked): self
    {
        $this->blocked = $is_blocked;
        return $this;
    }

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

    public function __toString()
    {
        return $this->getName() ?? 'unknown';
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param  string  $comment
     * @return Department
     */
    public function setComment(string $comment): Department
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getContactEmails(): array
    {
        //Handle empty fields from older migrations
        if ($this->contact_emails === null) {
            return [];
        }

        return $this->contact_emails;
    }

    /**
     * @param  string[]  $contact_emails
     * @return $this
     */
    public function setContactEmails(array $contact_emails): self
    {
        $this->contact_emails = $contact_emails;
        return $this;
    }

    /**
     * @return BankAccount|null
     */
    public function getBankAccount(): ?BankAccount
    {
        return $this->bank_account;
    }

    /**
     * @param  BankAccount|null  $bank_account
     * @return Department
     */
    public function setBankAccount(?BankAccount $bank_account): Department
    {
        $this->bank_account = $bank_account;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getEmailHhv(): array
    {
        //Handle empty fields from older migrations
        if ($this->email_hhv === null) {
            return [];
        }
        return $this->email_hhv;
    }

    /**
     * @param  array  $email_hhv
     * @return Department
     */
    public function setEmailHhv(array $email_hhv): Department
    {
        $this->email_hhv = $email_hhv;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getEmailTreasurer(): array
    {
        //Handle empty fields from older migrations
        if ($this->email_treasurer === null) {
            return [];
        }
        return $this->email_treasurer;
    }

    /**
     * @param  string[]  $email_treasurer
     * @return Department
     */
    public function setEmailTreasurer(array $email_treasurer): Department
    {
        $this->email_treasurer = $email_treasurer;
        return $this;
    }




}
