<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling;

enum RulingPreset: string
{
    case Grade1 = 'grade-1';
    case Grade2 = 'grade-2';
    case Grade3 = 'grade-3';
    case Grade4Plus = 'grade-4-plus';

    public function definition(): RulingDefinition
    {
        return match ($this) {
            self::Grade1 => new RulingDefinition(
                zonesMm: [4.0, 5.0, 4.0],
                gapMm: 5.0,
                textZoneIndex: 1,
            ),
            self::Grade2 => new RulingDefinition(
                zonesMm: [3.0, 4.0, 3.0],
                gapMm: 2.0,
                textZoneIndex: 1,
            ),
            self::Grade3 => new RulingDefinition(
                zonesMm: [3.0, 4.0, 3.0],
                gapMm: 2.0,
                textZoneIndex: 1,
                lineIndexes: [1, 2],
                sideBorderZoneIndexes: [1],
            ),
            self::Grade4Plus => new RulingDefinition(
                zonesMm: [10.0],
                gapMm: 0.0,
                leftBorder: false,
                rightBorder: false,
                topBorder: false,
                textZoneIndex: 0,
            ),
        };
    }

    public function referenceGeometry(): ReferenceGeometry
    {
        return match ($this) {
            self::Grade1 => new ReferenceGeometry(
                leftMm: 19.0,
                topMm: 22.0,
                widthMm: 170.0,
                bands: 14,
            ),
            self::Grade2 => new ReferenceGeometry(
                leftMm: 15.0,
                topMm: 29.0,
                widthMm: 178.0,
                bands: 20,
            ),
            self::Grade3 => new ReferenceGeometry(
                leftMm: 19.9,
                topMm: 25.7,
                widthMm: 171.0,
                bands: 22,
            ),
            self::Grade4Plus => new ReferenceGeometry(
                leftMm: 20.0,
                topMm: 20.0,
                widthMm: 170.0,
                bands: 25,
            ),
        };
    }
}
