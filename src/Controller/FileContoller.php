<?php


namespace App\Controller;


use App\Entity\PaymentOrder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Handler\DownloadHandler;
use Vich\UploaderBundle\Handler\UploadHandler;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * @Route("/file")
 * @package App\Controller
 */
class FileContoller extends AbstractController
{
    /**
     * @Route("/payment_order/{id}/form", name="file_payment_order_form")
     * @param  PaymentOrder  $paymentOrder
     * @param  DownloadHandler  $downloadHandler
     * @return Response
     */
    public function PaymentOrderForm(PaymentOrder $paymentOrder, DownloadHandler $downloadHandler): Response
    {
        return $downloadHandler->downloadObject(
            $paymentOrder,
            'printed_form_file',
            null,
            $paymentOrder->getPrintedFormFile()->getFilename(),
            false
        );
    }

    /**
     * @Route("/payment_order/{id}/references", name="file_payment_order_references")
     * @param  PaymentOrder  $paymentOrder
     * @param  DownloadHandler  $downloadHandler
     * @return Response
     */
    public function PaymentOrderReferences(PaymentOrder $paymentOrder, DownloadHandler $downloadHandler): Response
    {
        return $downloadHandler->downloadObject(
            $paymentOrder,
            'references_file',
            null,
            $paymentOrder->getReferencesFile()->getFilename(),
            false
        );
    }
}