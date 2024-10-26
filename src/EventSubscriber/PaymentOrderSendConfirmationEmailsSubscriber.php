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

use App\Event\PaymentOrderConfirmedEvent;
use App\Event\PaymentOrderSubmittedEvent;
use App\Services\EmailConfirmation\ConfirmationEmailSender;
use App\Services\ReplyEmailDecisonMaker;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use function Symfony\Component\String\u;

final readonly class PaymentOrderSendConfirmationEmailsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ConfirmationEmailSender $confirmationSender,
        private readonly MailerInterface $mailer,
        private readonly ReplyEmailDecisonMaker $replyEmailDecisonMaker,
    )
    {
    }

    public function sendConfirmationEmails(PaymentOrderSubmittedEvent $event): void
    {
        $paymentOrder = $event->getPaymentOrder();

        $this->confirmationSender->sendAllConfirmationEmails($paymentOrder);
    }

    /**
     * Send the confirmed email to the confirmer persons
     * @param  PaymentOrderConfirmedEvent  $event
     * @return void
     */
    public function sendConfirmedEmail(PaymentOrderConfirmedEvent $event): void
    {
        $paymentOrder = $event->getPaymentOrder();

        $email = new TemplatedEmail();
        $email->priority(Email::PRIORITY_HIGH);
        $email->replyTo($this->replyEmailDecisonMaker->getReplyToMailForPaymentOrder($paymentOrder));
        $email->subject(sprintf('%s (%s) bestätigt', $paymentOrder->getIDString(), u($paymentOrder->getProjectName())->truncate(50)));

        $email->htmlTemplate('mails/confirmed_notification.html.twig');
        $email->context([
            'payment_order' => $paymentOrder,
        ]);

        //Add all confirmers for the payment order into the BCC
        foreach ($paymentOrder->getDepartment()->getConfirmers() as $confirmer) {
            $email->addBcc($confirmer->getEmail());
        }

        //Add the form as attachment
        $email->attachFromPath($paymentOrder->getPrintedFormFile()->getRealPath(), $paymentOrder->getIDString() . '.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentOrderSubmittedEvent::NAME => [
                ['sendConfirmationEmails', 5],
            ],
            PaymentOrderConfirmedEvent::NAME => [
                ['sendConfirmedEmail', 5],
            ],
        ];
    }
}
