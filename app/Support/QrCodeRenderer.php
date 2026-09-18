<?php

namespace App\Support;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Log;

/**
 * Tiny wrapper around bacon/bacon-qr-code. Chooses whichever backend is
 * available in the PHP build (GD → Imagick → SVG) so PDFs render everywhere.
 *
 * Returns a base64 PNG (or SVG) data URI suitable for direct <img src="…"> use.
 */
class QrCodeRenderer
{
    public static function dataUri(string $payload, int $size = 180): string
    {
        try {
            if (function_exists('imagecreate')) {
                $renderer = new GDLibRenderer($size);
                $writer = new Writer($renderer);
                $png = $writer->writeString($payload);
                return 'data:image/png;base64,'.base64_encode($png);
            }

            if (extension_loaded('imagick')) {
                $renderer = new ImageRenderer(new RendererStyle($size), new ImagickImageBackEnd());
                $writer = new Writer($renderer);
                return 'data:image/png;base64,'.base64_encode($writer->writeString($payload));
            }

            $renderer = new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd());
            $writer = new Writer($renderer);
            return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($payload));
        } catch (\Throwable $e) {
            Log::warning('QR render failed', ['e' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Build a UPI intent URI per NPCI spec.
     * https://www.npci.org.in/PDF/npci/upi/UPI-Linking-Specs_ver1.6.pdf
     */
    public static function upiIntent(string $upiId, string $payeeName, float $amount, string $note = '', string $ref = ''): string
    {
        return 'upi://pay?'.http_build_query(array_filter([
            'pa' => $upiId,
            'pn' => $payeeName,
            'am' => number_format($amount, 2, '.', ''),
            'cu' => 'INR',
            'tn' => $note ?: null,
            'tr' => $ref ?: null,
        ]));
    }
}
