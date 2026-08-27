<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling;

use InvalidArgumentException;

final class HandwritingSpaceEstimator
{
    private const float FIXED_PADDING_MM = 4.0;

    private const float ROUNDING_INCREMENT_MM = 5.0;

    /** @var array<int, float> */
    private const array CHARACTER_WIDTHS_MM = [
        1 => 7.0,
        2 => 6.5,
        3 => 5.5,
        4 => 5.0,
        5 => 4.5,
    ];

    public function estimateWidthMm(
        string $answer,
        int $grade,
        AnswerFlexibility $flexibility = AnswerFlexibility::EXACT,
    ): float {
        if ($grade < 1) {
            throw new InvalidArgumentException('Grade must be at least 1.');
        }

        $rawWidth = self::FIXED_PADDING_MM
            + $this->weightedCharacterCount($answer)
            * $this->characterWidthMm($grade)
            * $flexibility->factor();

        $minimumWidth = $grade <= 2 ? 30.0 : 25.0;
        $width = max($rawWidth, $minimumWidth);

        return ceil($width / self::ROUNDING_INCREMENT_MM) * self::ROUNDING_INCREMENT_MM;
    }

    private function characterWidthMm(int $grade): float
    {
        return self::CHARACTER_WIDTHS_MM[min($grade, 5)];
    }

    private function weightedCharacterCount(string $answer): float
    {
        $characters = preg_split('//u', $answer, -1, PREG_SPLIT_NO_EMPTY);

        if ($characters === false) {
            throw new InvalidArgumentException('Answer must be valid UTF-8.');
        }

        $weightedCount = 0.0;

        foreach ($characters as $character) {
            $weightedCount += $this->characterWeight($character);
        }

        return $weightedCount;
    }

    private function characterWeight(string $character): float
    {
        if (preg_match('/\s/u', $character) === 1) {
            return 0.55;
        }

        return match ($character) {
            'm', 'w', 'M', 'W' => 1.3,
            'i', 'l', 'I', 't' => 0.7,
            '.', ',', ':', ';', '!', '?' => 0.5,
            '-', '–', '—' => 0.6,
            default => 1.0,
        };
    }
}
