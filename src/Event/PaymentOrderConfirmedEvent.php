<?php

declare(strict_types=1);


namespace App\Event;

use App\Entity\PaymentOrder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents the event that is triggered, if a payment order has been confirmed by all responsible persons,
 * and is now ready for the next steps.
 */
final class PaymentOrderConfirmedEvent extends Event implements PaymentOrderEventInterface
{
    public const NAME = 'payment_order.confirmed';

    public function __construct(private readonly PaymentOrder $paymentOrder)
    {
    }

    public function getPaymentOrder(): PaymentOrder
    {
        return $this->paymentOrder;
    }
}