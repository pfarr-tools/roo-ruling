<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\Tests\Unit;

use InvalidArgumentException;
use PfarrTools\RooRuling\AnswerFlexibility;
use PfarrTools\RooRuling\HandwritingSpaceEstimator;
use PHPUnit\Framework\TestCase;

final class HandwritingSpaceEstimatorTest extends TestCase
{
    private HandwritingSpaceEstimator $estimator;

    protected function setUp(): void
    {
        $this->estimator = new HandwritingSpaceEstimator();
    }

    public function testJesusInGradeTwoUsesWeightedLengthAndRoundsUp(): void
    {
        self::assertSame(45.0, $this->estimator->estimateWidthMm('Jesus', 2));
    }

    public function testSpacesAndCharacterClassesAreWeighted(): void
    {
        // i (.7) + m (1.3) + space (.55) + H (1.0) + i (.7) + m (1.3)
        // + m (1.3) + e (1.0) + l (.7) = 8.55.
        self::assertSame(70.0, $this->estimator->estimateWidthMm('im Himmel', 2));
        self::assertSame(45.0, $this->estimator->estimateWidthMm('mmmmmm', 5));
        self::assertSame(60.0, $this->estimator->estimateWidthMm('mMwWmMwW', 5));
        self::assertSame(25.0, $this->estimator->estimateWidthMm('ilt', 5));
        self::assertSame(45.0, $this->estimator->estimateWidthMm('iIltiIltiI', 5));
        self::assertSame(25.0, $this->estimator->estimateWidthMm("a\ta", 5));
    }

    public function testPunctuationAndDashesHaveTheirOwnWeights(): void
    {
        // Twelve punctuation marks (6.0) at Grade 5.
        self::assertSame(40.0, $this->estimator->estimateWidthMm('.,:;!?.,:;!?', 5));
        self::assertSame(25.0, $this->estimator->estimateWidthMm('-–—', 5));
    }

    public function testUnicodeLettersCountAsOneCharacter(): void
    {
        // für Öl = f + ü + r + space + Ö + l = 5.55 weighted characters.
        self::assertSame(35.0, $this->estimator->estimateWidthMm('für Öl', 5));
    }

    public function testMinimumWidthAppliesToShortAnswers(): void
    {
        self::assertSame(30.0, $this->estimator->estimateWidthMm('A', 1));
        self::assertSame(30.0, $this->estimator->estimateWidthMm('7', 2));
        self::assertSame(25.0, $this->estimator->estimateWidthMm('Ja', 3));
    }

    public function testWidthsAreRoundedUpToFiveMillimetres(): void
    {
        self::assertSame(30.0, $this->estimator->estimateWidthMm('abcde', 5));
        self::assertSame(40.0, $this->estimator->estimateWidthMm('abcdef', 5));
    }

    public function testGradeSpecificCharacterWidthsAreApplied(): void
    {
        self::assertSame(85.0, $this->estimator->estimateWidthMm('abcdefghjk', 1));
        self::assertSame(80.0, $this->estimator->estimateWidthMm('abcdefghjk', 2));
        self::assertSame(70.0, $this->estimator->estimateWidthMm('abcdefghjk', 3));
        self::assertSame(65.0, $this->estimator->estimateWidthMm('abcdefghjk', 4));
        self::assertSame(60.0, $this->estimator->estimateWidthMm('abcdefghjk', 5));
        self::assertSame(60.0, $this->estimator->estimateWidthMm('abcdefghjk', 10));
    }

    public function testFlexibilityIncreasesWidth(): void
    {
        $widths = array_map(
            fn (AnswerFlexibility $flexibility): float => $this->estimator->estimateWidthMm(
                'Ein ausreichend langer erwarteter Antworttext',
                3,
                $flexibility,
            ),
            AnswerFlexibility::cases(),
        );

        self::assertLessThan($widths[1], $widths[0]);
        self::assertLessThan($widths[2], $widths[1]);
    }

    public function testExactFlexibilityIsTheDefault(): void
    {
        self::assertSame(
            $this->estimator->estimateWidthMm('Jesus', 2, AnswerFlexibility::EXACT),
            $this->estimator->estimateWidthMm('Jesus', 2),
        );
    }

    public function testGradeMustBePositive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->estimator->estimateWidthMm('Ja', 0);
    }

    public function testNegativeGradeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->estimator->estimateWidthMm('Ja', -1);
    }
}
