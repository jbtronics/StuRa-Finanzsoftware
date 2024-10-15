<?php

declare(strict_types=1);


namespace App\Services\PDF;

use Jbtronics\DompdfFontLoaderBundle\Services\DompdfFactoryInterface;
use Twig\Environment;

/**
 * A service which allows to render twig templates into a PDF using DOMPDF
 */
final class TwigPDFRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly DompdfFactoryInterface $dompdfFactory,
    ){}

    /**
     * Render the given Twig template to a PDF
     * @param  string  $template
     * @param  array  $context
     * @param  string  $orientation
     * @return string
     */
    public function renderTemplate(string $template, array $context, string $orientation = 'portrait'): string
    {
        $html = $this->twig->render($template, $context);
        return $this->renderHtmlToPDF($html, $orientation);
    }

    /**
     * Render the given HTML to a PDF using DOMPDF
     * @param  string  $html
     * @param  string  $orientation
     * @return string
     */
    public function renderHtmlToPDF(string $html, string $orientation = "portrait"): string
    {
        $dompdf = $this->dompdfFactory->create();

        $dompdf->loadHtml($html);
        $dompdf->getOptions()->setIsRemoteEnabled(false);

        $dompdf->setPaper('A4', $orientation);

        $dompdf->render();
        return $dompdf->output();
    }
}