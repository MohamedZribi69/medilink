<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Psr\Container\ContainerInterface;

/**
 * Génération PDF : utilise NucleosDompdfBundle si installé, sinon Dompdf directement.
 */
class PdfService
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    public function generateFromHtml(string $html, array $options = []): string
    {
        // Si le bundle NucleosDompdfBundle est installé et enregistré, utiliser son wrapper
        if (class_exists(\Nucleos\DompdfBundle\Wrapper\DompdfWrapper::class)
            && $this->container->has(\Nucleos\DompdfBundle\Wrapper\DompdfWrapper::class)) {
            $wrapper = $this->container->get(\Nucleos\DompdfBundle\Wrapper\DompdfWrapper::class);
            return $wrapper->getPdf($html, $options);
        }

        // Fallback : Dompdf directement (sans bundle)
        $dompdfOptions = new Options();
        $dompdfOptions->set('isRemoteEnabled', true);
        $dompdfOptions->set('isHtml5ParserEnabled', true);
        $dompdfOptions->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($dompdfOptions);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($options['paper'] ?? 'A4', $options['orientation'] ?? 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
