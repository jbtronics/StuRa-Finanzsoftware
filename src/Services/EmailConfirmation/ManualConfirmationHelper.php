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
use App\Entity\User;
use Carbon\Carbon;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @see \App\Tests\Services\EmailConfirmation\ManualConfirmationHelperTest
 */
final readonly class ManualConfirmationHelper
{
    private array $notifications_risky;

    public function __construct(
        private Security $security,
        private TranslatorInterface $translator,
        private MailerInterface $mailer,
        array $notifications_risky,
        private string $fsb_email,
        private string $hhv_email
    )
    {
        $this->notifications_risky = array_filter($notifications_risky);
    }

    /**
     * Confirm the given PaymentOrder manually. The user, datetime and reason for this is logged.
     * Generates an comment, sends an email to confirmation people and confirm the payment order.
     * The DB is not flushed, so you have to do this outside.
     *
     * @param User|null $user Specify the user that should be shown in email/comment. If null the current user is used.
     */
    public function confirmManually(PaymentOrder $paymentOrder, string $reason, ?User $user = null): void
    {
        if ($paymentOrder->isConfirmed()) {
            throw new \RuntimeException('You can not manually confirm an already confirmed payment order!');
        }

        if (null === $user) {
            if (!$this->security->getUser() instanceof User) {
                throw new \RuntimeException('$user must be an User entity object!');
            }
            $user = $this->security->getUser();
        }

        //Add a comment about the manual confirmation
        $tmp = $paymentOrder->getComment();
        //Add line breaks if comment is not empty.
        if ($tmp !== '' && $tmp !== '0') {
            $tmp .= '<br><br>';
        }
        $tmp .= $this->generateComment($paymentOrder, $reason, $user);
        $paymentOrder->setComment($tmp);

        //Send emails that payment order we manually confirmed
        $this->sendNotification($paymentOrder, $reason, $user);

        //Do the confirmation process where it was not needed
        if (null === $paymentOrder->getConfirm1Timestamp()) {
            $paymentOrder->setConfirm1Timestamp(new \DateTime());
        }
        if (null === $paymentOrder->getConfirm2Timestamp()) {
            $paymentOrder->setConfirm2Timestamp(new \DateTime());
        }
    }

    private function sendNotification(PaymentOrder $paymentOrder, string $reason, User $user): void
    {
        //We can not continue if the payment order is not serialized / has an ID (as we cannot generate an URL for it)
        if (null === $paymentOrder->getId()) {
            throw new \RuntimeException('$paymentOrder must be serialized / have an ID so than an confirmation URL can be generated!');
        }

        $email = new TemplatedEmail();

        $email->priority(Email::PRIORITY_HIGHEST);
        $email->replyTo($paymentOrder->getDepartment()->isFSR() ? $this->fsb_email : $this->hhv_email);

        $email->subject(
            $this->translator->trans(
                'payment_order.manual_confirmation.email.subject',
                [
                    '%project%' => $paymentOrder->getProjectName(),
                ]
            ));

        $email->htmlTemplate('mails/manual_confirmation.html.twig');
        $email->context([
            'payment_order' => $paymentOrder,
            'reason' => $reason,
            'user' => $user,
        ]);

        //Add confirmation 1 people
        $email->addBcc(...$paymentOrder->getDepartment()->getEmailHhv());
        //Add confirmation 2 people
        $email->addBcc(...$paymentOrder->getDepartment()->getEmailTreasurer());

        //Add risky notification people
        $email->addBcc(...$this->notifications_risky);

        $this->mailer->send($email);
    }

    private function generateComment(PaymentOrder $paymentOrder, string $reason, User $user): string
    {
        $date = Carbon::now()->toDateTimeLocalString();

        return '<h4>Manuelle Bestätigung</h4>'
            .'durch '.$user->getFullName().' ('.$user->getUsername().'), '.$date.'<br>'
            .'<b>Begründung: </b>'.$reason;
    }
}
