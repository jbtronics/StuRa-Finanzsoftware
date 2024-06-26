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

namespace App\EventSubscriber;

use App\Event\PaymentOrderSubmittedEvent;
use App\Services\EmailConfirmation\ConfirmationEmailSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class PaymentOrderSendConfirmationEmailsSubscriber implements EventSubscriberInterface
{
    public function __construct(private ConfirmationEmailSender $confirmationSender)
    {
    }

    public function sendConfirmationEmails(PaymentOrderSubmittedEvent $event): void
    {
        $paymentOrder = $event->getPaymentOrder();

        $this->confirmationSender->sendConfirmation1($paymentOrder);
        $this->confirmationSender->sendConfirmation2($paymentOrder);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentOrderSubmittedEvent::NAME => [
                ['sendConfirmationEmails', 5],
            ],
        ];
    }
}
