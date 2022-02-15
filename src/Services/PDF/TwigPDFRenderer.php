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

namespace App\Services\PDF;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class TwigPDFRenderer
{
    private $twig;
    private $cacheDir;

    public function __construct(Environment $twig, KernelInterface $kernel)
    {
        $this->twig = $twig;
        $this->cacheDir = $kernel->getCacheDir();
    }

    /**
     * @param  string  $template The path of the template to render
     * @param  array  $context The twig context (variables)
     * @param  string  $orientation The paper orientation
     * @return string The PDF data stream
     */
    public function renderTemplate(string $template, array $context, string $orientation = 'portrait'): string
    {
        $html = $this->twig->render($template, $context);
        return $this->renderHTML($html, $orientation);
    }

    /**
     * @param  string  $html The HTML to render
     * @param  string  $orientation The paper orientation to use
     * @return string The PDF data stream
     */
    public function renderHTML(string $html, string $orientation = 'portrait'): string
    {
        $dompdf = new Dompdf();

        $dompdf->loadHtml($html);
        $dompdf->getOptions()->setIsRemoteEnabled(false);
        $dompdf->getOptions()->setFontCache($this->cacheDir);
        $dompdf->getOptions()->setTempDir($this->cacheDir);

        $dompdf->setPaper('A4', $orientation);

        $dompdf->render();
        return $dompdf->output();
    }
}