<?php

declare(strict_types=1);


namespace App\Event;

use App\Entity\PaymentOrder;

interface PaymentOrderEventInterface
{
    public function getPaymentOrder(): PaymentOrder;
}