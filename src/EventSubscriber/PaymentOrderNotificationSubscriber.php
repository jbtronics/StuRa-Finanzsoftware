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
    private $mailer;
    private $translator;
    private $paymentOrderPDFGenerator;
    private $entityManager;
    private $fsb_email;
    private $hhv_email;
    private $send_notifications;
    private $notifications_bcc;
    private $userProvider;

    public function __construct(MailerInterface $mailer, TranslatorInterface $translator,
        PaymentOrderPDFGenerator $paymentOrderPDFGenerator, EntityManagerInterface $entityManager,
        UserProvider $userProvider, string $fsb_email, string $hhv_email,
        bool $send_notifications, array $notifications_bcc)
    {
        $this->mailer = $mailer;
        $this->fsb_email = $fsb_email;
        $this->hhv_email = $hhv_email;
        $this->translator = $translator;

        $this->send_notifications = $send_notifications;
        $this->notifications_bcc = $notifications_bcc;

        $this->paymentOrderPDFGenerator = $paymentOrderPDFGenerator;
        $this->entityManager = $entityManager;
        $this->userProvider = $userProvider;
    }

    public function sendUserEmail(PaymentOrderSubmittedEvent $event): void
    {
        //Do nothing if notifications are disabled
        if (!$this->send_notifications) {
            return;
        }

        $payment_order = $event->getPaymentOrder();
        if (null === $payment_order->getDepartment() || empty($payment_order->getDepartment()->getContactEmails())) {
            return;
        }
        $department = $payment_order->getDepartment();

        $email = new TemplatedEmail();

        if (!empty($this->notifications_bcc) && null !== $this->notifications_bcc[0]) {
            $email->addBcc(...$this->notifications_bcc);
        }

        $email->replyTo($department->isFSR() ? $this->fsb_email : $this->hhv_email);

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

    public function generatePDF(PaymentOrderSubmittedEvent $event): void
    {
        $payment_order = $event->getPaymentOrder();
        $pdf_content = $this->paymentOrderPDFGenerator->generatePDF($payment_order);

        //Create temporary file
        $tmpfname = tempnam(sys_get_temp_dir(), 'stura');
        file_put_contents($tmpfname, $pdf_content);

        $file = new UploadedFile($tmpfname, 'form.pdf', null, null, true);

        $payment_order->setPrintedFormFile($file);

        $this->userProvider->setManualUsername('[Automatic form generation]', UserProvider::INTERNAL_USER_IDENTIFIER);

        //Save to database and let VichUploadBundle handle everything else (it will also remove the temp file)
        $this->entityManager->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentOrderSubmittedEvent::NAME => [
                ['generatePDF', 10],
                ['sendUserEmail', 0],
            ],
        ];
    }
}
