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
use App\Entity\Embeddable\Check;
use App\Entity\Embeddable\Confirmation;
use App\Entity\Embeddable\PayeeInfo;
use App\EntityListener\PaymentOrderChangedFieldsListener;
use App\Repository\PaymentOrderRepository;
use App\Serializer\MoneyNormalizer;
use App\Validator\FSRNotBlocked;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
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
#[Vich\Uploadable]
#[ORM\EntityListeners([PaymentOrderChangedFieldsListener::class])]
class PaymentOrder implements DBElementInterface, TimestampedElementInterface, \Serializable
{
    use TimestampTrait;

    /**
     * Legacy regex for the funding IDs. (e.g. FA-0001-2020)
     */
    public const FUNDING_ID_REGEX_LEGACY = '/^(FA|M)-\d{3,4}-20\d{2}(_\d{2})?$/';

    /**
     * Regex for new funding IDs of the StuRa or FSR-Kom (e.g. FA-0001-2020_01)
     */
    public const FUNDING_ID_STURA_FSRKOM = '/^(FA|M)-\d{3,4}-20\d{2}_\d{2}$/';

    /**
     * Regex for new funding IDs of the departments (e.g. M-PAF-0001-2024_25)
     */
    public const FUNDING_ID_DEPARTMENT = '/^(FA|M)-\w{3,5}-\d{3,4}-20\d{2}_\d{2}/';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups('csv_export')]
    #[SerializedName("ID")]
    private ?int $id = null;

    /*******************************************************************************************************************
     * Submitter info
     ******************************************************************************************************************/

    /**
     * @var string The name of the person, which has submitted this payment order
     */
    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank]
    #[Groups('csv_export')]
    #[SerializedName("Name Auftraggeber")]
    private string $submitter_name = '';

    /**
     * @var string The email of the personn, who has submitted this payment order
     */
    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Assert\Email]
    #[Groups('csv_export')]
    #[SerializedName("E-Mail Auftraggeber")]
    private string $submitter_email = '';

    /**
     * @var Department|null "Struktur/Organisation"
     */
    #[ORM\ManyToOne(targetEntity: Department::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[FSRNotBlocked(groups: ['fsr_blocked'])]
    #[Groups('csv_export')]
    #[SerializedName("Struktur")]
    private ?Department $department = null;

    /*******************************************************************************************************************
     * Mittelfreigabe Informations
     ******************************************************************************************************************/

    /**
     * @var string "Mittelfreigabe / Finanzantrag" Number for the submitting department
     */
    #[ORM\Column(type: Types::STRING)]
    #[Assert\AtLeastOneOf([
        new Assert\Regex(pattern: PaymentOrder::FUNDING_ID_DEPARTMENT),
        new Assert\Regex(pattern: PaymentOrder::FUNDING_ID_STURA_FSRKOM),
    ], groups: ['frontend'], message: 'validator.funding_id.invalid', includeInternalMessages: false)]
    #[Assert\AtLeastOneOf([
        new Assert\Regex(pattern: PaymentOrder::FUNDING_ID_DEPARTMENT),
        new Assert\Regex(pattern: PaymentOrder::FUNDING_ID_STURA_FSRKOM),
        new Assert\Regex(PaymentOrder::FUNDING_ID_REGEX_LEGACY),
    ], groups: ['backend'], includeInternalMessages: false)]
    #[Groups('csv_export')]
    #[SerializedName("Mittelfreigabe")]
    private string $funding_id = '';

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\LessThanOrEqual(value: 'today', message: 'validator.resolution_must_not_be_in_future')]
    #[Assert\GreaterThan(value: '-3 years', message: 'validator.resolution_too_old', groups: ['frontend'])]
    #[Assert\NotNull(groups: ['frontend'])]
    #[Assert\Expression("value !== null || (this.getDepartment() !== null && this.getDepartment().getType() != 'fsr' && this.isFsrKomResolution() === false)", message: 'validator.resolution_date.needed_for_fsr_fsrkom')]
    #[Groups('csv_export')]
    #[SerializedName("Beschlussdatum")]
    private ?\DateTime $resolution_date = null;

    /**
     * @var int|null "Betrag" (in cents). The amount sum that will be paid out.
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Positive]
    #[Groups('csv_export')]
    #[SerializedName("Betrag")]
    private ?int $amount = null;

    /**
     * @var string "Mittelfreigabe / Finanzantrag" ID of the supporting deparment (when the FSR-kom pays part of the amount).
     * This is optional.
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Assert\AtLeastOneOf([
        new Assert\Regex(pattern: PaymentOrder::FUNDING_ID_DEPARTMENT),
        new Assert\Regex(pattern: PaymentOrder::FUNDING_ID_STURA_FSRKOM),
    ], message: 'validator.funding_id.invalid', includeInternalMessages: false)]
    #[Assert\Expression("value === null || this.getSupportingAmount() !== null", message: 'validator.supporting_funding_id.needed_for_supporting_amount')]
    #[Groups('csv_export')]
    #[SerializedName("Unterstützende Mittelfreigabe")]
    private ?string $supporting_funding_id = null;

    /**
     * The day, when the supporting funding was decided
     * @var DateTime|null
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\LessThanOrEqual(value: 'today', message: 'validator.resolution_must_not_be_in_future')]
    #[Assert\GreaterThan(value: '-3 years', message: 'validator.resolution_too_old', groups: ['frontend'])]
    #[Groups('csv_export')]
    #[SerializedName("Unterstützung Beschlussdatum")]
    private ?\DateTime $supporting_funding_date = null;

    /**
     * @var int|null The amount that will be paid out by the supporting department (in cents).
     * This is required together with the supporting_funding_id.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(propertyPath: 'amount')]
    #[Assert\Expression("value === null || this.getSupportingFundingId() !== null", message: 'validator.supporting_amount.needed_for_supporting_funding_id')]
    #[Groups('csv_export')]
    #[SerializedName("Unterstützender Betrag")]
    private ?int $supporting_amount = null;

    /*******************************************************************************************************************
     * Verwendungszweck block
     ******************************************************************************************************************/
    /**
     * @var string "Projektbezeichnung"
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 70, maxMessage: 'validator.project_name.too_long')]
    #[ORM\Column(type: Types::STRING)]
    #[Groups('csv_export')]
    #[SerializedName("Projektname")]
    private string $project_name = '';

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Assert\Length(max: 23)]
    #[Groups('csv_export')]
    #[SerializedName("Rechnungsnummer")]
    private ?string $invoice_number = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Assert\Length(max: 23)]
    #[Groups('csv_export')]
    #[SerializedName("Kundennummer")]
    private ?string $customer_number = null;

    /*******************************************************************************************************************
     * Miscellaneous
     ******************************************************************************************************************/

    #[ORM\Embedded(class: PayeeInfo::class)]
    #[Assert\Valid]
    #[Groups('csv_export')]
    #[SerializedName("Zahlungsempfänger")]
    private PayeeInfo $bank_info;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups('csv_export')]
    #[SerializedName("Kommentar")]
    private string $comment = '';

    /**
     * @var bool Is FSR-Kom resolution
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups('csv_export')]
    #[SerializedName("FSR-Kom Beschluss?")]
    private bool $fsr_kom_resolution = false;

    /*******************************************************************************************************************
     * Files
     ******************************************************************************************************************/

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



    /******************************************************************************************************************
     * Confirmations and status infos
     *******************************************************************************************************************/

    /**
     * @var Check "mathematisch richtig"
     */
    #[ORM\Embedded(Check::class)]
    #[Assert\Valid]
    #[Groups('csv_export')]
    #[SerializedName("Rechnerische Prüfung")]
    private Check $mathematically_correct;

    /**
     * @var Check "sachlich richtig"
     */
    #[ORM\Embedded(Check::class)]
    #[Assert\Valid]
    #[Groups('csv_export')]
    #[SerializedName("Sachliche Prüfung")]
    private Check $factually_correct;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $exported = false;

    /**
     * @var Confirmation The first confirmation for this payment order
     */
    #[ORM\Embedded(class: Confirmation::class)]
    #[Assert\Valid]
    #[Groups('csv_export')]
    #[SerializedName("Bestätigung 1")]
    private Confirmation $confirmation1;

    /**
     * @var Confirmation The second confirmation for this payment order. Depending on the department this may not be
     * required
     */
    #[ORM\Embedded(class: Confirmation::class)]
    #[Assert\Valid]
    #[Groups('csv_export')]
    #[SerializedName("Bestätigung 2")]
    private Confirmation $confirmation2;

    /**
     * @var int The number of confirmations required for this payment order. The number is determined by the department
     * and how many confirmations are required for the department.
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Range(min: 1, max: 2)]
    #[Groups('csv_export')]
    #[SerializedName("Benötigte Bestätigungen")]
    private int $requiredConfirmations = 2;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups('csv_export')]
    #[SerializedName("Buchnungsdatum")]
    private ?\DateTime $booking_date = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups('csv_export')]
    #[SerializedName("Belege exportiert?")]
    private bool $references_exported = false;

    /******************************************************************************************************************
     * Misc
     *****************************************************************************************************************/

    /**
     * @var FieldChanges|null The changes that were made to this payment order (or null if old case, where no changes were tracked)
     */
    #[ORM\Column(type: 'field_changes', nullable: true, name: 'field_changes')]
    private ?FieldChanges $field_changes = null;

    /**
     * @var Collection<ConfirmationToken> The confirmation tokens that can be used to confirm this payment order
     */
    #[ORM\OneToMany(targetEntity: ConfirmationToken::class, mappedBy: 'paymentOrder', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $confirmationTokens;

    public function __construct()
    {
        $this->confirmationTokens = new \Doctrine\Common\Collections\ArrayCollection();

        $this->bank_info = new PayeeInfo();

        $this->references = new File();
        $this->printed_form = new File();

        $this->confirmation1 = new Confirmation();
        $this->confirmation2 = new Confirmation();

        $this->mathematically_correct = new Check();
        $this->factually_correct = new Check();
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

    public function getSubmitterName(): string
    {
        return $this->submitter_name;
    }

    public function setSubmitterName(string $submitter_name): PaymentOrder
    {
        $this->submitter_name = $submitter_name;
        return $this;
    }

    public function getSupportingFundingId(): ?string
    {
        return $this->supporting_funding_id;
    }

    public function setSupportingFundingId(?string $supporting_funding_id): PaymentOrder
    {
        $this->supporting_funding_id = $supporting_funding_id;
        return $this;
    }

    public function getSupportingFundingDate(): ?DateTime
    {
        return $this->supporting_funding_date;
    }

    public function setSupportingFundingDate(?DateTime $supporting_funding_date): PaymentOrder
    {
        $this->supporting_funding_date = $supporting_funding_date;
        return $this;
    }

    public function getSupportingAmount(): ?int
    {
        return $this->supporting_amount;
    }

    public function setSupportingAmount(?int $supporting_amount): PaymentOrder
    {
        $this->supporting_amount = $supporting_amount;
        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoice_number;
    }

    public function setInvoiceNumber(?string $invoice_number): PaymentOrder
    {
        $this->invoice_number = $invoice_number;
        return $this;
    }

    public function getCustomerNumber(): ?string
    {
        return $this->customer_number;
    }

    public function setCustomerNumber(?string $customer_number): PaymentOrder
    {
        $this->customer_number = $customer_number;
        return $this;
    }


    /**
     * Returns the department for which this payment order was submitted.
     *
     * @return Department|null
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
     * Returns the check object for "mathematically correct".
     * @return Check
     */
    public function getMathematicallyCorrect(): Check
    {
        return $this->mathematically_correct;
    }

    /**
     * Returns whether this payment order was checked as mathematically correct.
     * This means it was checked that the amount and data in this payment order matches the data on the invoice.
     */
    public function isMathematicallyCorrectChecked(): bool
    {
        return $this->mathematically_correct->isChecked();
    }


    /**
     * Returns the check object for "factually correct".
     * @return Check
     */
    public function getFactuallyCorrect(): Check
    {
        return $this->factually_correct;
    }

    /**
     * Returns whether this payment order was checked as factually correct.
     * This means it was checked that this payment is really needed. In our context it also means that an payment order
     * was payed out and is finished.
     */
    public function isFactuallyCorrectChecked(): bool
    {
        return $this->factually_correct->isChecked();
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
    public function getSubmitterEmail(): string
    {
        return $this->submitter_email;
    }

    /**
     * Sets the email of the person which has submitted this payment order and which is used for answering questions.
     */
    public function setSubmitterEmail(string $submitter_email): PaymentOrder
    {
        $this->submitter_email = $submitter_email;

        return $this;
    }

    /**
     * Returns whether this is a payment order for a resolution of the FSR-Kom (these are handled differently).
     */
    public function isFsrKomResolution(): bool
    {
        return $this->fsr_kom_resolution;
    }

    /**
     * Sets whether this is a payment order for a resolution of the FSR-Kom (these are handled differently).
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


    /**
     * Get the ID as string like ZA0005.
     */
    #[Groups('csv_export')]
    #[SerializedName("ID String")]
    public function getIDString(): string
    {
        return sprintf('ZA%04d', $this->getId());
    }

    public function getConfirmationTokens(): Collection
    {
        return $this->confirmationTokens;
    }

    public function addConfirmationToken(ConfirmationToken $confirmationToken): self
    {
        if (!$this->confirmationTokens->contains($confirmationToken)) {
            $this->confirmationTokens[] = $confirmationToken;
            $confirmationToken->setPaymentOrder($this);
        }

        return $this;
    }

    public function removeConfirmationToken(ConfirmationToken $confirmationToken): self
    {
        $this->confirmationTokens->removeElement($confirmationToken);

        return $this;
    }

    /**
     * Returns information about the changes that were made to this payment order.
     * The reference might change during the lifecycle of this payment order, so do not store the reference in a long-term
     * use variable.
     * @return FieldChanges
     */
    public function getFieldChanges(): FieldChanges
    {
        //If no field changes are set, create a new one
        if ($this->field_changes === null) {
            $this->field_changes = FieldChanges::new();
        }

        return $this->field_changes;
    }

    /**
     * Change the fields changes instance of this object. Should only be used by the entity listener.
     * @param  FieldChanges  $fieldChanges
     * @return $this
     */
    public function setFieldChanges(FieldChanges $fieldChanges): self
    {
        $this->field_changes = $fieldChanges;
        return $this;
    }

    public function serialize(): ?string
    {
        return serialize($this->getId());
    }

    public function unserialize($data): void
    {
        $this->id = unserialize($data, ['allowed_classes' => false]);
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
    }
}
