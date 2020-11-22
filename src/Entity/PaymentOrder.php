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
use App\Entity\Contracts\TimestampedElementInterface;
use App\Entity\Embeddable\BankAccountInfo;
use App\Repository\PaymentOrderRepository;
use App\Validator\FSRNotBlocked;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Entity\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * This entity contains all the info of a PaymentOrder ("Zahlungsauftrag")
 * @ORM\Entity(repositoryClass=PaymentOrderRepository::class)
 * @ORM\Table("payment_orders")
 * @Vich\Uploadable()
 * @ORM\HasLifecycleCallbacks()
 */
class PaymentOrder implements DBElementInterface, TimestampedElementInterface
{
    use TimestampTrait;

    public const FUNDING_REGEX = '/^(FA|M)-\d{3}-20\d{2}$/';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var BankAccountInfo
     * @ORM\Embedded(class="App\Entity\Embeddable\BankAccountInfo")
     * @Assert\Valid()
     */
    private $bank_info;

    /**
     * @var string "Vorname"
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $first_name = "";

    /**
     * @var string "Nachname"
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $last_name = "";

    /**
     * @var Department "Struktur/Organisation"
     * @ORM\ManyToOne(targetEntity="App\Entity\Department")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull()
     * @FSRNotBlocked()
     */
    private $department;

    /**
     * @var string "Projektbezeichnung"
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $project_name = "";

    /**
     * @var int "Betrag"
     * @ORM\Column(type="integer")
     * @Assert\Positive()
     */
    private $amount = null;

    /**
     * @var bool "mathematisch richtig"
     * @ORM\Column(type="boolean")
     */
    private $mathematically_correct = false;

    /**
     * @var bool "sachlich richtig"
     * @ORM\Column(type="boolean")
     */
    private $factually_correct = false;

    /**
     * @ORM\Column(type="text")
     */
    private $comment = "";

    /**
     * @var string "Mittelfreigabe / Finanzantrag"
     * @ORM\Column(type="string")
     * @Assert\Regex(PaymentOrder::FUNDING_REGEX)
     */
    private $funding_id = "";

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $confirm1_token = null;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $confirm1_timestamp = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $confirm2_token = null;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $confirm2_timestamp = null;

    /*
     * Associated files
     */

    /**
     * @Vich\UploadableField(mapping="payment_orders_form", fileNameProperty="printed_form.name", size="printed_form.size", mimeType="printed_form.mimeType", originalName="printed_form.originalName", dimensions="printed_form.dimensions")
     * @var \Symfony\Component\HttpFoundation\File\File|null
     * @Assert\File(
     *     maxSize = "1024k",
     *     mimeTypes = {"application/pdf", "application/x-pdf"},
     *     mimeTypesMessage = "validator.upload_pdf"
     * )
     */
    private $printed_form_file;

    /**
     * @ORM\Embedded(class="Vich\UploaderBundle\Entity\File")
     *
     * @var File
     */
    private $printed_form;

    /**
     * @Vich\UploadableField(mapping="payment_orders_references", fileNameProperty="references.name", size="references.size", mimeType="references.mimeType", originalName="references.originalName", dimensions="references.dimensions")
     * @var \Symfony\Component\HttpFoundation\File\File|null
     *
     * @Assert\File(
     *     maxSize = "10M",
     *     mimeTypes = {"application/pdf", "application/x-pdf"},
     *     mimeTypesMessage = "validator.upload_pdf"
     * )
     */
    private $references_file;

    /**
     * @ORM\Embedded(class="Vich\UploaderBundle\Entity\File")
     *
     * @var File
     */
    private $references;

    public function __construct()
    {
        $this->bank_info = new BankAccountInfo();

        $this->references = new File();
        $this->printed_form = new File();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return BankAccountInfo
     */
    public function getBankInfo(): BankAccountInfo
    {
        return $this->bank_info;
    }

    /**
     * @param  BankAccountInfo  $bank_info
     * @return PaymentOrder
     */
    public function setBankInfo(BankAccountInfo $bank_info): PaymentOrder
    {
        $this->bank_info = $bank_info;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->first_name;
    }

    /**
     * @param  string  $first_name
     * @return PaymentOrder
     */
    public function setFirstName(string $first_name): PaymentOrder
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->last_name;
    }

    /**
     * @param  string  $last_name
     * @return PaymentOrder
     */
    public function setLastName(string $last_name): PaymentOrder
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * @return Department
     */
    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    /**
     * @param  Department  $department
     * @return PaymentOrder
     */
    public function setDepartment(Department $department): PaymentOrder
    {
        $this->department = $department;
        return $this;
    }

    /**
     * @return string
     */
    public function getProjectName(): string
    {
        return $this->project_name;
    }

