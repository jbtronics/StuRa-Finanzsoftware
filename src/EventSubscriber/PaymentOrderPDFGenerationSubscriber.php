<?php

declare(strict_types=1);


namespace App\EventSubscriber;

use App\Audit\UserProvider;
use App\Event\PaymentOrderConfirmedEvent;
use App\Event\PaymentOrderSubmittedEvent;
use App\Services\PDF\PaymentOrderPDFGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PaymentOrderPDFGenerationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PaymentOrderPDFGenerator $paymentOrderPDFGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserProvider $userProvider,)
    {

    }

    public function generatePDF(PaymentOrderSubmittedEvent|PaymentOrderConfirmedEvent $event): void
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
        //The priorities here need to be high, so that the PDF is generated before the emails are sent
        return [
            //Generate a draft version of the PDF when a payment order is submitted
            PaymentOrderSubmittedEvent::NAME => [
                ['generatePDF', 1000],
            ],
            //Generae the final version of the PDF when a payment order is confirmed
            PaymentOrderConfirmedEvent::NAME => [
                ['generatePDF', 1000]
            ],
        ];
    }
}