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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class ManualConfirmationHelper
{
    private $security;
    private $notifications_risky;
    private $fsb_email;
    private $hhv_email;
    private $translator;
    private $mailer;

    public function __construct(Security $security, TranslatorInterface $translator, MailerInterface $mailer,
        array $notifications_risky, string $fsb_email, string $hhv_email)
    {
        $this->security = $security;
        $this->notifications_risky = $notifications_risky;
        $this->fsb_email = $fsb_email;
        $this->hhv_email = $hhv_email;
        $this->translator = $translator;

        $this->mailer = $mailer;
    }

    public function confirmManually(PaymentOrder $paymentOrder, string $reason): void
    {
        if ($paymentOrder->isConfirmed()) {
            throw new \RuntimeException('You can not manually confirm an already confirmed payment order!');
        }

        //Add a comment about the manual confirmation
        $tmp = $paymentOrder->getComment();
        //Add line breaks if comment is not empty.
        if(!empty($tmp)) {
            $tmp .= '<br><br>';
        }
        $tmp .= $this->generateComment($paymentOrder, $reason);
        $paymentOrder->setComment($tmp);

        //Send emails that payment order we manually confirmed
        $this->sendNotification($paymentOrder, $reason);

        //Do the confirmation process where it was not needed
        if($paymentOrder->getConfirm1Timestamp() === null) {
            $paymentOrder->setConfirm1Timestamp(new \DateTime());
        }
        if ($paymentOrder->getConfirm2Timestamp() === null) {
            $paymentOrder->setConfirm2Timestamp(new \DateTime());
        }
    }

    private function sendNotification(PaymentOrder $paymentOrder, string $reason): void
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

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('$user must be an User entity object!');
        }

        $email->htmlTemplate('mails/manual_confirmation.html.twig');
        $email->context([
            'payment_order' => $paymentOrder,
            'reason' => $reason,
            'user' => $user,
        ]);

        //Add confirmation 1 people
        $email->addTo(...$paymentOrder->getDepartment()->getEmailHhv());
        //Add confirmation 2 people
        $email->addTo(...$paymentOrder->getDepartment()->getEmailTreasurer());
        //Add risky notification people
        $email->addTo(...$this->notifications_risky);


        $this->mailer->send($email);
    }

    private function generateComment(PaymentOrder $paymentOrder, string $reason): string
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new \RuntimeException('$user must be an User entity object!');
        }

        $date = Carbon::now()->toDateTimeLocalString();

        return '<h4>Manuelle Bestätigung</h4>'
            . 'durch ' . $user->getFullName() . ' (' . $user->getUsername() . '), ' . $date . '<br>'
            . '<b>Begründung: </b>' . $reason;

    }
}