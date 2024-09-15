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

use App\Entity\Embeddable\Confirmation;
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

        //Send emails that payment order we manually confirmed
        $this->sendNotification($paymentOrder, $reason, $user);

        //We always need at least one confirmation, so do this for the first confirmation
        $this->performConfirmationIfNeeded($paymentOrder->getConfirmation1(), $reason, $user);

        //If there is a second confirmation, do this for the second confirmation
        if ($paymentOrder->getRequiredConfirmations() > 1) {
            $this->performConfirmationIfNeeded($paymentOrder->getConfirmation2(), $reason, $user);
        }
    }

    private function performConfirmationIfNeeded(Confirmation $confirmation, string $reason, User $user): void
    {
        //If the confirmation is already confirmed we are finished
        if ($confirmation->isConfirmed()) {
            return;
        }

        //Confirm the confirmation
        $confirmation->setTimestamp(new \DateTime());
        $confirmation->setConfirmationOverriden(true);
        $confirmation->setRemark("Bestätigung durch StuRa-Finanzer: " . $reason);
        $confirmation->setConfirmerName($user->getFullName() . ' (StuRa-Finanzer)');
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

        //Add all department confirmation persons
        foreach($paymentOrder->getDepartment()->getConfirmers() as $person) {
            $email->addBcc($person->getEmail());
        }

        //Add risky notification people
        $email->addBcc(...$this->notifications_risky);

        $this->mailer->send($email);
    }
}
