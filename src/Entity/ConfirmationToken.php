<?php

declare(strict_types=1);


namespace App\Entity;

use App\Entity\Contracts\DBElementInterface;
use App\Entity\Contracts\TimestampedElementInterface;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * This entity describes a token, that a Confirmer can use to confirm a specific PaymentOrder
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ConfirmationToken implements DBElementInterface, TimestampedElementInterface
{

    use TimestampTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * The token in hashed form
     * @var string
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $hashedToken;

    #[ORM\ManyToOne(targetEntity: Confirmer::class, inversedBy: 'confirmationTokens')]
    private Confirmer $confirmer;

    #[ORM\ManyToOne(targetEntity: PaymentOrder::class, inversedBy: 'confirmationTokens')]
    private PaymentOrder $paymentOrder;

    public function __construct(Confirmer $confirmer, PaymentOrder $paymentOrder, string $hashedToken)
    {
        $this->confirmer = $confirmer;
        $this->paymentOrder = $paymentOrder;
        $this->hashedToken = $hashedToken;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHashedToken(): string
    {
        return $this->hashedToken;
    }

    public function setHashedToken(string $hashedToken): ConfirmationToken
    {
        $this->hashedToken = $hashedToken;
        return $this;
    }

    public function getConfirmer(): Confirmer
    {
        return $this->confirmer;
    }

    public function setConfirmer(Confirmer $confirmer): ConfirmationToken
    {
        $this->confirmer = $confirmer;
        return $this;
    }

    public function getPaymentOrder(): PaymentOrder
    {
        return $this->paymentOrder;
    }

    public function setPaymentOrder(PaymentOrder $paymentOrder): ConfirmationToken
    {
        $this->paymentOrder = $paymentOrder;
        return $this;
    }
}