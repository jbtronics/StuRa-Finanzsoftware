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

namespace App\Services\EmailConfirmation;


use App\Entity\PaymentOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfirmationEmailSender
{
    private $mailer;
    private $tokenGenerator;
    private $entityManager;
    private $translator;

    private $fsb_email;
    private $send_notifications;
    private $notifications_bcc;

    public function __construct(MailerInterface $mailer, ConfirmationTokenGenerator $tokenGenerator,
        EntityManagerInterface $entityManager, TranslatorInterface $translator,
        string $fsb_email, bool $send_notifications, array $notifications_bcc)
    {
        $this->mailer = $mailer;
        $this->tokenGenerator = $tokenGenerator;
        $this->entityManager = $entityManager;
        $this->translator = $translator;

        $this->fsb_email = $fsb_email;
        $this->send_notifications = $send_notifications;
        $this->notifications_bcc = $notifications_bcc;
    }

    public function sendConfirmation1(PaymentOrder $paymentOrder): void
    {
        $token = $this->tokenGenerator->getToken();
        $paymentOrder->setConfirm1Token($this->hash_token($token));
        $email = $paymentOrder->getDepartment()->getEmailHhv();
        //Dont send the confirmation email if no email is set, otherwise just confirm it
        if (!empty($email) && $this->send_notifications) {
            $this->sendConfirmation($paymentOrder, $email , $token, 1);
        } else {
            $paymentOrder->setConfirm1Timestamp(new \DateTime());
        }

        $this->entityManager->flush();
    }

    public function sendConfirmation2(PaymentOrder $paymentOrder): void
    {
        $token = $this->tokenGenerator->getToken();
        $paymentOrder->setConfirm2Token($this->hash_token($token));
        $email = $paymentOrder->getDepartment()->getEmailTreasurer();
        //Dont send the confirmation email if no email is set, otherwise just confirm it
        if (!empty($email) && $this->send_notifications) {
            $this->sendConfirmation($paymentOrder, $email , $token, 2);
        } else {
            $paymentOrder->setConfirm2Timestamp(new \DateTime());
        }
        $this->entityManager->flush();
    }

    private function sendConfirmation(PaymentOrder $paymentOrder, string $email_address, string $token, int $verification_number)
    {
        $email = new TemplatedEmail();
        $email->addTo($email_address);

        $email->priority(Email::PRIORITY_HIGH);
        $email->replyTo($this->fsb_email);

        $email->subject(
            $this->translator->trans(
                'payment_order.confirmation_email.subject',
                ['%project%' => $paymentOrder->getProjectName()]
            ));

        $email->htmlTemplate('mails/confirmation.html.twig');
        $email->context([
                            'payment_order' => $paymentOrder,
                            'token' => $token,
                            'verification_number' => $verification_number
                        ]);


        //Submit mail
        $this->mailer->send($email);
    }

    private function hash_token(string $token): string
    {
        return password_hash($token, PASSWORD_DEFAULT);
    }
}