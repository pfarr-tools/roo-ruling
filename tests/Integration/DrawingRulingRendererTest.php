<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\Tests\Integration;

use InvalidArgumentException;
use PhpOffice\PhpWord\Element\Line;
use PhpOffice\PhpWord\PhpWord;
use PfarrTools\RooRuling\PhpWord\DrawingRulingRenderer;
use PfarrTools\RooRuling\RulingPreset;
use PHPUnit\Framework\TestCase;

final class DrawingRulingRendererTest extends TestCase
{
    public function testGrade3PreservesLeadingZoneAndMiddleSideBorders(): void
    {
        $section = (new PhpWord())->addSection();

        $lines = (new DrawingRulingRenderer())->render(
            section: $section,
            ruling: RulingPreset::Grade3->definition(),
            leftMm: 10.0,
            topMm: 20.0,
            widthMm: 100.0,
            count: 1,
        );

        self::assertCount(4, $lines);
        self::assertContainsOnlyInstancesOf(Line::class, $lines);

        $horizontalLines = array_values(array_filter(
            $lines,
            static fn (Line $line): bool => abs((float) $line->getStyle()->getHeight()) < 0.0001,
        ));
        $verticalLines = array_values(array_filter(
            $lines,
            static fn (Line $line): bool => $line->getStyle()->getHeight() > 0,
        ));

        self::assertCount(2, $horizontalLines);
        self::assertCount(2, $verticalLines);
        self::assertEqualsWithDelta(
            23.0,
            $horizontalLines[0]->getStyle()->getTop() / 2.834645669,
            0.001,
        );
        self::assertEqualsWithDelta(
            27.0,
            $horizontalLines[1]->getStyle()->getTop() / 2.834645669,
            0.001,
        );
        self::assertEqualsWithDelta(
            3.0,
            ($verticalLines[0]->getStyle()->getTop() / 2.834645669) - 20.0,
            0.001,
        );
        self::assertEqualsWithDelta(
            4.0,
            $verticalLines[0]->getStyle()->getHeight() / 2.834645669,
            0.001,
        );
    }

    public function testDrawingLinesUseRulingColorAndWidth(): void
    {
        $section = (new PhpWord())->addSection();

        $lines = (new DrawingRulingRenderer())->render(
            section: $section,
            ruling: RulingPreset::Grade1->definition(),
            leftMm: 10.0,
            topMm: 20.0,
            widthMm: 100.0,
            count: 1,
        );

        $horizontalLine = $lines[array_key_first(array_filter(
            $lines,
            static fn (Line $line): bool => abs((float) $line->getStyle()->getHeight()) < 0.0001,
        ))];

        self::assertNotEmpty($lines);
        self::assertSame('808080', $horizontalLine->getStyle()->getColor());
        self::assertSame(0.5, $horizontalLine->getStyle()->getWeight());
        self::assertEqualsWithDelta(
            100.0,
            $horizontalLine->getStyle()->getWidth() / 2.834645669,
            0.001,
        );
    }

    public function testDrawingRendererRequiresPositiveWidthAndCount(): void
    {
        $section = (new PhpWord())->addSection();
        $renderer = new DrawingRulingRenderer();
        $ruling = RulingPreset::Grade1->definition();

        $this->expectException(InvalidArgumentException::class);

        $renderer->render($section, $ruling, 10.0, 20.0, 0.0, 1);
    }
}
