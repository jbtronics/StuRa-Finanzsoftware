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
use App\Services\PDF\PaymentOrderPDFGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/pdf')]
final class PDFGeneratorController extends AbstractController
{
    public function __construct(private PaymentOrderPDFGenerator $paymentOrderPDFGenerator)
    {
    }

    #[Route(path: '/payment_order/{id}/structure', name: "payment_order_pdf_structure")]
    public function pdfStructure(PaymentOrder $paymentOrder): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SHOW_PAYMENT_ORDERS');

        $data = $this->paymentOrderPDFGenerator->generatePDF($paymentOrder);
        $response = new Response($data);

        $response->headers->set('Content-type', 'application/pdf');
        $response->headers->set('Content-length', (string) strlen($data));
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }

    #[Route(path: '/payment_order/{id}/stura', name: "payment_order_pdf_stura")]
    public function pdfStuRa(PaymentOrder $paymentOrder, PaymentOrderPDFGenerator $paymentOrderPDFGenerator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SHOW_PAYMENT_ORDERS');

        $data = $this->paymentOrderPDFGenerator->generateStuRaPDF($paymentOrder);
        $response = new Response($data);

        $response->headers->set('Content-type', 'application/pdf');
        $response->headers->set('Content-length', (string) strlen($data));
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }


}
