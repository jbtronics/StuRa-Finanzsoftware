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
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This service is responsible for sending the confirmation emails for a payment_order.
 * @see \App\Tests\Services\EmailConfirmation\ConfirmationEmailSenderTest
 */
final readonly class ConfirmationEmailSender
{
    public function __construct(
        private MailerInterface $mailer,
        private ConfirmationTokenGenerator $tokenGenerator,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private string $fsb_email,
        private string $hhv_email,
        private bool $send_notifications,
        private array $notifications_bcc
    )
    {
    }

    /**
     * Send the confirmation email to the first verification person for the given payment_order.
     * Email addresses are taken from department (and are added as BCC)
     * A token is generated, send via email and saved in hashed form in the payment order.
     * Calling this function will flush database.
     * If no applicable emails are found (or email notifications are disabled) the payment order will be confirmed and
     * no email is sent.
     */
    public function sendConfirmation1(PaymentOrder $paymentOrder): void
    {
        $token = $this->tokenGenerator->getToken();
        $paymentOrder->setConfirm1Token($this->hash_token($token));
        $email = $paymentOrder->getDepartment()
            ->getEmailHhv();
        //Dont send the confirmation email if no email is set, otherwise just confirm it
        if ($email !== [] && $this->send_notifications) {
            $this->sendConfirmation($paymentOrder, $email, $token, 1);
        } else {
            $paymentOrder->setConfirm1Timestamp(new DateTime());
        }

        $this->entityManager->flush();
    }

    /**
     * Send the confirmation email to the second verification person for the given payment_order.
     * Email addresses are taken from department (and are added as BCC)
     * A token is generated, send via email and saved in hashed form in the payment order.
     * Calling this function will flush database.
     * If no applicable emails are found (or email notifications are disabled) the payment order will be confirmed and
     * no email is sent.
     */
    public function sendConfirmation2(PaymentOrder $paymentOrder): void
    {
        $token = $this->tokenGenerator->getToken();
        $paymentOrder->setConfirm2Token($this->hash_token($token));
        $email = $paymentOrder->getDepartment()
            ->getEmailTreasurer();
        //Dont send the confirmation email if no email is set, otherwise just confirm it
        if ($email !== [] && $this->send_notifications) {
            $this->sendConfirmation($paymentOrder, $email, $token, 2);
        } else {
            $paymentOrder->setConfirm2Timestamp(new DateTime());
        }
        $this->entityManager->flush();
    }

    /**
     * Sents a confirmation email for the given payment order for a plaintext token.
     *
     * @param PaymentOrder $paymentOrder        The paymentOrder for which the email should be generated
     * @param string[]     $email_addresses     The mail addresses that should be added as BCC
     * @param string       $token               The plaintext token to access confirmation page.
     * @param int          $verification_number The verification step (1 or 2)
     *
     * @throws TransportExceptionInterface
     */
    private function sendConfirmation(PaymentOrder $paymentOrder, array $email_addresses, string $token, int $verification_number): void
    {
        //We can not continue if the payment order is not serialized / has an ID (as we cannot generate an URL for it)
        if (null === $paymentOrder->getId()) {
            throw new InvalidArgumentException('$paymentOrder must be serialized / have an ID so than an confirmation URL can be generated!');
        }

        $email = new TemplatedEmail();

        $email->priority(Email::PRIORITY_HIGH);
        $email->replyTo($paymentOrder->getDepartment()->isFSR() ? $this->fsb_email : $this->hhv_email);

        $email->subject(
            $this->translator->trans(
                'payment_order.confirmation_email.subject',
                [
                    '%project%' => $paymentOrder->getProjectName(),
                ]
            ));

        $email->htmlTemplate('mails/confirmation.html.twig');
        $email->context([
            'payment_order' => $paymentOrder,
            'token' => $token,
            'verification_number' => $verification_number,
        ]);

        $email->addBcc(...$email_addresses);
        $this->mailer->send($email);
    }

    /**
     * Resend all confirmation emails for cases where a confirmation is missing.
     * If some part is already confirmed this confirmation is not sent again.
     * If a confirmation is missing a new token will be generated and sent via email.
     */
    public function resendConfirmations(PaymentOrder $paymentOrder): void
    {
        //Resend emails that not already were confirmed
        if (null === $paymentOrder->getConfirm1Timestamp()) {
            $this->sendConfirmation1($paymentOrder);
        }

        if (null === $paymentOrder->getConfirm2Timestamp()) {
            $this->sendConfirmation2($paymentOrder);
        }
    }

    private function hash_token(string $token): string
    {
        return password_hash($token, PASSWORD_DEFAULT);
    }
}
