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
 * This entity represents a request to pay money for something to a persons bank account.
 * The applicant (which can be some other person than the payment receiver) submits a payment order ("Zahlungsauftrag")
 * via the front end form. The finance officers of the department for which the payment order was submitted receive an
 * email with a verification link where they have to confirm the payment oder.
 * In the backend the back office officers can see all submitted payment orders. In the first step it is checked if a
 * payment order is factually correct, then it is exported (as SEPA-XML) to the online banking system. Another officer
 * then checks if the payment order is factually correct and then approve the payment in the online banking.
 *
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
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $exported = false;

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

    /**
     * @var bool Is FSR-Kom resolution
     * @ORM\Column(type="boolean")
     */
    private $fsr_kom_resolution = false;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="date", nullable=true)
     * @Assert\LessThanOrEqual(value="today", message="validator.resolution_must_not_be_in_future")
     * @Assert\GreaterThan(value="-3 years", message="validator.resolution_too_old")
     * @Assert\Expression("value !== null || (this.getDepartment().getType() != 'fsr' && this.isFsrKomResolution() === false)", message="validator.resolution_date.needed_for_fsr_fsrkom")
     */
    private $resolution_date = null;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     * @Assert\Email()
     */
    private $contact_email = "";

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
     * Returns the bank info associated with this payment order.
     * @see BankAccountInfo
     * @return BankAccountInfo
     */
    public function getBankInfo(): BankAccountInfo
    {
        return $this->bank_info;
    }

    /**
     * Returns the bank info associated with this payment order.
     * @see BankAccountInfo
     * @param  BankAccountInfo  $bank_info
     * @return PaymentOrder
     */
    public function setBankInfo(BankAccountInfo $bank_info): PaymentOrder
    {
        $this->bank_info = $bank_info;
        return $this;
    }

    /**
     * Returns the full name of person which has submitted this payment order.
     * @return string
     */
    public function getFullName(): string
    {
        if (empty($this->getFirstName())) {
            return $this->getLastName();
        }
        if (empty($this->getLastName())) {
            return $this->getFirstName();
        }
        return $this->getFirstName().' '.$this->getLastName();
    }

    /**
     * Returns the first name of the person which has submitted this payment order.
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->first_name;
    }

    /**
     * Sets the first name of the person which has submitted this payment order.
     * @param  string  $first_name
     * @return PaymentOrder
     */
    public function setFirstName(string $first_name): PaymentOrder
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * Returns the last name of the person which has submitted this payment order.
     * @return string
     */
    public function getLastName(): string
    {
        return $this->last_name;
    }

    /**
     * Sets the last name of the person which has submitted this payment order.
     * @param  string  $last_name
     * @return PaymentOrder
     */
    public function setLastName(string $last_name): PaymentOrder
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * Returns the department for which this payment order was submitted.
     * @return Department
     */
    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    /**
     * Returns the department for which this payment order was submitted.
     * @param  Department  $department
     * @return PaymentOrder
     */
    public function setDepartment(Department $department): PaymentOrder
    {
        $this->department = $department;
        return $this;
    }

    /**
     * Returns the name of the project which caused this payment order.
     * This value will be used in the bank reference value by default.
     * @return string
     */
    public function getProjectName(): string
    {
        return $this->project_name;
    }

    /**
     * Returns the name of the project which caused this payment order.
     * This value will be used in the bank reference value by default.
     * @param  string  $project_name
     * @return PaymentOrder
     */
    public function setProjectName(string $project_name): PaymentOrder
    {
        $this->project_name = $project_name;
        return $this;
    }

    /**
     * Returns the amount that should be paid in (euro) cents.
     * @return int
     */
    public function getAmount(): ?int
    {
        return $this->amount;
    }

    /**
     * Sets the amount that should be paid in (euro) cents.
     * @param  int  $amount
     * @return PaymentOrder
     */
    public function setAmount(int $amount): PaymentOrder
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Returns the amount formatted as euro string (with dot as decimal separator) in the form "123.45" (only 2 digits).
     * Returns null if no amount was set.
     * @return string|null
     */
    public function getAmountString(): ?string
    {
        if ($this->amount === null) {
            return null;
        }

        //%F (with big F) is important here, to always output with a dot
        return sprintf("%.2F", $this->amount / 100);
    }

    /**
     * Returns whether this payment order was checked as mathematically correct.
     * This means it was checked that the amount and data in this payment order matches the data on the invoice.
     * @return bool
     */
    public function isMathematicallyCorrect(): bool
    {
        return $this->mathematically_correct;
    }

    /**
     * Sets whether this payment order was checked as mathematically correct.
     * This means it was checked that the amount and data in this payment order matches the data on the invoice.
     * @param  bool  $mathematically_correct
     * @return PaymentOrder
     */
    public function setMathematicallyCorrect(bool $mathematically_correct): PaymentOrder
    {
        $this->mathematically_correct = $mathematically_correct;
        return $this;
    }

    /**
     * Returns whether this payment order was checked as factually correct.
     * This means it was checked that this payment is really needed. In our context it also means that an payment order
     * was payed out and is finished.
     * @return bool
     */
    public function isFactuallyCorrect(): bool
    {
        return $this->factually_correct;
    }

    /**
     * Sets whether this payment order was checked as factually correct.
     * This means it was checked that this payment is really needed. In our context it also means that an payment order
     * was payed out and is finished.
     * @param  bool  $factually_correct
     * @return PaymentOrder
     */
    public function setFactuallyCorrect(bool $factually_correct): PaymentOrder
    {
        $this->factually_correct = $factually_correct;
        return $this;
    }

    /**
     * Returns whether this payment order was exported as SEPA-XML (and imported in online banking).
     * This is automatically set when payment orders are exported.
     * @return bool
     */
    public function isExported(): bool
    {
        return $this->exported;
    }

    /**
     * Sets whether this payment order was exported as SEPA-XML (and imported in online banking).
     * This is automatically set when payment orders are exported.
     * @param  bool  $exported
     * @return PaymentOrder
     */
    public function setExported(bool $exported): PaymentOrder
    {
        $this->exported = $exported;
        return $this;
    }


    /**
     * Returns the comment associated with this payment order.
     * This can be HTML if changed in the backend.
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * Returns the comment associated with this payment order.
     * This can be HTML if changed in the backend.
     * @param  string  $comment
     * @return PaymentOrder
     */
    public function setComment(string $comment): PaymentOrder
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Returns the funding ID associated with this payment order (if it existing).
     * Returns an empty string if no funding ID is associated.
     * @return string
     */
    public function getFundingId(): string
    {
        return $this->funding_id;
    }

    /**
     * Sets the funding ID associated with this payment order (if it existing).
     * Set to an empty string if no funding ID is associated.
     * @param  string  $funding_id
     */
    public function setFundingId(string $funding_id): self
    {
        $this->funding_id = $funding_id;
        return $this;
    }

    /**
     * Return the HTTPFoundation File associated that contains the PDF version of this payment order.
     * @return \Symfony\Component\HttpFoundation\File\File|null
     */
    public function getPrintedFormFile(): ?\Symfony\Component\HttpFoundation\File\File
    {
        return $this->printed_form_file;
    }

    /**
     * Sets the HTTPFoundation File associated that contains the PDF version of this payment order.
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
     * Return the Vich File associated that contains the PDF version of this payment order.
     * @return File
     */
    public function getPrintedForm(): File
    {
        return $this->printed_form;
    }

    /**
     * Return the Vich File associated that contains the PDF version of this payment order.
     * @param  File  $printed_form
     * @return PaymentOrder
     */
    public function setPrintedForm(File $printed_form): PaymentOrder
    {
        $this->printed_form = $printed_form;
        return $this;
    }

    /**
     * Return the HTTPFoundation File associated that contains the invoice for this payment order.
     * @return \Symfony\Component\HttpFoundation\File\File|null
     */
    public function getReferencesFile(): ?\Symfony\Component\HttpFoundation\File\File
    {
        return $this->references_file;
    }

    /**
     * Sets the HTTPFoundation File associated that contains the invoice for this payment order.
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
     * Return the Vich File associated that contains the invoice for this payment order.
     * @return File
     */
    public function getReferences(): File
    {
        return $this->references;
    }

    /**
     * Sets the HTTPFoundation File associated that contains the invoice for this payment order.
     * @param  File  $references
     * @return PaymentOrder
     */
    public function setReferences(File $references): PaymentOrder
    {
        $this->references = $references;
        return $this;
    }

    /**
     * Returns the (hashed) token that can be used to access the confirmation1 page of this payment page.
     * This value be verified with the password_verify() function.
     * Can be null if no confirmation should be possible for this payment order.
     * @return string|null
     */
    public function getConfirm1Token(): ?string
    {
        return $this->confirm1_token;
    }

    /**
     * Returns the (hashed) token that can be used to access the confirmation1 page of this payment page.
     * This value be created with the password_hash() function.
     * Can be null if no confirmation should be possible for this payment order.
     * @param  string|null  $confirm1_token
     * @return PaymentOrder
     */
    public function setConfirm1Token(?string $confirm1_token): PaymentOrder
    {
        $this->confirm1_token = $confirm1_token;
        return $this;
    }

    /**
     * Returns the timestamp when the first confirmation for this payment order was submitted.
     * Returns null if this payment order is not confirmed (yet).
     * @return \DateTime|null
     */
    public function getConfirm1Timestamp(): ?\DateTime
    {
        return $this->confirm1_timestamp;
    }

    /**
     * Sets the timestamp when the first confirmation for this payment order was submitted.
     * Set to null if this payment order is not confirmed (yet).
     * @param  \DateTime|null  $confirm1_timestamp
     * @return PaymentOrder
     */
    public function setConfirm1Timestamp(?\DateTime $confirm1_timestamp): PaymentOrder
    {
        $this->confirm1_timestamp = $confirm1_timestamp;
        return $this;
    }

    /**
     *  Returns the (hashed) token that can be used to access the confirmation2 page of this payment page.
     * This value be verified with the password_verify() function.
     * Can be null if no confirmation should be possible for this payment order.
     * @return string|null
     */
    public function getConfirm2Token(): ?string
    {
        return $this->confirm2_token;
    }

    /**
     * Sets the (hashed) token that can be used to access the confirmation1 page of this payment page.
     * This value be created with the password_hash() function.
     * Can be null if no confirmation should be possible for this payment order.
     * @param  string|null  $confirm2_token
     * @return PaymentOrder
     */
    public function setConfirm2Token(?string $confirm2_token): PaymentOrder
    {
        $this->confirm2_token = $confirm2_token;
        return $this;
    }

    /**
     * Returns the timestamp when the second confirmation for this payment order was submitted.
     * Returns null if this payment order is not confirmed (yet).
     * @return \DateTime|null
     */
    public function getConfirm2Timestamp(): ?\DateTime
    {
        return $this->confirm2_timestamp;
    }

    /**
     * Sets the timestamp when the second confirmation for this payment order was submitted.
     * Set to null if this payment order is not confirmed (yet).
     * @param  \DateTime|null  $confirm2_timestamp
     * @return PaymentOrder
     */
    public function setConfirm2Timestamp(?\DateTime $confirm2_timestamp): PaymentOrder
    {
        $this->confirm2_timestamp = $confirm2_timestamp;
        return $this;
    }

    /**
     * Returns whether this payment order is confirmed (by both instances).
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->confirm1_timestamp !== null && $this->confirm2_timestamp !== null;
    }

    /**
     * Returns the email of the person which has submitted this payment order and which is used for answering questions.
     * @return string
     */
    public function getContactEmail(): string
    {
        return $this->contact_email;
    }

    /**
     * Sets the email of the person which has submitted this payment order and which is used for answering questions.
     * @param  string  $contact_email
     * @return PaymentOrder
     */
    public function setContactEmail(string $contact_email): PaymentOrder
    {
        $this->contact_email = $contact_email;
        return $this;
    }

    /**
     * Returns whether this is an payment order for an resolution of the FSR-Kom (these are handled differently)
     * @return bool
     */
    public function isFsrKomResolution(): bool
    {
        return $this->fsr_kom_resolution;
    }

    /**
     * Sets whether this is an payment order for an resolution of the FSR-Kom (these are handled differently)
     * @param  bool  $fsr_kom_resolution
     * @return PaymentOrder
     */
    public function setFsrKomResolution(bool $fsr_kom_resolution): PaymentOrder
    {
        $this->fsr_kom_resolution = $fsr_kom_resolution;
        return $this;
    }

    /**
     * Returns the date when the resolution that causes this payment order was passed.
     * This value is optional as not every payment order needs an resolution.
     * Only the date is shown for this DateTime.
     * @return \DateTime|null
     */
    public function getResolutionDate(): ?\DateTime
    {
        return $this->resolution_date;
    }

    /**
     * Sets the date when the resolution that causes this payment order was passed.
     * This value is optional as not every payment order needs an resolution.
     * Only the date is shown for this DateTime.
     * @param  \DateTime|null  $resolution_date
     * @return PaymentOrder
     */
    public function setResolutionDate(?\DateTime $resolution_date): PaymentOrder
    {
        $this->resolution_date = $resolution_date;
        return $this;
    }

    /**
     * Get the ID as string like ZA0005
     * @return string
     */
    public function getIDString(): string
    {
        return sprintf("ZA%04d", $this->getId());
    }
}
