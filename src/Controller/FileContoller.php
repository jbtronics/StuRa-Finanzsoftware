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
 * This controller handles the display/download of attachment files.
 */
class FileContoller extends AbstractController
{
    /**
     * @Route("/payment_order/{id}/form", name="file_payment_order_form")
     * @param  PaymentOrder  $paymentOrder
     * @param  DownloadHandler  $downloadHandler
     * @return Response
     */
    public function paymentOrderForm(PaymentOrder $paymentOrder, DownloadHandler $downloadHandler): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SHOW_PAYMENT_ORDERS');

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
    public function paymentOrderReferences(PaymentOrder $paymentOrder, DownloadHandler $downloadHandler): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SHOW_PAYMENT_ORDERS');

        return $downloadHandler->downloadObject(
            $paymentOrder,
            'references_file',
            null,
            $paymentOrder->getReferencesFile()->getFilename(),
            false
        );
    }
}