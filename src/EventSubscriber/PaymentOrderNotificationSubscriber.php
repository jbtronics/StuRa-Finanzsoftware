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
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PaymentOrderNotificationSubscriber implements EventSubscriberInterface
{
    private $mailer;
    private $translator;
    private $fsb_email;
    private $send_notifications;
    private $notifications_bcc;

    public function __construct(MailerInterface $mailer, TranslatorInterface $translator, string $fsb_email,
        bool $send_notifications, array $notifications_bcc)
    {
        $this->mailer = $mailer;
        $this->fsb_email = $fsb_email;
        $this->translator = $translator;

        $this->send_notifications = $send_notifications;
        $this->notifications_bcc = $notifications_bcc;
    }

    public function sendUserEmail(PaymentOrderSubmittedEvent $event): void
    {
        //Do nothing if notifications are disabled
        if(!$this->send_notifications) {
            return;
        }

        $payment_order = $event->getPaymentOrder();
        if ($payment_order->getDepartment() === null || empty($payment_order->getDepartment()->getContactEmails())) {
            return;
        }
        $department = $payment_order->getDepartment();

        $email = new TemplatedEmail();
        $email->addTo(...$department->getContactEmails());

        if(!empty($this->notifications_bcc) && $this->notifications_bcc[0] !== null) {
            $email->addBcc(...$this->notifications_bcc);
        }

        $email->replyTo($this->fsb_email);

        $email->priority(Email::PRIORITY_HIGH);
        $email->subject($this->translator->trans(
            'payment_order.notification_user.subject',
            ['%project%' => $payment_order->getProjectName()]
        ));

        $email->htmlTemplate('mails/user_notification.html.twig');
        $email->context([
                            'payment_order' => $payment_order
                        ]);


        //Submit mail
        $this->mailer->send($email);

    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentOrderSubmittedEvent::NAME => [
                'sendUserEmail'
            ]
        ];
    }
}