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

namespace App\Services\PDF;

use App\Entity\PaymentOrder;
use IntlDateFormatter;
use LogicException;
use TCPDF;

/**
 * This service generates a PDF document describing the payment order.
 * @see \App\Tests\Services\PDF\PaymentOrderPDFGeneratorTest
 */
class PaymentOrderPDFGenerator
{
    public function __construct(private readonly TwigPDFRenderer $PDFRenderer)
    {
    }

    public function generatePDF(PaymentOrder $paymentOrder): string
    {
        $context = [
            'paymentOrder' => $paymentOrder,
        ];

        return $this->PDFRenderer->renderTemplate('pdf/payment_order/payment_order.html.twig', $context);
    }
}
