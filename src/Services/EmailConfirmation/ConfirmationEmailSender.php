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

use App\Entity\ConfirmationToken;
use App\Entity\Confirmer;
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
    )
    {
    }

    /**
     * Generate a confirmationToken and send the confirmation email to the given confirmer.
     * The confirmer must be an allowed confirmer for the given payment order.
     * @param  PaymentOrder  $paymentOrder
     * @param  Confirmer  $confirmer
     * @return void
     * @throws TransportExceptionInterface
     */
    public function generateAndSendConfirmationEmail(PaymentOrder $paymentOrder, Confirmer $confirmer): void
    {
        //Ensure that the confirmer is allowed to confirm the payment order
        if (!$this->isAllowedConfirmer($confirmer, $paymentOrder)) {
            throw new \LogicException('The given confirmer is not allowed to confirm the given payment order!');
        }

        //Check if there already is a confirmation token for this confirmer
        foreach ($paymentOrder->getConfirmationTokens() as $confirmationToken) {
            if ($confirmationToken->getConfirmer() === $confirmer) {
                //Then remove the old token
                $this->entityManager->remove($confirmationToken);
                $paymentOrder->removeConfirmationToken($confirmationToken);
            }
        }

        $token = $this->tokenGenerator->getToken();

        $confirmationToken = new ConfirmationToken($confirmer, $paymentOrder, $this->hash_token($token));
        $paymentOrder->addConfirmationToken($confirmationToken);

        //Persist the confirmationToken
        $this->entityManager->persist($confirmationToken);
        $this->entityManager->flush();

        //Send the confirmation email
        $this->sendConfirmationEmail($paymentOrder, $confirmer, $token);

    }

    /**
     * Generate and send out confirmation emails to all confirmers of this payment order.
     * @param  PaymentOrder  $paymentOrder
     * @return void
     * @throws TransportExceptionInterface
     */
    public function sendAllConfirmationEmails(PaymentOrder $paymentOrder): void
    {
        if ($paymentOrder->getDepartment() === null) {
            throw new InvalidArgumentException('The department of the payment order must be set!');
        }

        //Send a confirmation email, to all confirmers that are allowed to confirm the payment order
        foreach ($paymentOrder->getDepartment()->getConfirmers() as $confirmer) {
            $this->generateAndSendConfirmationEmail($paymentOrder, $confirmer);
        }
    }

    /**
     * Clear existing confirmation tokens and send all confirmation emails again.
     */
    public function resendConfirmations(PaymentOrder $paymentOrder): void
    {
        //Clear all existing tokens
        foreach ($paymentOrder->getConfirmationTokens() as $confirmationToken) {
            $this->entityManager->remove($confirmationToken);
            $paymentOrder->removeConfirmationToken($confirmationToken);
        }

        //And send all confirmation emails again
        $this->sendAllConfirmationEmails($paymentOrder);
    }

    /**
     * Checks if the given confirmer is allowed to confirm the given payment order.
     * @param  Confirmer  $confirmer
     * @param  PaymentOrder  $paymentOrder
     * @return bool
     */
    private function isAllowedConfirmer(Confirmer $confirmer, PaymentOrder $paymentOrder): bool
    {
        //An confirmer is allowed, if he is set as responsible in the department of the payment order
        if ($paymentOrder->getDepartment() === null) {
            throw new InvalidArgumentException('The department of the payment order must be set!');
        }

        return $paymentOrder->getDepartment()->getConfirmers()->contains($confirmer);
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
            $this->sendConfirmationEmail($paymentOrder, $email, $token, 1);
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
            $this->sendConfirmationEmail($paymentOrder, $email, $token, 2);
        } else {
            $paymentOrder->setConfirm2Timestamp(new DateTime());
        }
        $this->entityManager->flush();
    }

    /**
     * Sents a confirmation email for the given payment order for a plaintext token.
     *
     * @param PaymentOrder $paymentOrder        The paymentOrder for which the email should be generated
     * @param Confirmer    $confirmer           The mail addresses that should be added as BCC
     * @param string       $token               The plaintext token to access confirmation page.
     *
     * @throws TransportExceptionInterface
     */
    private function sendConfirmationEmail(PaymentOrder $paymentOrder, Confirmer $confirmer, string $token): void
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
            'confirmer' => $confirmer,
        ]);

        //If the email address contains a comma, we split it and add each part as BCC (this is just for backward compatibility, with migrated confirmers)
        if (strpos($confirmer->getEmail(), ',') !== false) {
            $email->addBcc(...explode(',', $confirmer->getEmail()));
        } else { //Otherwise we just add the email address as recepient, as this is only one email address
            $email->addTo($confirmer->getEmail());
        }

        $this->mailer->send($email);
    }



    private function hash_token(string $token): string
    {
        return password_hash($token, PASSWORD_DEFAULT);
    }
}
