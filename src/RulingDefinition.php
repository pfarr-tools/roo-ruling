<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling;

use InvalidArgumentException;

final readonly class RulingDefinition
{
    /**
     * @param non-empty-list<float> $zonesMm Heights between the horizontal ruling lines.
     * @param list<int> $lineIndexes Horizontal lines to draw, numbered from 0 at the top.
     * @param list<int> $sideBorderZoneIndexes Zones receiving left/right borders.
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
        public array $lineIndexes = [],
        public array $sideBorderZoneIndexes = [],
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

        foreach ($this->lineIndexes as $lineIndex) {
            if ($lineIndex < 0 || $lineIndex > count($this->zonesMm)) {
                throw new InvalidArgumentException('A line index is outside the available horizontal lines.');
            }
        }

        foreach ($this->sideBorderZoneIndexes as $zoneIndex) {
            if ($zoneIndex < 0 || $zoneIndex >= count($this->zonesMm)) {
                throw new InvalidArgumentException('A side-border zone index is outside the available zones.');
            }
        }
    }

    public function drawsLine(int $lineIndex): bool
    {
        return $this->lineIndexes === [] || in_array($lineIndex, $this->lineIndexes, true);
    }

    public function drawsSideBorder(int $zoneIndex): bool
    {
        return $this->sideBorderZoneIndexes === [] || in_array($zoneIndex, $this->sideBorderZoneIndexes, true);
    }

    public function bandHeightMm(): float
    {
        return array_sum($this->zonesMm);
    }

    public function pitchMm(): float
    {
        return $this->bandHeightMm() + $this->gapMm;
    }

    /**
     * Return the total height of a rendered number of writing bands in millimetres.
     *
     * Gaps are included only between bands, matching the renderer's output.
     */
    public function heightMm(int $count): float
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Count must be at least 1.');
        }

        return ($count * $this->bandHeightMm()) + (($count - 1) * $this->gapMm);
    }
}
