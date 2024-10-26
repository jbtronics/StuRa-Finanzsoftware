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

use App\Audit\UserProvider;
use App\Event\PaymentOrderSubmittedEvent;
use App\Services\PDF\PaymentOrderPDFGenerator;
use App\Services\ReplyEmailDecisonMaker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This subscriber send notification emails to the responsible people if a payment order is submitted (and the event was
 * triggered).
 */
final class PaymentOrderNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly TranslatorInterface $translator,
        private readonly ReplyEmailDecisonMaker $replyEmailDecisonMaker,
        private readonly bool $send_notifications,
        private array $notifications_bcc
    )
    {
    }

    public function sendUserEmail(PaymentOrderSubmittedEvent $event): void
    {
        //Do nothing if notifications are disabled
        if (!$this->send_notifications) {
            return;
        }

        $payment_order = $event->getPaymentOrder();
        if (null === $payment_order->getDepartment() || $payment_order->getDepartment()->getContactEmails() === []) {
            return;
        }
        $department = $payment_order->getDepartment();

        $email = new TemplatedEmail();

        if ($this->notifications_bcc !== [] && null !== $this->notifications_bcc[0]) {
            $email->addBcc(...$this->notifications_bcc);
        }

        $email->replyTo($this->replyEmailDecisonMaker->getReplyToMailForPaymentOrder($payment_order));

        $email->priority(Email::PRIORITY_HIGH);
        $email->subject($this->translator->trans(
            'payment_order.notification_user.subject',
            [
                '%project%' => $payment_order->getProjectName(),
            ]
        ));

        $email->htmlTemplate('mails/user_notification.html.twig');
        $email->context([
            'payment_order' => $payment_order,
        ]);

        $email->addBcc(...$department->getContactEmails());
        $this->mailer->send($email);
    }



    public static function getSubscribedEvents(): array
    {
        return [
            PaymentOrderSubmittedEvent::NAME => [
                ['sendUserEmail', 0],
            ],
        ];
    }
}
