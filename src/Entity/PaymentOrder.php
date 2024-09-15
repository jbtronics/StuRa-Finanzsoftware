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
use App\Entity\Embeddable\Confirmation;
use App\Entity\Embeddable\PayeeInfo;
use App\Repository\PaymentOrderRepository;
use App\Validator\FSRNotBlocked;
use DateTime;
use Doctrine\DBAL\Types\Types;
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
 * @see \App\Tests\Entity\PaymentOrderTest
 */
#[ORM\Entity(repositoryClass: PaymentOrderRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table('payment_orders')]
#[Vich\Uploadable()]
class PaymentOrder implements DBElementInterface, TimestampedElementInterface, \Serializable
{
    use TimestampTrait;

    public const FUNDING_ID_REGEX = '/^(FA|M)-\d{3,4}-20\d{2}(_\d{2})?$/';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Embedded(class: PayeeInfo::class)]
    #[Assert\Valid]
    private PayeeInfo $bank_info;

    /**
     * @var string "Vorname"
     */
    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank]
    private string $first_name = '';

    /**
     * @var string "Nachname"
     */
    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank]
    private string $last_name = '';

    /**
     * @var Department|null "Struktur/Organisation"
     */
    #[ORM\ManyToOne(targetEntity: Department::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[FSRNotBlocked(groups: ['fsr_blocked'])]
    private ?Department $department = null;

    /**
     * @var string "Projektbezeichnung"
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 70, maxMessage: 'validator.project_name.too_long')]
    #[ORM\Column(type: Types::STRING)]
    private string $project_name = '';

    /**
     * @var int|null "Betrag" (in cents)
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Positive]
    private ?int $amount = null;

    /**
     * @var bool "mathematisch richtig"
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $mathematically_correct = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $exported = false;

    /**
     * @var bool "sachlich richtig"
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $factually_correct = false;

    #[ORM\Column(type: Types::TEXT)]
    private string $comment = '';

    /**
     * @var string "Mittelfreigabe / Finanzantrag"
     */
    #[ORM\Column(type: Types::STRING)]
    #[Assert\Regex(PaymentOrder::FUNDING_ID_REGEX)]
    private string $funding_id = '';

    /**
     * @var Confirmation The first confirmation for this payment order
     */
    #[ORM\Embedded(class: Confirmation::class)]
    #[Assert\Valid]
    private Confirmation $confirmation1;

    /**
     * @var Confirmation The second confirmation for this payment order. Depending on the department this may not be
     * required
     */
    #[ORM\Embedded(class: Confirmation::class)]
    #[Assert\Valid]
    private Confirmation $confirmation2;

    /**
     * @var int The number of confirmations required for this payment order. The number is determined by the department
     * and how many confirmations are required for the department.
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Range(min: 1, max: 2)]
    private int $requiredConfirmations = 2;

    /**
     * @var bool Is FSR-Kom resolution
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $fsr_kom_resolution = false;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\LessThanOrEqual(value: 'today', message: 'validator.resolution_must_not_be_in_future')]
    #[Assert\GreaterThan(value: '-3 years', message: 'validator.resolution_too_old')]
    #[Assert\Expression("value !== null || (this.getDepartment() !== null && this.getDepartment().getType() != 'fsr' && this.isFsrKomResolution() === false)", message: 'validator.resolution_date.needed_for_fsr_fsrkom')]
    private ?\DateTime $resolution_date = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Assert\Email]
    private string $contact_email = '';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $booking_date = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $references_exported = false;

    /*
     * Associated files
     */
    #[Assert\File(maxSize: '1024k', mimeTypes: ['application/pdf', 'application/x-pdf'], mimeTypesMessage: 'validator.upload_pdf')]
    #[Vich\UploadableField(mapping: 'payment_orders_form', fileNameProperty: 'printed_form.name', size: 'printed_form.size', mimeType: 'printed_form.mimeType', originalName: 'printed_form.originalName', dimensions: 'printed_form.dimensions')]
    private ?\Symfony\Component\HttpFoundation\File\File $printed_form_file = null;

    
    #[ORM\Embedded(class: \Vich\UploaderBundle\Entity\File::class)]
    private \Vich\UploaderBundle\Entity\File $printed_form;

    #[Assert\NotBlank(groups: ['frontend'])]
    #[Assert\File(maxSize: '10M', mimeTypes: ['application/pdf', 'application/x-pdf'], mimeTypesMessage: 'validator.upload_pdf')]
    #[Vich\UploadableField(mapping: 'payment_orders_references', fileNameProperty: 'references.name', size: 'references.size', mimeType: 'references.mimeType', originalName: 'references.originalName', dimensions: 'references.dimensions')]
    private ?\Symfony\Component\HttpFoundation\File\File $references_file = null;

    
    #[ORM\Embedded(class: \Vich\UploaderBundle\Entity\File::class)]
    private \Vich\UploaderBundle\Entity\File $references;

    public function __construct()
    {
        $this->bank_info = new PayeeInfo();

        $this->references = new File();
        $this->printed_form = new File();

        $this->confirmation1 = new Confirmation();
        $this->confirmation2 = new Confirmation();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the bank info associated with this payment order.
     *
     *@see PayeeInfo
     */
    public function getBankInfo(): PayeeInfo
    {
        return $this->bank_info;
    }

    /**
     * Returns the bank info associated with this payment order.
     *
     *@see PayeeInfo
     */
    public function setBankInfo(PayeeInfo $bank_info): PaymentOrder
    {
        $this->bank_info = $bank_info;

        return $this;
    }

    /**
     * Returns the full name of person which has submitted this payment order.
     */
    public function getFullName(): string
    {
        if ($this->getFirstName() === '' || $this->getFirstName() === '0') {
            return $this->getLastName();
        }
        if ($this->getLastName() === '' || $this->getLastName() === '0') {
            return $this->getFirstName();
        }

        return $this->getFirstName().' '.$this->getLastName();
    }

    /**
     * Returns the first name of the person which has submitted this payment order.
     */
    public function getFirstName(): string
    {
        return $this->first_name;
    }

    /**
     * Sets the first name of the person which has submitted this payment order.
     */
    public function setFirstName(string $first_name): PaymentOrder
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * Returns the last name of the person which has submitted this payment order.
     */
    public function getLastName(): string
    {
        return $this->last_name;
    }

    /**
     * Sets the last name of the person which has submitted this payment order.
     */
    public function setLastName(string $last_name): PaymentOrder
    {
        $this->last_name = $last_name;

        return $this;
    }

    /**
     * Returns the department for which this payment order was submitted.
     *
     * @return Department
     */
    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    /**
     * Returns the department for which this payment order was submitted.
     */
    public function setDepartment(Department $department): PaymentOrder
    {
        $this->department = $department;

        return $this;
    }

    /**
     * Returns the name of the project which caused this payment order.
     * This value will be used in the bank reference value by default.
     */
    public function getProjectName(): string
    {
        return $this->project_name;
    }

    /**
     * Returns the name of the project which caused this payment order.
     * This value will be used in the bank reference value by default.
     */
    public function setProjectName(string $project_name): PaymentOrder
    {
        $this->project_name = $project_name;

        return $this;
    }

    /**
     * Returns the amount that should be paid in (euro) cents.
     *
     * @return int
     */
    public function getAmount(): ?int
    {
        return $this->amount;
    }

    /**
     * Sets the amount that should be paid in (euro) cents.
     */
    public function setAmount(int $amount): PaymentOrder
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Returns the amount formatted as euro string (with dot as decimal separator) in the form "123.45" (only 2 digits).
     * Returns null if no amount was set.
     */
    public function getAmountString(): ?string
    {
        if (null === $this->amount) {
            return null;
        }

        //%F (with big F) is important here, to always output with a dot
        return sprintf('%.2F', $this->amount / 100);
    }

    /**
     * Returns whether this payment order was checked as mathematically correct.
     * This means it was checked that the amount and data in this payment order matches the data on the invoice.
     */
    public function isMathematicallyCorrect(): bool
    {
        return $this->mathematically_correct;
    }

    /**
     * Sets whether this payment order was checked as mathematically correct.
     * This means it was checked that the amount and data in this payment order matches the data on the invoice.
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
     */
    public function isFactuallyCorrect(): bool
    {
        return $this->factually_correct;
    }

    /**
     * Sets whether this payment order was checked as factually correct.
     * This means it was checked that this payment is really needed. In our context it also means that an payment order
     * was payed out and is finished.
     */
    public function setFactuallyCorrect(bool $factually_correct): PaymentOrder
    {
        $this->factually_correct = $factually_correct;

        //Update the status of booking date
        $this->booking_date = $factually_correct ? new \DateTime() : null;

        return $this;
    }

    /**
     * Returns whether this payment order was exported as SEPA-XML (and imported in online banking).
     * This is automatically set when payment orders are exported.
     */
    public function isExported(): bool
    {
        return $this->exported;
    }

    /**
     * Sets whether this payment order was exported as SEPA-XML (and imported in online banking).
     * This is automatically set when payment orders are exported.
     */
    public function setExported(bool $exported): PaymentOrder
    {
        $this->exported = $exported;

        return $this;
    }

    /**
     * Returns the comment associated with this payment order.
     * This can be HTML if changed in the backend.
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * Returns the comment associated with this payment order.
     * This can be HTML if changed in the backend.
     */
    public function setComment(string $comment): PaymentOrder
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Returns the funding ID associated with this payment order (if it existing).
     * Returns an empty string if no funding ID is associated.
     */
    public function getFundingId(): string
    {
        return $this->funding_id;
    }

    /**
     * Sets the funding ID associated with this payment order (if it existing).
     * Set to an empty string if no funding ID is associated.
     */
    public function setFundingId(string $funding_id): self
    {
        $this->funding_id = $funding_id;

        return $this;
    }

    /**
     * Return the HTTPFoundation File associated that contains the PDF version of this payment order.
     */
    public function getPrintedFormFile(): ?\Symfony\Component\HttpFoundation\File\File
    {
        return $this->printed_form_file;
    }

    /**
     * Sets the HTTPFoundation File associated that contains the PDF version of this payment order.
     */
    public function setPrintedFormFile(?\Symfony\Component\HttpFoundation\File\File $printed_form_file): PaymentOrder
    {
        $this->printed_form_file = $printed_form_file;

        if (null !== $printed_form_file) {
            /* It is required that at least one field changes if you are using doctrine
             otherwise the event listeners won't be called and the file is lost
             But we dont really want to change  the last time value, so just copy it and create a new reference
            so doctrine thinks something has changed, but practically everything looks the same */
            $this->last_modified = clone ($this->last_modified ?? new \DateTime());
        }

        return $this;
    }

    /**
     * Return the Vich File associated that contains the PDF version of this payment order.
     */
    public function getPrintedForm(): File
    {
        return $this->printed_form;
    }

    /**
     * Return the Vich File associated that contains the PDF version of this payment order.
     */
    public function setPrintedForm(File $printed_form): PaymentOrder
    {
        $this->printed_form = $printed_form;

        return $this;
    }

    /**
     * Return the HTTPFoundation File associated that contains the invoice for this payment order.
     */
    public function getReferencesFile(): ?\Symfony\Component\HttpFoundation\File\File
    {
        return $this->references_file;
    }

    /**
     * Sets the HTTPFoundation File associated that contains the invoice for this payment order.
     */
    public function setReferencesFile(?\Symfony\Component\HttpFoundation\File\File $references_file): PaymentOrder
    {
        $this->references_file = $references_file;

        if (null !== $references_file) {
            /* It is required that at least one field changes if you are using doctrine
             otherwise the event listeners won't be called and the file is lost
             But we dont really want to change  the last time value, so just copy it and create a new reference
            so doctrine thinks something has changed, but practically everything looks the same */
            $this->last_modified = clone ($this->last_modified ?? new \DateTime());
        }

        return $this;
    }

    /**
     * Return the Vich File associated that contains the invoice for this payment order.
     */
    public function getReferences(): File
    {
        return $this->references;
    }

    /**
     * Sets the HTTPFoundation File associated that contains the invoice for this payment order.
     */
    public function setReferences(File $references): PaymentOrder
    {
        $this->references = $references;

        return $this;
    }

    /**
     * Checks how many confirmations this payment order has.
     * This is between 0 and 2.
     * @return int
     */
    public function getNumberOfConfirmations(): int
    {
        $count = 0;
        if ($this->confirmation1->isConfirmed()) {
            ++$count;
        }
        if ($this->confirmation2->isConfirmed()) {
            ++$count;
        }

        return $count;
    }

    /**
     * Returns whether this payment order is confirmed (by both instances).
     */
    public function isConfirmed(): bool
    {
        //The payment order is confirmed, if we have enough confirmations
        return $this->getNumberOfConfirmations() >= $this->requiredConfirmations;
    }

    /**
     * Returns the email of the person which has submitted this payment order and which is used for answering questions.
     */
    public function getContactEmail(): string
    {
        return $this->contact_email;
    }

    /**
     * Sets the email of the person which has submitted this payment order and which is used for answering questions.
     */
    public function setContactEmail(string $contact_email): PaymentOrder
    {
        $this->contact_email = $contact_email;

        return $this;
    }

    /**
     * Returns whether this is an payment order for an resolution of the FSR-Kom (these are handled differently).
     */
    public function isFsrKomResolution(): bool
    {
        return $this->fsr_kom_resolution;
    }

    /**
     * Sets whether this is an payment order for an resolution of the FSR-Kom (these are handled differently).
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
     */
    public function getResolutionDate(): ?DateTime
    {
        return $this->resolution_date;
    }

    /**
     * Sets the date when the resolution that causes this payment order was passed.
     * This value is optional as not every payment order needs an resolution.
     * Only the date is shown for this DateTime.
     */
    public function setResolutionDate(?DateTime $resolution_date): PaymentOrder
    {
        $this->resolution_date = $resolution_date;

        return $this;
    }

    /**
     * Returns the datetime when this payment was booked in banking.
     * Returns null if payment_order was not booked yet.
     * The value is set automatically to now when the "factually_checked" field is set.
     */
    public function getBookingDate(): ?DateTime
    {
        return $this->booking_date;
    }

    /**
     * Manually set the datetime when this payment was booked in banking.
     * Set to null if payment_order was not booked yet.
     * The value is set automatically to now when the "factually_checked" field is set.
     */
    public function setBookingDate(?DateTime $booking_date): PaymentOrder
    {
        $this->booking_date = $booking_date;

        return $this;
    }

    /**
     * Returns whether the references for this payment order were already exported.
     */
    public function isReferencesExported(): bool
    {
        return $this->references_exported;
    }

    /**
     * Sets whether the references for this payment order were already exported.
     */
    public function setReferencesExported(bool $references_exported): PaymentOrder
    {
        $this->references_exported = $references_exported;

        return $this;
    }

    public function getConfirmation1(): Confirmation
    {
        return $this->confirmation1;
    }

    public function setConfirmation1(Confirmation $confirmation1): PaymentOrder
    {
        $this->confirmation1 = $confirmation1;
        return $this;
    }

    public function getConfirmation2(): Confirmation
    {
        return $this->confirmation2;
    }

    public function setConfirmation2(Confirmation $confirmation2): PaymentOrder
    {
        $this->confirmation2 = $confirmation2;
        return $this;
    }

    public function getRequiredConfirmations(): int
    {
        return $this->requiredConfirmations;
    }

    public function setRequiredConfirmations(int $requiredConfirmations): PaymentOrder
    {
        if ($requiredConfirmations < 1 || $requiredConfirmations > 2) {
            throw new \InvalidArgumentException('The number of required confirmations must be between 1 and 2');
        }

        $this->requiredConfirmations = $requiredConfirmations;
        return $this;
    }



    public function serialize()
    {
        return serialize($this->getId());
    }

    public function unserialize($serialized): void
    {
        $this->id = unserialize($serialized);
    }

    /**
     * Get the ID as string like ZA0005.
     */
    public function getIDString(): string
    {
        return sprintf('ZA%04d', $this->getId());
    }
}
