<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\Tests\Unit;

use PfarrTools\RooRuling\PhpWord\UnitConverter;
use PHPUnit\Framework\TestCase;

final class UnitConverterTest extends TestCase
{
    public function testMillimetresRoundTripWithinTwipPrecision(): void
    {
        foreach ([2.0, 3.0, 3.8, 4.0, 5.0, 7.7, 170.0] as $mm) {
            $roundTrip = UnitConverter::twipsToMm(UnitConverter::mmToTwips($mm));
            self::assertEqualsWithDelta($mm, $roundTrip, 0.02);
        }
    }
}
