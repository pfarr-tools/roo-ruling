<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\Image;

use InvalidArgumentException;
use PfarrTools\RooRuling\RulingDefinition;
use RuntimeException;

final class PngRulingRenderer
{
    private const float MM_PER_INCH = 25.4;

    /**
     * Render one ruling band as transparent PNG data.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException when the GD extension is unavailable.
     */
    public function render(RulingDefinition $ruling, float $widthMm, int $dpi = 300): string
    {
        if ($widthMm <= 0) {
            throw new InvalidArgumentException('Width must be greater than zero.');
        }

        if ($dpi <= 0) {
            throw new InvalidArgumentException('DPI must be greater than zero.');
        }

        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('The GD extension is required to render PNG rulings.');
        }

        $widthPx = max(1, (int) round($this->mmToPixels($widthMm, $dpi)));
        $heightPx = max(1, (int) round($this->mmToPixels($ruling->bandHeightMm(), $dpi)));
        $image = imagecreatetruecolor($widthPx, $heightPx);

        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefill($image, 0, 0, $transparent);

        imagealphablending($image, true);
        imagesetthickness($image, max(1, (int) round($ruling->lineSize / 8.0 / 72.0 * $dpi)));
        $lineColor = imagecolorallocate($image, ...$this->rgb($ruling->lineColor));
        $widthEnd = $widthPx - 1;

        $lineTopMm = 0.0;
        for ($lineIndex = 0; $lineIndex <= count($ruling->zonesMm); ++$lineIndex) {
            $drawTopLine = $lineIndex === 0 && $ruling->topBorder;
            $drawLine = $ruling->drawsLine($lineIndex) && ($lineIndex > 0 || $drawTopLine);

            if ($drawLine) {
                $y = min($heightPx - 1, (int) round($this->mmToPixels($lineTopMm, $dpi)));
                imageline($image, 0, $y, $widthEnd, $y, $lineColor);
            }

            if (isset($ruling->zonesMm[$lineIndex])) {
                $lineTopMm += $ruling->zonesMm[$lineIndex];
            }
        }

        $zoneTopMm = 0.0;
        foreach ($ruling->zonesMm as $zoneIndex => $zoneHeightMm) {
            if ($ruling->drawsSideBorder($zoneIndex)) {
                $top = (int) round($this->mmToPixels($zoneTopMm, $dpi));
                $bottom = min($heightPx - 1, (int) round($this->mmToPixels($zoneTopMm + $zoneHeightMm, $dpi)));

                if ($ruling->leftBorder) {
                    imageline($image, 0, $top, 0, $bottom, $lineColor);
                }

                if ($ruling->rightBorder) {
                    imageline($image, $widthEnd, $top, $widthEnd, $bottom, $lineColor);
                }
            }

            $zoneTopMm += $zoneHeightMm;
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        if ($png === false) {
            throw new RuntimeException('The ruling PNG could not be encoded.');
        }

        return $png;
    }

    private function mmToPixels(float $millimetres, int $dpi): float
    {
        return $millimetres / self::MM_PER_INCH * $dpi;
    }

    /** @return array{int, int, int} */
    private function rgb(string $hex): array
    {
        if (preg_match('/^[0-9a-fA-F]{6}$/', $hex) !== 1) {
            throw new InvalidArgumentException('Line color must be a six-digit hexadecimal value.');
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
