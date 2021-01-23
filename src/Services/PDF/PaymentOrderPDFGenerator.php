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
 */
class PaymentOrderPDFGenerator
{
    /**
     * Generates a PDF from the given PaymentOrder.
     * The raw PDF content is returned as string.
     */
    public function generatePDF(PaymentOrder $paymentOrder): string
    {
        if (null === $paymentOrder->getDepartment()) {
            throw new LogicException('$paymentOrder must have an associated department!');
        }

        $pdf = new SturaPDF();
        $pdf->setAuthor('StuRa FSU Jena');
        $pdf->setTitle('Zahlungsauftrag #'.$paymentOrder->getId());
        $pdf->setSubject('Zahlungsauftrag');
        $pdf->SetAutoPageBreak(false);

        $pdf->AddPage();

        $pdf->setY(80);
        $pdf->setMargins(25, 10);

        $pdf->writeHTML('<h1>Zahlungsauftrag '.$paymentOrder->getIDString().'</h1><br>');

        $this->writeRow($pdf, 'Name Auftraggeber*in', $paymentOrder->getFullName());
        $this->writeRow($pdf, 'Struktur / Organisation', $paymentOrder->getDepartment()->getName());
        $this->writeRow($pdf, 'Projektbezeichnung', $paymentOrder->getProjectName());
        $this->writeRow($pdf, 'Betrag', $paymentOrder->getAmountString().' €');
        $this->writeRow($pdf, 'Mittelfreigabe / Finanzantrag', !empty($paymentOrder->getFundingId()) ? $paymentOrder->getFundingId() : '<i>Nicht angegeben</i>');
        $this->writeRow($pdf, 'FSR-Kom Umbuchung', $paymentOrder->isFsrKomResolution() ? 'Ja' : 'Nein');
        $formatter = new IntlDateFormatter('de_DE', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
        $this->writeRow($pdf, 'Beschlussdatum', null === $paymentOrder->getResolutionDate() ? '<i>Nicht angegeben</i>' : $formatter->format($paymentOrder->getResolutionDate()));

        $pdf->Ln();

        $this->writeRow($pdf, 'Kontoinhaber*in', $paymentOrder->getBankInfo()->getAccountOwner());
        $this->writeRow($pdf, 'Straße/Nr.', $paymentOrder->getBankInfo()->getStreet());
        $this->writeRow($pdf, 'PLZ/Ort', $paymentOrder->getBankInfo()->getZipCode().' '.$paymentOrder->getBankInfo()->getCity());
        $this->writeRow($pdf, 'IBAN', $paymentOrder->getBankInfo()->getIban());
        $this->writeRow($pdf, 'BIC', $paymentOrder->getBankInfo()->getBic());
        $this->writeRow($pdf, 'Bank', $paymentOrder->getBankInfo()->getBankName());
        $this->writeRow($pdf, 'Verwendungszweck', $paymentOrder->getBankInfo()->getReference());

        $pdf->Ln();
        $formatter = new IntlDateFormatter('de-DE', IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM);
        $this->writeRow($pdf, 'Einreichungsdatum', $formatter->format($paymentOrder->getCreationDate()));

        $pdf->Ln(15);
        $pdf->writeHTML('Dieses Dokument muss <i>ausgedruckt</i> und <i>unterschrieben</i> werden und wird dann zusammen mit den Belegen abgeheftet
                und mit dem Jahresabschluss beim StuRa abgegeben!');
        $pdf->writeHTML('Mit meiner Unterschrift erkläre ich, dass die Angaben hier korrekt sind und ich alle Belege vorliegen habe.');

        if ($paymentOrder->getDepartment()->isFSR()) {
            $pdf->Ln(20);
            $this->addSignatureField($pdf, 'Datum, Unterschrift FSR Verantwortliche', false);
        }

        //$pdf->MultiCell(0,0, 'Name:', 0, 'L', )

        return $pdf->Output('doc.pdf', 'S');
    }

    private function addSignatureField(TCPDF $pdf, string $content, bool $ln = true, string $align = 'L'): void
    {
        $pdf->writeHTML('_____________________________________________<br><small>'.$content.'</small>', $ln, false, false, false, $align);
    }

    private function writeRow(TCPDF $pdf, string $property, string $value): void
    {
        $pdf->MultiCell(80, 5, '<b>'.$property.':</b>', 0, 'L', 0, 0, '', '', true, 0, true);
        $pdf->MultiCell(0, 5, $value, 0, 'L', 0, 1, '', '', true, 0, true);
    }
}
