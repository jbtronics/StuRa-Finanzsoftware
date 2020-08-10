<?php


namespace App\Event;


use App\Entity\PaymentOrder;
use Symfony\Contracts\EventDispatcher\Event;

final class PaymentOrderSubmittedEvent extends Event
{
    public const NAME = 'payment_order.submitted';

    private $payment_order;

    public function __construct(PaymentOrder $paymentOrder)
    {
        $this->payment_order = $paymentOrder;
    }

    public function getPaymentOrder(): PaymentOrder
    {
        return $this->payment_order;
    }
}