<?php


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

    public function __construct(MailerInterface $mailer, TranslatorInterface $translator, string $fsb_email)
    {
        $this->mailer = $mailer;
        $this->fsb_email = $fsb_email;
        $this->translator = $translator;
    }

    public function sendUserEmail(PaymentOrderSubmittedEvent $event): void
    {
        $payment_order = $event->getPaymentOrder();
        if ($payment_order->getDepartment() === null || empty($payment_order->getDepartment()->getContactEmails())) {
            return;
        }
        $department = $payment_order->getDepartment();

        $email = new TemplatedEmail();
        $email->addTo(...$department->getContactEmails());
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