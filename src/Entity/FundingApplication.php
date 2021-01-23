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
use App\Entity\Embeddable\Address;
use App\Entity\Embeddable\FundingID;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Entity\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Repository\FundingApplicationRepository;

/**
 * @ORM\Entity(repositoryClass=FundingApplicationRepository::class)
 * @ORM\Table(name="funding_applications", uniqueConstraints={@ORM\UniqueConstraint(name="funding_id_idx", columns={"funding_id_external_funding", "funding_id_number", "funding_id_year_part"})})
 * @ORM\EntityListeners({"App\Doctrine\FundingIDGeneratorListener"})
 * @Vich\Uploadable
 * @ORM\HasLifecycleCallbacks()
 */
class FundingApplication implements DBElementInterface, TimestampedElementInterface
{
    use TimestampTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * The assigned funding ID of this application (e.g. M-123-21/22 or FA-123-21/22).
     * This value is filled by an entity listener before persist.
     * @ORM\Embedded(class="App\Entity\Embeddable\FundingID", columnPrefix="funding_id_")
     * @var FundingID|null
     */
    private $funding_id = null;

    /**
     * @var bool This is used to store that this is an external funding, until we fill in the funding ID in onPersist.
     */
    private $tmp_external_funding = false;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotBlank()
     * @var string
     */
    private $applicant_name = "";

    /**
     * @var string The email address of the applicant
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Email
     */
    private $applicant_email = "";

    /**
     * @var Department|null The organisation which apply for funding.
     * @ORM\ManyToOne(targetEntity="App\Entity\Department")
     * @Assert\NotNull(groups={"internal_funding_application"})
     * @Assert\IsNull(groups={"external_funding_application"})
     */
    private $applicant_department = null;

    /**
     * @var string|null The name of the external organsisation which applied for funding. Only needed for external funding.
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank(groups={"external_funding_application"})
     * @Assert\IsNull(groups={"internal_funding_application"})
     */
    private $applicant_organisation_name = null;

    /**
     * @var string|null The phone number of the applicant. Only used in external funding.
     * @ORM\Column(type="string", nullable=true)
     */
    private $applicant_phone = null;

    /**
     * @var Address The address of the external organisation. Only needed for external funding.
     * @ORM\Embedded("App\Entity\Embeddable\Address")
     * @Assert\Valid(groups={"external_funding_application"})
     */
    private $applicant_address = null;

    /**
     * The originally requested amount of funding in (euro)cents.
     * @Assert\GreaterThan(0)
     * @var int|null
     * @ORM\Column(type="integer", nullable=false)
     */
    private $requested_amount = null;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotBlank
     * @var string|null A short description for which the requested money is intended to use.
     */
    private $funding_intention = null;

    /**
     * @Vich\UploadableField(mapping="funding_application_explanation", fileNameProperty="explanation.name", size="explanation.size", mimeType="explanation.mimeType", originalName="explanation.originalName", dimensions="explanation.dimensions")
     * @var \Symfony\Component\HttpFoundation\File\File|null
     * @Assert\File(
     *     maxSize = "1024k",
     *     mimeTypes = {"application/pdf", "application/x-pdf"},
     *     mimeTypesMessage = "validator.upload_pdf"
     * )
     */
    private $explanation_file;

    /**
     * @ORM\Embedded(class="Vich\UploaderBundle\Entity\File")
     * @var File
     */
    private $explanation;

    /**
     * @Vich\UploadableField(mapping="funding_application_finance_plan", fileNameProperty="finance_plan.name", size="finance_plan.size", mimeType="finance_plan.mimeType", originalName="finance_plan.originalName", dimensions="finance_plan.dimensions")
     *
     * @var \Symfony\Component\HttpFoundation\File\File|null
     * @Assert\NotBlank(groups={"frontend"})
     * @Assert\File(
     *     maxSize = "10M",
     *     mimeTypes = {"application/pdf", "application/x-pdf"},
     *     mimeTypesMessage = "validator.upload_pdf"
     * )
     */
    private $finance_plan_file;

    /**
     * @ORM\Embedded(class="Vich\UploaderBundle\Entity\File")
     *
     * @var File
     */
    private $finance_plan;

    public function __construct()
    {
        $this->applicant_address = new Address();
        $this->explanation = new File();
        $this->finance_plan = new File();
    }

