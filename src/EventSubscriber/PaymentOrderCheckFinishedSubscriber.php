<?php

declare(strict_types=1);


namespace App\EventSubscriber;

use App\Entity\PaymentOrder;
use App\Event\PaymentOrderCheckFinishedEvent;
use App\Services\PDF\PaymentOrderPDFGenerator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;

use function Symfony\Component\String\u;

/**
 * This subscriber handles the event that is triggered, if a payment order has been checked by all responsible persons,
 * and is now ready for the next steps.
 * It send an email to the form email address, with the generated form attached.
 */
class PaymentOrderCheckFinishedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly PaymentOrderPDFGenerator $paymentOrderPDFGenerator,
        #[Autowire('%app.form_email%')] private readonly string $formEmail
    )
    {
    }

    /**
     * Sends an email to the form email address, with the generated form attached.
     * @param  PaymentOrder  $paymentOrder
     * @return void
     */
    public function sendCheckedEmail(PaymentOrder $paymentOrder): void
    {
        $email = new TemplatedEmail();
        $email->to($this->formEmail);
        $email->subject(sprintf('%s (%s) geprüft', $paymentOrder->getIDString(), u($paymentOrder->getProjectName())->truncate(50)));
        $email->htmlTemplate('mails/checked_notification.html.twig');
        $email->context([
            'payment_order' => $paymentOrder,
        ]);

        $pdfData = $this->paymentOrderPDFGenerator->generateStuRaPDF($paymentOrder);

        //Add the form as attachment
        $email->attach($pdfData, $paymentOrder->getIDString() . '.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    public function handleEvent(PaymentOrderCheckFinishedEvent $event): void
    {
        //Ensure that the payment order is actually checked
        if (!$event->getPaymentOrder()->getFactuallyCorrect()->isChecked() || !$event->getPaymentOrder()->getMathematicallyCorrect()->isChecked()) {
            return;
        }

        $this->sendCheckedEmail($event->getPaymentOrder());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentOrderCheckFinishedEvent::NAME => ['handleEvent'],
        ];
    }
}