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

use DateTime;
use IntlDateFormatter;
use TCPDF;

/**
 * A TCPDF class configured to create a PDF document with StuRa header.
 */
class SturaPDF extends TCPDF
{
    public function Header(): void
    {
        $image_file = dirname(__DIR__, 3).'/assets/StuRa.png';

        //$image_file = 'C:\Users\janhb\Documents\Projekte\PHP\stura\assets\StuRa.png';

        $this->Image($image_file, 5, 5, 210, 0, 'png', 'https://stura.uni-jena.de');

        $this->SetFont('helvetica', '', 9);
        $this->setY(35);
        $this->writeHTMLCell(0, 0, 100, 50, '<h2>Studierendenrat</h2>');
        $this->writeHTMLCell(0, 0, 145, 55, 'Carl-Zeiss-Straße 3');
        $this->writeHTMLCell(0, 0, 145, 60, '07747 Jena');

        // Title
        //$this->Cell(0, 15, '<< TCPDF Example 003 >>', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        //$this->writeHTML($image_file);
    }

    public function Footer(): void
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);

        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Seite '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'L');

        $formatter = new IntlDateFormatter('de-DE', IntlDateFormatter::SHORT, IntlDateFormatter::MEDIUM);

        $this->Cell(0, 10, 'Erzeugt '.$formatter->format(new DateTime()), 0, false, 'R');
    }
}