    /**
     * Returns the funding ID assigned to this funding ID (e.g. M-123-21/22 or FA-123-21/22).
     * This is the ID shown to users instead of our internal databse ID.
     * The ID is set in an EntityListener before persisting.
     * @return FundingID|null
     */
    public function getFundingId(): ?FundingID
    {
        return $this->funding_id;
    }

    /**
     * Sets the funding ID assigned to this funding ID (e.g. M-123-21/22 or FA-123-21/22).
     * This is the ID shown to users instead of our internal databse ID.
     * The ID is set in an EntityListener before persisting, you can not call this function after the ID was already set.
     * @param  FundingID  $funding_id
     * @return FundingApplication
     */
    public function setFundingId(FundingID $funding_id): FundingApplication
    {
        if ($this->funding_id !== null) {
            throw new \LogicException("You can not change the funding ID after it was set!");
        }

        $this->funding_id = $funding_id;
        return $this;
    }

    /**
     * Returns whether this is an application of funding of an external organisation.
     * @return bool
     */
    public function isExternalFunding(): bool
    {
        if ($this->funding_id) {
            return $this->funding_id->isExternalFunding();
        }
        return $this->tmp_external_funding;
    }

    /**
     * Sets whether this is an application of funding of an external organisation.
     * @param  bool  $external_funding
     * @return FundingApplication
     */
    public function setExternalFunding(bool $external_funding): FundingApplication
    {
        if ($this->funding_id !== null) {
            throw new \LogicException("You can not change the funding type after it was set!");
        }

        $this->tmp_external_funding = $external_funding;
        return $this;
    }

    /**
     * Returns the name of the person which submitted the application.
     * @return string
     */
    public function getApplicantName(): string
    {
        return $this->applicant_name;
    }

    /**
     * Sets the name of the person which submitted the application.
     * @param  string  $applicant_name
     * @return FundingApplication
     */
    public function setApplicantName(string $applicant_name): FundingApplication
    {
        $this->applicant_name = $applicant_name;
        return $this;
    }

    /**
     * Returns the email address of the person which submitted the application.
     * @return string
     */
    public function getApplicantEmail(): string
    {
        return $this->applicant_email;
    }

    /**
     * Sets the email address of the person which submitted the application.
     * @param  string  $applicant_email
     * @return FundingApplication
     */
    public function setApplicantEmail(string $applicant_email): FundingApplication
    {
        $this->applicant_email = $applicant_email;
        return $this;
    }

    /**
     * Returns the department for which the funding application is submitted.
     * Only used for internal funding applications. For external applications applicant_organisation_name is used and this function returns null.
     * @return Department|null
     */
    public function getApplicantDepartment(): ?Department
    {
        return $this->applicant_department;
    }

    /**
     * Sets the department for which the funding application is submitted.
     * Only used for internal funding applications. For external applications applicant_organisation_name is used and this value must be null.
     * @param  Department|null  $applicant_department
     * @return FundingApplication
     */
    public function setApplicantDepartment(?Department $applicant_department): FundingApplication
    {
        $this->applicant_department = $applicant_department;
        return $this;
    }

    /**
     * Returns the name of the external organisation which applied for funding.
     * Only used for external funding applications. For internal applications applicant_department is used and this value must be null.
     * @return string|null
     */
    public function getApplicantOrganisationName(): ?string
    {
        return $this->applicant_organisation_name;
    }

    /**
     * Sets the name of the external organisation which applied for funding.
     * Only used for external funding applications. For interal applications applicant_department is used and this function must return null.
     * @param  string|null  $applicant_organisation_name
     * @return FundingApplication
     */
    public function setApplicantOrganisationName(?string $applicant_organisation_name): FundingApplication
    {
        $this->applicant_organisation_name = $applicant_organisation_name;
        return $this;
    }

    /**
     * Returns the phone number where the applicant can be contacted. Only used for external funding, for internal funding it returns null.
     * @return string|null
     */
    public function getApplicantPhone(): ?string
    {
        return $this->applicant_phone;
    }

    /**
     * Sets the phone number where the applicant can be contacted. Only used for external funding, for internal funding it returns null.
     * @param  string|null  $applicant_phone
     * @return FundingApplication
     */
    public function setApplicantPhone(?string $applicant_phone): FundingApplication
    {
        $this->applicant_phone = $applicant_phone;
        return $this;
    }

