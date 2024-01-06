<?php

namespace App\Utils\Helpers;

use Dompdf\Dompdf;

class PdfHelper
{
    final public static function generate(string $htmlCode): null|string
    {
        $dompdf = new Dompdf();
        $options = $dompdf->getOptions();
        $options->setDefaultFont('Helvetica');
        $options->setIsRemoteEnabled(true);
        $options->setDefaultPaperSize('A4');
        $dompdf->setOptions($options);
        $dompdf->loadHtml($htmlCode);
        $dompdf->render();
        // Output the generated PDF
        return $dompdf->output();
    }
}