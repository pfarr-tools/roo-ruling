<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\Tests\Integration;

use InvalidArgumentException;
use PfarrTools\RooRuling\Image\PngRulingRenderer;
use PfarrTools\RooRuling\RulingPreset;
use PHPUnit\Framework\TestCase;

final class PngRulingRendererTest extends TestCase
{
    private PngRulingRenderer $renderer;

    protected function setUp(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('The GD extension is required.');
        }

        $this->renderer = new PngRulingRenderer();
    }

    public function testRendersTransparentPngWithGradeSpecificDimensions(): void
    {
        $png = $this->renderer->render(
            ruling: RulingPreset::Grade1->definition(),
            widthMm: 25.4,
            dpi: 100,
        );
        $image = imagecreatefromstring($png);

        self::assertNotFalse($image);
        self::assertSame(100, imagesx($image));
        self::assertSame(51, imagesy($image));
        self::assertLessThan(127, $this->alphaAt($image, 50, 50));
        self::assertLessThan(127, $this->alphaAt($image, 50, 0));

        imagedestroy($image);
    }

    public function testRendersTheSameLinesAsTheGradeThreeDefinition(): void
    {
        $image = imagecreatefromstring($this->renderer->render(
            ruling: RulingPreset::Grade3->definition(),
            widthMm: 25.4,
            dpi: 100,
        ));

        self::assertNotFalse($image);
        // Grade 3 has no line at the top; its visible horizontal lines are at
        // 3 mm and 7 mm, while its side borders span the middle zone.
        self::assertSame(127, $this->alphaAt($image, 50, 0));
        self::assertLessThan(127, $this->alphaAt($image, 50, 12));
        self::assertLessThan(127, $this->alphaAt($image, 50, 28));
        self::assertLessThan(127, $this->alphaAt($image, 0, 20));
        self::assertSame(127, $this->alphaAt($image, 0, 5));

        imagedestroy($image);
    }

    public function testRejectsInvalidDimensions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->renderer->render(RulingPreset::Grade1->definition(), 0.0);
    }

    private function alphaAt(\GdImage $image, int $x, int $y): int
    {
        return (imagecolorat($image, $x, $y) >> 24) & 0x7f;
    }
}
