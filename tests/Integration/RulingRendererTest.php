<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\Tests\Integration;

use PhpOffice\PhpWord\PhpWord;
use PfarrTools\RooRuling\PhpWord\RulingRenderer;
use PfarrTools\RooRuling\RulingPreset;
use PHPUnit\Framework\TestCase;

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
}
