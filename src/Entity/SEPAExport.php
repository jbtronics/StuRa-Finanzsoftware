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
use App\Helpers\SEPAXML\SEPAXMLParser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Entity\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity()
 * @ORM\Table("sepa_exports")
 * @Vich\Uploadable()
 * @ORM\HasLifecycleCallbacks()
 */
class SEPAExport implements DBElementInterface, TimestampedElementInterface
{

    use TimestampTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var int|null The number of payments contained in this export
     * @ORM\Column(type="integer")
     */
    private $number_of_payments = null;

    /**
     * @var int|null The total sum of payments in this payment (in cents)
     * @ORM\Column(type="integer")
     */
    private $total_sum = null;

    /**
     * @var Collection|PaymentOrder[]
     * @ORM\ManyToMany(targetEntity="App\Entity\PaymentOrder")
     */
    private $associated_payment_orders;

    /**
     * @ORM\Embedded(class="Vich\UploaderBundle\Entity\File")
     *
     * @var File
     */
    private $xml;

    /**
     * @Vich\UploadableField(mapping="sepa_export_xml", fileNameProperty="references.name", size="references.size", mimeType="references.mimeType", originalName="references.originalName", dimensions="references.dimensions")
     *
     * @var \Symfony\Component\HttpFoundation\File\File|null
     * @Assert\NotBlank(groups={"frontend"})
     * @Assert\File(
     *     maxSize = "10M",
     *     mimeTypes = {"application/pdf", "application/x-pdf"},
     *     mimeTypesMessage = "validator.upload_pdf"
     * )
     */
    private $xml_file;

    /**
     * @var string|null The SEPA message id of the assiociated file.
     * @ORM\Column(type="text")
     */
    private $sepa_message_id = null;

    /**
     * @var string|null The BIC of the debtor
     * @ORM\Column(type="text")
     */
    private $initiator_bic = null;

    /**
     * @var \DateTime|null The datetime when this SEPA export was booked (or marked as booked) in banking. Null if it was not booked yet.
     * @ORM\Column(type="datetime")
     */
    private $booking_date = null;

    /**
     * @var string A text describing this SEPA export
     * @ORM\Column(type="text")
     */
    private $description = "";

    /**
     * @var string A comment for this SEPA export
     * @ORM\Column(type="text")
     */
    private $comment = "";

    /**
     * @var string|null The IBAN of the debtor
     * @ORM\Column(type="text")
     */
    private $initiator_iban = null;

    /**
     * The export group this SEPA export belongs to. Null if it belongs to no export group.
     * @ORM\Column(type="ulid", nullable=true)
     * @var null|Ulid
     */
    private $group_ulid = null;

