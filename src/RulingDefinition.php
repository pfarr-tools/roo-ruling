<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling;

use InvalidArgumentException;

final readonly class RulingDefinition
{
    /**
     * @param non-empty-list<float> $zonesMm Heights between the horizontal ruling lines.
     */
    public function __construct(
        public array $zonesMm,
        public float $gapMm,
        public bool $leftBorder = true,
        public bool $rightBorder = true,
        public bool $topBorder = true,
        public string $lineColor = '808080',
        public int $lineSize = 4,
        public int $textZoneIndex = 0,
    ) {
        if ($this->zonesMm === []) {
            throw new InvalidArgumentException('A ruling needs at least one zone.');
        }

        foreach ($this->zonesMm as $height) {
            if ($height <= 0) {
                throw new InvalidArgumentException('Zone heights must be greater than zero.');
            }
        }

        if ($this->gapMm < 0) {
            throw new InvalidArgumentException('Gap height cannot be negative.');
        }

        if ($this->lineSize <= 0) {
            throw new InvalidArgumentException('Line size must be greater than zero.');
        }

        if ($this->textZoneIndex < 0 || $this->textZoneIndex >= count($this->zonesMm)) {
            throw new InvalidArgumentException('Text zone index is outside the available zones.');
        }
    }

    public function bandHeightMm(): float
    {
        return array_sum($this->zonesMm);
    }

    public function pitchMm(): float
    {
        return $this->bandHeightMm() + $this->gapMm;
    }
}
