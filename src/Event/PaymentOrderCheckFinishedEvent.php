<?php

declare(strict_types=1);


namespace App\Event;

use App\Entity\PaymentOrder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is dispatched when a payment order was fully checked (mathematically and factually).
 */
class PaymentOrderCheckFinishedEvent extends Event implements PaymentOrderEventInterface
{
    public const NAME = 'payment_order.check_finished';

    public function __construct(private readonly PaymentOrder $paymentOrder)
    {
    }

    public function getPaymentOrder(): PaymentOrder
    {
        return $this->paymentOrder;
    }
}