    /**
     * @param  string  $project_name
     * @return PaymentOrder
     */
    public function setProjectName(string $project_name): PaymentOrder
    {
        $this->project_name = $project_name;
        return $this;
    }

    /**
     * Returns the requested amount of money in cents.
     * @return int
     */
    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function getAmountString(): ?string
    {
        if ($this->amount === null) {
            return null;
        }

        //%F (with big F) is important here, to always output with a dot
        return sprintf("%.2F", $this->amount / 100);
    }

    /**
     * @param  int  $amount
     * @return PaymentOrder
     */
    public function setAmount(int $amount): PaymentOrder
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMathematicallyCorrect(): bool
    {
        return $this->mathematically_correct;
    }

    /**
     * @param  bool  $mathematically_correct
     * @return PaymentOrder
     */
    public function setMathematicallyCorrect(bool $mathematically_correct): PaymentOrder
    {
        $this->mathematically_correct = $mathematically_correct;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFactuallyCorrect(): bool
    {
        return $this->factually_correct;
    }

    /**
     * @param  bool  $factually_correct
     * @return PaymentOrder
     */
    public function setFactuallyCorrect(bool $factually_correct): PaymentOrder
    {
        $this->factually_correct = $factually_correct;
        return $this;
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
     * @return PaymentOrder
     */
    public function setComment(string $comment): PaymentOrder
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return string
     */
    public function getFundingId(): string
    {
        return $this->funding_id;
    }

    /**
     * @param  string  $funding_id
     */
    public function setFundingId(string $funding_id): self
    {
        $this->funding_id = $funding_id;
        return $this;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\File\File|null
     */
    public function getPrintedFormFile(): ?\Symfony\Component\HttpFoundation\File\File
    {
        return $this->printed_form_file;
    }

    /**
     * @param  \Symfony\Component\HttpFoundation\File\File|null  $printed_form_file
     * @return PaymentOrder
     */
    public function setPrintedFormFile(?\Symfony\Component\HttpFoundation\File\File $printed_form_file): PaymentOrder
    {
        $this->printed_form_file = $printed_form_file;

        if (null !== $printed_form_file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updateTimestamps();
        }
        return $this;
    }

    /**
     * @return File
     */
    public function getPrintedForm(): File
    {
        return $this->printed_form;
    }

    /**
     * @param  \Symfony\Component\HttpFoundation\File\File  $printed_form
     * @return PaymentOrder
     */
    public function setPrintedForm(File $printed_form): PaymentOrder
    {
        $this->printed_form = $printed_form;
        return $this;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\File\File|null
     */
    public function getReferencesFile(): ?\Symfony\Component\HttpFoundation\File\File
    {
        return $this->references_file;
    }

    /**
     * @param  \Symfony\Component\HttpFoundation\File\File|null  $references_file
     * @return PaymentOrder
     */
    public function setReferencesFile(?\Symfony\Component\HttpFoundation\File\File $references_file): PaymentOrder
    {
        $this->references_file = $references_file;

        if (null !== $references_file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updateTimestamps();
        }
        return $this;
    }

    /**
     * @return File
     */
    public function getReferences(): File
    {
        return $this->references;
    }

    /**
     * @param  File  $references
     * @return PaymentOrder
     */
    public function setReferences(File $references): PaymentOrder
    {
        $this->references = $references;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getConfirm1Token(): ?string
    {
        return $this->confirm1_token;
    }

    /**
     * @param  string|null  $confirm1_token
     * @return PaymentOrder
     */
    public function setConfirm1Token(?string $confirm1_token): PaymentOrder
    {
        $this->confirm1_token = $confirm1_token;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getConfirm1Timestamp(): ?\DateTime
    {
        return $this->confirm1_timestamp;
    }

    /**
     * @param  \DateTime|null  $confirm1_timestamp
     * @return PaymentOrder
     */
    public function setConfirm1Timestamp(?\DateTime $confirm1_timestamp): PaymentOrder
    {
        $this->confirm1_timestamp = $confirm1_timestamp;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getConfirm2Token(): ?string
    {
        return $this->confirm2_token;
    }

    /**
     * @param  string|null  $confirm2_token
     * @return PaymentOrder
     */
    public function setConfirm2Token(?string $confirm2_token): PaymentOrder
    {
        $this->confirm2_token = $confirm2_token;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getConfirm2Timestamp(): ?\DateTime
    {
        return $this->confirm2_timestamp;
    }

    /**
     * @param  \DateTime|null  $confirm2_timestamp
     * @return PaymentOrder
     */
    public function setConfirm2Timestamp(?\DateTime $confirm2_timestamp): PaymentOrder
    {
        $this->confirm2_timestamp = $confirm2_timestamp;
        return $this;
    }



}
