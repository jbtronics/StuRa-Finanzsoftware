<?php
/*
 * Copyright (C)  2020-2022  Jan Böhmer
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

namespace App\Services\PDF\SEPAExport;

use App\Entity\SEPAExport;
use App\Services\PDF\TwigPDFRenderer;

class SEPAExportPDFGenerator
{
    private $twigPDFRenderer;

    public function __construct(TwigPDFRenderer $twigPDFRenderer)
    {
        $this->twigPDFRenderer = $twigPDFRenderer;
    }

    public function generatePDF(SEPAExport $SEPAExport): string
    {
        return $this->twigPDFRenderer->renderTemplate('pdf/SEPAExport/sepa_export.html.twig', [
            'sepaExport' => $SEPAExport,
        ], 'landscape');
    }
}