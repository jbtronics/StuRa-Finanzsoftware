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
use App\Entity\SEPAExport;
use App\Helpers\PDFResponse;
use App\Services\PDF\PaymentOrderPDFGenerator;
use App\Services\PDF\SEPAExport\SEPAExportPDFGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/pdf")
 */
class PDFGeneratorController extends AbstractController
{
    /**
     * @Route("/payment_order/{id}")
     */
    public function paymentOrderPdf(PaymentOrder $paymentOrder, PaymentOrderPDFGenerator $paymentOrderPDFGenerator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SHOW_PAYMENT_ORDERS');

        $data = $paymentOrderPDFGenerator->generatePDF($paymentOrder);

        return new PDFResponse($data);
    }

    /**
     * @Route("/sepa_export/{id}")
     *
     * @param  SEPAExport  $SEPAExport
     * @param  SEPAExportPDFGenerator  $SEPAExportPDFGenerator
     * @return Response
     */
    public function sepaExportPDF(SEPAExport $SEPAExport, SEPAExportPDFGenerator $SEPAExportPDFGenerator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SHOW_SEPA_EXPORTS');

        $data = $SEPAExportPDFGenerator->generatePDF($SEPAExport);
        return new PDFResponse($data);
    }

}
