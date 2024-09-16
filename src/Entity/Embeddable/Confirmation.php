<?php

declare(strict_types=1);


namespace App\Entity\Embeddable;
use App\Entity\ConfirmationToken;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * This embeddable contains all information about a confirmation
 */
#[ORM\Embeddable]
class Confirmation
{

    /**
     * @var \DateTime|null The timestamp of the confirmation. Null if not confirmed.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $timestamp = null;

    /**
     * @var string|null The name of the person who confirmed the confirmation. Null if not confirmed
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $confirmerName = null;

    /**
     * @var int|null The ID of the ConfirmationToken used to confirm this confirmation. Null if not confirmed (or if legacy data)
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $confirmationTokenID = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $confirmerID = null;

    /**
     * @var bool Whether the confirmation was overridden by an StuRa Finance member
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $confirmationOverriden = false;

    /**
     * @var string|null An optional remark about the confirmation
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remark = null;

    public function isConfirmed(): bool
    {
        //It is confirmed if the confirmation timestamp is set
        return $this->timestamp !== null;
    }

    public function getTimestamp(): ?\DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(?\DateTime $timestamp): Confirmation
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getConfirmerName(): ?string
    {
        return $this->confirmerName;
    }

    public function setConfirmerName(?string $confirmerName): Confirmation
    {
        $this->confirmerName = $confirmerName;
        return $this;
    }

    public function getConfirmationTokenID(): ?int
    {
        return $this->confirmationTokenID;
    }

    public function setConfirmationTokenID(?ConfirmationToken $confirmation): Confirmation
    {
        $this->confirmationTokenID = $confirmation?->getId();
        return $this;
    }

    public function getConfirmerID(): ?int
    {
        return $this->confirmerID;
    }

    public function setConfirmerID(?int $confirmerID): Confirmation
    {
        $this->confirmerID = $confirmerID;
        return $this;
    }



    public function isConfirmationOverriden(): bool
    {
        return $this->confirmationOverriden;
    }

    public function setConfirmationOverriden(bool $confirmationOverriden): Confirmation
    {
        $this->confirmationOverriden = $confirmationOverriden;
        return $this;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): Confirmation
    {
        $this->remark = $remark;
        return $this;
    }
}