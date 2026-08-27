<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling;

enum AnswerFlexibility: string
{
    case EXACT = 'exact';
    case SHORT_TEXT = 'short-text';
    case FREE_TEXT = 'free-text';

    public function factor(): float
    {
        return match ($this) {
            self::EXACT => 1.15,
            self::SHORT_TEXT => 1.30,
            self::FREE_TEXT => 1.50,
        };
    }
}
