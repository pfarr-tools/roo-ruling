<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling;

/**
 * Geometry measured from the supplied legacy .doc files after rendering at 300 dpi.
 * These values describe the reference sheets, not a hard requirement for arbitrary use.
 */
final readonly class ReferenceGeometry
{
    public function __construct(
        public float $leftMm,
        public float $topMm,
        public float $widthMm,
        public int $bands,
    ) {
    }
}
