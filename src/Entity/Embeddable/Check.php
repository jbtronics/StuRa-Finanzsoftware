<?php

declare(strict_types=1);


namespace App\Entity\Embeddable;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * This embeddable contains all information about a check (the checks done by the StuRa Finance members).
 */
#[ORM\Embeddable]
class Check
{
    #[Groups('csv_export')]
    #[SerializedName("Geprüft?")]
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $checked = false;

    /**
     * @var \DateTime|null The timestamp of the confirmation. Null if not confirmed, or if legacy data.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups('csv_export')]
    #[SerializedName("Prüfzeitpunkt")]
    private ?\DateTime $timestamp = null;

    /**
     * @var string|null The name of the person who confirmed the confirmation. Null if not confirmed
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups('csv_export')]
    #[SerializedName("Prüfer")]
    private ?string $confirmerName = null;

    /**
     * @var int|null The ID of the user who confirmed this check. Null if not confirmed (or if legacy data)
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups('csv_export')]
    #[SerializedName("Benutzer ID")]
    private ?int $confirmerID = null;

    /**
     * @var string|null An optional remark about the confirmation
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('csv_export')]
    #[SerializedName("Anmerkung")]
    private ?string $remark = null;

    public function isChecked(): bool
    {
        return $this->checked;
    }

    public function setChecked(bool $checked): Check
    {
        $this->checked = $checked;
        return $this;
    }

    public function getTimestamp(): ?\DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(?\DateTime $timestamp): Check
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getConfirmerName(): ?string
    {
        return $this->confirmerName;
    }

    public function setConfirmerName(?string $confirmerName): Check
    {
        $this->confirmerName = $confirmerName;
        return $this;
    }

    public function getConfirmerID(): ?int
    {
        return $this->confirmerID;
    }

    public function setConfirmerID(?int $confirmerID): Check
    {
        $this->confirmerID = $confirmerID;
        return $this;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): Check
    {
        $this->remark = $remark;
        return $this;
    }

}