<?php

namespace App\Service;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;

/**
 * Génère un QR Code SVG pour un événement.
 *
 * Utilise la librairie endroid/qr-code.
 * Le QR Code encode les informations de l'événement en texte multiligne.
 */
final class QrCodeGenerator
{
    private const MAX_DATA_LENGTH = 800; // Limite QR Code raisonnable en caractères
    private const QR_SIZE        = 280;  // Taille en pixels
    private const QR_MARGIN      = 10;

    /**
     * Génère un QR Code SVG à partir d'un tableau de données texte.
     *
     * @param array<string, string|null> $fields  Ex: ['Titre' => 'Journée santé', 'Lieu' => 'Tunis']
     * @return ResultInterface  Résultat SVG prêt à envoyer en réponse HTTP
     */
    public function generateSvg(array $fields): ResultInterface
    {
        $lines = [];
        foreach ($fields as $label => $value) {
            $lines[] = $label . ' : ' . ($value !== null && $value !== '' ? $value : '—');
        }

        $data = implode("\n", $lines);

        // Tronquer si trop long (les QR Codes ont une capacité maximale)
        if (mb_strlen($data) > self::MAX_DATA_LENGTH) {
            $data = mb_substr($data, 0, self::MAX_DATA_LENGTH - 3) . '...';
        }

        $qrCode = new QrCode(
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: self::QR_SIZE,
            margin: self::QR_MARGIN,
        );

        $writer = new SvgWriter();

        return $writer->write($qrCode);
    }
}