    /**
     * Return the address of the external organisation. Only used for external funding applications, for internal applications all values are empty.
     * @return Address
     */
    public function getApplicantAddress(): ?Address
    {
        return $this->applicant_address;
    }

    /**
     * Sets the address of the external organisation. Only used for external funding applications, for internal applications all values are empty.
     * @param  Address  $applicant_address
     * @return FundingApplication
     */
    public function setApplicantAddress(?Address $applicant_address): FundingApplication
    {
        $this->applicant_address = $applicant_address;
        return $this;
    }

    /**
     * Returns the originally requested amount of funding in (euro)cents.
     * @return int|null
     */
    public function getRequestedAmount(): ?int
    {
        return $this->requested_amount;
    }

    /**
     * Returns the originally requested amount of funding in (euro)cents.
     * @param  int|null  $requested_amount
     * @return FundingApplication
     */
    public function setRequestedAmount(?int $requested_amount): FundingApplication
    {
        $this->requested_amount = $requested_amount;
        return $this;
    }

    /**
     * Returns a short description for which the funding will be used.
     * @return string|null
     */
    public function getFundingIntention(): ?string
    {
        return $this->funding_intention;
    }

    /**
     * Sets a short description for which the funding will be used.
     * @param  string|null  $funding_intention
     * @return FundingApplication
     */
    public function setFundingIntention(?string $funding_intention): FundingApplication
    {
        $this->funding_intention = $funding_intention;
        return $this;
    }

    /**
     * Returns the detailed explaination why this funding is needed as PDF file.
     * @return \Symfony\Component\HttpFoundation\File\File|null
     */
    public function getExplanationFile(): ?\Symfony\Component\HttpFoundation\File\File
    {
        return $this->explanation_file;
    }

    /**
     * Sets the detailed explaination why this funding is needed as PDF file.
     * @param  \Symfony\Component\HttpFoundation\File\File|null  $explanation_file
     * @return FundingApplication
     */
    public function setExplanationFile(?\Symfony\Component\HttpFoundation\File\File $explanation_file
    ): FundingApplication {
        if (null !== $explanation_file) {
            /* It is required that at least one field changes if you are using doctrine
             otherwise the event listeners won't be called and the file is lost
             But we dont really want to change  the last time value, so just copy it and create a new reference
            so doctrine thinks something has changed, but practically everything looks the same */
            $this->last_modified = clone ($this->last_modified ?? new \DateTime());
        }

        $this->explanation_file = $explanation_file;
        return $this;
    }

    /*
     * Returns the detailed explaination why this funding is needed as PDF file.
     * @return File
     */
    public function getExplanation(): File
    {
        return $this->explanation;
    }

    /**
     * Sets the detailed explaination why this funding is needed as PDF file.
     * @param  File  $explanation
     * @return FundingApplication
     */
    public function setExplanation(File $explanation): FundingApplication
    {
        $this->explanation = $explanation;
        return $this;
    }

    /**
     * Returns the finance plan for this funding application as PDF file.
     * @return \Symfony\Component\HttpFoundation\File\File|null
     */
    public function getFinancePlanFile(): ?\Symfony\Component\HttpFoundation\File\File
    {
        return $this->finance_plan_file;
    }

    /**
     * Sets the finance plan for this funding application as PDF file.
     * @param  \Symfony\Component\HttpFoundation\File\File|null  $finance_plan_file
     * @return FundingApplication
     */
    public function setFinancePlanFile(?\Symfony\Component\HttpFoundation\File\File $finance_plan_file
    ): FundingApplication {
        if (null !== $finance_plan_file) {
            /* It is required that at least one field changes if you are using doctrine
             otherwise the event listeners won't be called and the file is lost
             But we dont really want to change  the last time value, so just copy it and create a new reference
            so doctrine thinks something has changed, but practically everything looks the same */
            $this->last_modified = clone ($this->last_modified ?? new \DateTime());
        }

        $this->finance_plan_file = $finance_plan_file;
        return $this;
    }

    /**
     * Returns the finance plan for this funding application as PDF file.
     * @return File
     */
    public function getFinancePlan(): File
    {
        return $this->finance_plan;
    }

    /**
     * Sets the finance plan for this funding application as PDF file.
     * @param  File  $finance_plan
     * @return FundingApplication
     */
    public function setFinancePlan(File $finance_plan): FundingApplication
    {
        $this->finance_plan = $finance_plan;
        return $this;
    }

    /**
     * Returns the internal database ID for this funding application.
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}