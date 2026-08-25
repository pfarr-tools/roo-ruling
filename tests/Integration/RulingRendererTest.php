<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\Tests\Integration;

use PhpOffice\PhpWord\PhpWord;
use PfarrTools\RooRuling\PhpWord\RulingRenderer;
use PfarrTools\RooRuling\PhpWord\RulingDocument;
use PfarrTools\RooRuling\RulingPreset;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class RulingRendererTest extends TestCase
{
    public function testRendererCreatesExpectedNumberOfRows(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $ruling = RulingPreset::Grade1->definition();

        $table = (new RulingRenderer())->render($section, $ruling, 2, 170.0);

        // 3 zone rows + 1 gap + 3 zone rows.
        self::assertCount(7, $table->getRows());
    }

    public function testOdtReferenceSheetContainsEditableCellBorders(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'roo-ruling-').'.odt';

        try {
            (new RulingDocument())->saveReferenceSheet(RulingPreset::Grade1, $filename);

            $zip = new ZipArchive();
            self::assertSame(true, $zip->open($filename));
            $content = $zip->getFromName('content.xml');
            $zip->close();

            self::assertIsString($content);
            self::assertStringContainsString('style:family="table-cell"', $content);
            self::assertStringContainsString('fo:border-bottom="0.200pt solid #808080"', $content);
            self::assertMatchesRegularExpression('/style:row-height="0\.400[0-9]+cm"/', $content);
            self::assertStringContainsString('style:use-optimal-row-height="false"', $content);
        } finally {
            unlink($filename);
        }
    }

    public function testDrawingReferenceSheetOdtContainsAllFourRulingTypes(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'roo-drawing-ruling-').'.odt';

        try {
            (new RulingDocument())->saveDrawingReferenceSheet($filename);

            $zip = new ZipArchive();
            self::assertSame(true, $zip->open($filename));
            $content = $zip->getFromName('content.xml');
            $zip->close();

            self::assertIsString($content);
            self::assertSame(453, substr_count($content, '<draw:line'));
            self::assertStringContainsString('svg:stroke-color="#808080"', $content);
            self::assertStringContainsString('svg:stroke-width="0.5pt"', $content);
        } finally {
            unlink($filename);
        }
    }
}
