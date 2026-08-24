<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\PhpWord;

final class UnitConverter
{
    private const TWIPS_PER_INCH = 1440.0;
    private const MM_PER_INCH = 25.4;

    public static function mmToTwips(float $mm): int
    {
        return (int) round($mm / self::MM_PER_INCH * self::TWIPS_PER_INCH);
    }

    public static function twipsToMm(int $twips): float
    {
        return $twips / self::TWIPS_PER_INCH * self::MM_PER_INCH;
    }

    public static function mmToPoints(float $mm): float
    {
        return $mm / self::MM_PER_INCH * 72.0;
    }
}