    public function __construct()
    {
        $this->associated_payment_orders = new ArrayCollection();
        $this->xml = new File();

        $this->creation_date = new \DateTime();
        $this->last_modified = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the number of payments in this SEPA export
     * @return int
     */
    public function getNumberOfPayments(): ?int
    {
        return $this->number_of_payments;
    }

    /**
     * Returns the total sum of all payments in this SEPA export in cents
     * @return int
     */
    public function getTotalSum(): ?int
    {
        return $this->total_sum;
    }

    /**
     * Returns the payment orders which are associated to this SEPA export
     * @return PaymentOrder[]|Collection
     */
    public function getAssociatedPaymentOrders()
    {
        return $this->associated_payment_orders;
    }

    /**
     * Returns the associated XML file
     * @return File
     */
    public function getXml(): File
    {
        return $this->xml;
    }

    /**
     * Returns the associated XML file
     * @return \Symfony\Component\HttpFoundation\File\File|null
     */
    public function getXmlFile(): ?\Symfony\Component\HttpFoundation\File\File
    {
        return $this->xml_file;
    }

    /**
     * Returns the content of the associated XML file.
     * @throws \RuntimeException if no xml file was set yet.
     * @return string
     */
    public function getXMLContent(): string
    {
        if ($this->getXmlFile() === null) {
            throw new \RuntimeException("No XML file provided yet!");
        }

        return $this->xml_file->getContent();
    }

    /**
     * Returns the sepa message id associated with this file
     * @return string
     */
    public function getSepaMessageId(): ?string
    {
        return $this->sepa_message_id;
    }

    /**
     * Returns the BIC of the debitor
     * @return string
     */
    public function getInitiatorBic(): ?string
    {
        return $this->initiator_bic;
    }

    /**
     * Returns the IBAN of the debitor
     * @return string
     */
    public function getInitiatorIban(): ?string
    {
        return $this->initiator_iban;
    }

    /**
     * Returns the datetime, when this export was marked as booked. Null if it was not booked yet.
     * @return \DateTime|null
     */
    public function getBookingDate(): ?\DateTime
    {
        return $this->booking_date;
    }

    /**
     * Returns true, if this export was not booked yet.
     * @return bool
     */
    public function isOpen(): bool
    {
        return ($this->booking_date === null);
    }

    /**
     * Returns a short describtion for this export
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Returns a comment for this export
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * Sets the booking status. If set to true, the booking date is set to now.
     * @param  bool  $is_booked
     * @return $this
     */
    public function setIsBooked(bool $is_booked = true): self
    {
        if ($is_booked === false) {
            //Reset booking state
            $this->booking_date = null;
        } else {
            //Set booking date to now
            $this->booking_date = new \DateTime();
        }

        return $this;
    }

    /**
     * @param  File  $xml
     * @return SEPAExport
     */
    public function setXml(File $xml): SEPAExport
    {
        $this->xml = $xml;
        return $this;
    }

    /**
     * @param  \Symfony\Component\HttpFoundation\File\File  $xml_file
     * @return SEPAExport
     */
    public function setXmlFile(\Symfony\Component\HttpFoundation\File\File $xml_file): SEPAExport
    {
        //Check if $xml_file was changed
        if($this->xml_file === null || $xml_file->getPathname() !== $this->xml_file->getPathname()) {
            $this->xml_file = $xml_file;
            $this->updateFromFile();
        } else {
            $this->xml_file = $xml_file;
        }
        return $this;
    }

    /**
     * @param  string  $description
     * @return SEPAExport
     */
    public function setDescription(string $description): SEPAExport
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param  string  $comment
     * @return SEPAExport
     */
    public function setComment(string $comment): SEPAExport
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Update the total sum, number of payments, message id, debitor iban and bic from the associatated XML file
     * @return void
     */
    public function updateFromFile(): void
    {
        $data = SEPAXMLParser::parseFromFile($this->xml_file);

        $this->sepa_message_id = $data['msg_id'];
        $this->number_of_payments = $data['number_of_payments'];
        $this->total_sum = $data['total_sum'];
        $this->initiator_bic = $data['initiator_bic'];
        $this->initiator_iban = $data['initiator_iban'];
    }

    /**
     * Creates a new SEPAExport with the content of the given XML string.
     * A new temporary file is created with the content which is later saved in the proper folder during persisting to database.
     * @param  string  $xml_string The content of the the XML file
     * @param  string $original_filename The value which should be used in database entry original filename.
     * @return SEPAExport
     */
    public static function createFromXMLString(string $xml_string, string $original_filename): SEPAExport
    {
        $tmpfname = tempnam(sys_get_temp_dir(), 'stura_') . '.xml';
        file_put_contents($tmpfname, $xml_string);

        $export = new SEPAExport();
        $export->setXmlFile(new UploadedFile($tmpfname, $original_filename, 'application/xml'));

        return $export;
    }

    /**
     * Returns the ulid of the export group this export belongs to. Null if this export belongs to no export group.
     * @return Ulid|null
     */
    public function getGroupUlid(): ?Ulid
    {
        return $this->group_ulid;
    }

    /**
     * Sets the ulid of the export group this export belongs to. Set to null if this export belongs to no export group.
     * @param  Ulid|null  $group_ulid
     * @return SEPAExport
     */
    public function setGroupUlid(?Ulid $group_ulid): SEPAExport
    {
        $this->group_ulid = $group_ulid;
        return $this;
    }



}