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

namespace App\MessageHandler;

use App\Message\PaymentOrder\PaymentOrderDeletedNotification;
use App\Services\ReplyEmailDecisonMaker;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
final readonly class PaymentOrderDeletedNotificationHandler
{

    public function __construct(
        private MailerInterface $mailer,
        private ReplyEmailDecisonMaker $reply_decision_maker,
        private TranslatorInterface $translator
    )
    {
    }

    public function __invoke(PaymentOrderDeletedNotification $message): void
    {
        $paymentOrder = $message->getPaymentOrder();

        $email = new TemplatedEmail();
        $email->priority(Email::PRIORITY_HIGH);
        $reply_to_email = $this->reply_decision_maker->getReplyToMailForPaymentOrder($paymentOrder);
        $email->replyTo($reply_to_email);

        $email->subject(
            $this->translator->trans(
                'payment_order.deletion_email.subject',
                [
                    '%project%' => $paymentOrder->getProjectName(),
                ]
            ));

        $email->htmlTemplate('mails/deletion_notification.html.twig');
        $email->context([
            'payment_order' => $paymentOrder,
            'blame_user' => $message->getBlameUser(),
            'deleted_where' => $message->getDeletedWhere(),
        ]);

        //Send the email to the FSR officers and the HHV/FSB
        $email_addresses = array_merge(
            $paymentOrder->getDepartment()->getEmailHhv(),
            $paymentOrder->getDepartment()->getEmailTreasurer(),
            [$reply_to_email]
        );

        $email->addBcc(...$email_addresses);


        //Send email
        $this->mailer->send($email);
    }
}