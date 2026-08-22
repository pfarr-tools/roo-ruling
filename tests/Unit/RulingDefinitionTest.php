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

        self::assertSame([3.0, 4.0, 3.0], $ruling->zonesMm);
        self::assertSame(2.0, $ruling->gapMm);
        self::assertSame(12.0, $ruling->pitchMm());
        self::assertSame(1, $ruling->textZoneIndex);
        self::assertSame([1, 2], $ruling->lineIndexes);
        self::assertFalse($ruling->drawsLine(0));
        self::assertTrue($ruling->drawsLine(1));
        self::assertTrue($ruling->drawsLine(2));
        self::assertFalse($ruling->drawsLine(3));
        self::assertSame([1], $ruling->sideBorderZoneIndexes);
        self::assertTrue($ruling->drawsSideBorder(1));
        self::assertFalse($ruling->drawsSideBorder(0));
        self::assertFalse($ruling->drawsSideBorder(2));
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
