<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\Tests\Unit;

use PfarrTools\RooRuling\RulingPreset;
use PHPUnit\Framework\TestCase;

final class RulingDefinitionTest extends TestCase
{
    public function testGrade1Geometry(): void
    {
        $ruling = RulingPreset::Grade1->definition();

        self::assertSame([4.0, 5.0, 4.0], $ruling->zonesMm);
        self::assertSame(5.0, $ruling->gapMm);
        self::assertSame(13.0, $ruling->bandHeightMm());
        self::assertSame(18.0, $ruling->pitchMm());
        self::assertSame(1, $ruling->textZoneIndex);
    }

    public function testGrade2Geometry(): void
    {
        $ruling = RulingPreset::Grade2->definition();

        self::assertSame([3.0, 4.0, 3.0], $ruling->zonesMm);
        self::assertSame(2.0, $ruling->gapMm);
        self::assertSame(12.0, $ruling->pitchMm());
    }

    public function testGrade3Geometry(): void
    {
        $ruling = RulingPreset::Grade3->definition();

        self::assertSame([3.8], $ruling->zonesMm);
        self::assertSame(7.7, $ruling->gapMm);
        self::assertEqualsWithDelta(11.5, $ruling->pitchMm(), 0.001);
    }

    public function testGrade4PlusGeometry(): void
    {
        $ruling = RulingPreset::Grade4Plus->definition();

        self::assertSame([10.0], $ruling->zonesMm);
        self::assertSame(0.0, $ruling->gapMm);
        self::assertSame(10.0, $ruling->pitchMm());
        self::assertFalse($ruling->leftBorder);
        self::assertFalse($ruling->rightBorder);
        self::assertFalse($ruling->topBorder);
    }
}
