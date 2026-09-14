<?php

namespace App\Support;

use GdImage;
use RuntimeException;

/**
 * Renders the application's PWA/favicon icon files (letter+color or an
 * uploaded image) at every size the app needs, using GD — no image
 * processing package required, GD ships with almost every PHP install.
 */
class AppIconGenerator
{
    /**
     * @var array<string, int>
     */
    private const SIZES = [
        'icon-192.png' => 192,
        'icon-512.png' => 512,
        'apple-touch-icon.png' => 180,
        'favicon-32.png' => 32,
        'favicon-16.png' => 16,
    ];

    public static function isAvailable(): bool
    {
        return extension_loaded('gd');
    }

    public static function generateFromLetter(string $letter, string $hexColor): void
    {
        self::ensureAvailable();

        [$r, $g, $b] = self::hexToRgb($hexColor);

        foreach (self::SIZES as $filename => $size) {
            $canvas = self::renderLetterCanvas($letter, $r, $g, $b, $size);
            imagepng($canvas, public_path('icons/'.$filename));
            imagedestroy($canvas);
        }

        self::writeFavicon();
    }

    public static function generateFromImage(string $absoluteSourcePath): void
    {
        self::ensureAvailable();

        $source = self::loadImage($absoluteSourcePath);

        foreach (self::SIZES as $filename => $size) {
            $canvas = self::squareCropResize($source, $size);
            imagepng($canvas, public_path('icons/'.$filename));
            imagedestroy($canvas);
        }

        imagedestroy($source);

        self::writeFavicon();
    }

    private static function ensureAvailable(): void
    {
        if (! self::isAvailable()) {
            throw new RuntimeException('L\'estensione GD di PHP non è attiva su questo server: impossibile generare le icone.');
        }
    }

    private static function renderLetterCanvas(string $letter, int $r, int $g, int $b, int $size): GdImage
    {
        $canvas = imagecreatetruecolor($size, $size);
        $bg = imagecolorallocate($canvas, $r, $g, $b);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $bg);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        $letter = mb_strtoupper(mb_substr($letter, 0, 1));
        $font = self::fontPath();
        $fontSize = (int) round($size * 0.5);

        if ($font && $fontSize > 0) {
            $box = imagettfbbox($fontSize, 0, $font, $letter);
            $textWidth = abs($box[4] - $box[0]);
            $textHeight = abs($box[5] - $box[1]);
            $x = (int) round(($size - $textWidth) / 2 - $box[0]);
            // $box[7] (upper-left y, negative = above baseline), not $box[1]
            // (lower-left y, always 0 for a baseline-sitting letter like this)
            // — using the wrong one here previously pushed the glyph mostly
            // above the canvas instead of centering it.
            $y = (int) round(($size - $textHeight) / 2 - $box[7]);
            imagettftext($canvas, $fontSize, 0, $x, $y, $white, $font, $letter);
        } else {
            // No TTF available — fall back to GD's built-in bitmap font
            // rather than failing outright. Looks blocky at large sizes,
            // but only kicks in if the bundled font file goes missing.
            $gdFont = 5;
            $x = (int) round(($size - imagefontwidth($gdFont) * strlen($letter)) / 2);
            $y = (int) round(($size - imagefontheight($gdFont)) / 2);
            imagestring($canvas, $gdFont, $x, $y, $letter, $white);
        }

        return $canvas;
    }

    private static function loadImage(string $path): GdImage
    {
        $info = getimagesize($path);
        abort_unless($info, 422, 'File immagine non valido.');

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            default => null,
        };

        abort_unless($image, 422, 'Formato immagine non supportato.');

        return $image;
    }

    private static function squareCropResize(GdImage $source, int $size): GdImage
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $cropSize = min($sourceWidth, $sourceHeight);
        $cropX = (int) (($sourceWidth - $cropSize) / 2);
        $cropY = (int) (($sourceHeight - $cropSize) / 2);

        $canvas = imagecreatetruecolor($size, $size);
        imagecopyresampled($canvas, $source, 0, 0, $cropX, $cropY, $size, $size, $cropSize, $cropSize);

        return $canvas;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function fontPath(): ?string
    {
        $path = resource_path('fonts/DejaVuSans-Bold.ttf');

        return is_file($path) ? $path : null;
    }

    /**
     * A .ico file that simply wraps the 32×32 PNG — valid since Windows
     * Vista and supported by every current browser, and far simpler than
     * hand-rolling the legacy uncompressed BMP icon format.
     */
    private static function writeFavicon(): void
    {
        $pngData = file_get_contents(public_path('icons/favicon-32.png'));

        $header = pack('vvv', 0, 1, 1);
        $entry = pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($pngData), 22);

        file_put_contents(public_path('favicon.ico'), $header.$entry.$pngData);
    }
}
