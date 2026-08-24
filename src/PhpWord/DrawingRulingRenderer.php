<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\PhpWord;

use InvalidArgumentException;
use PhpOffice\PhpWord\Element\Line;
use PhpOffice\PhpWord\Element\Section;
use PfarrTools\RooRuling\RulingDefinition;

final class DrawingRulingRenderer
{
    /**
     * Render a ruling as positioned, editable PHPWord line elements.
     *
     * Coordinates and dimensions are measured from the page's top-left corner in
     * millimetres. The returned elements are also added to the section.
     *
     * @return list<Line>
     */
    public function render(
        Section $section,
        RulingDefinition $ruling,
        float $leftMm,
        float $topMm,
        float $widthMm,
        int $count,
    ): array {
        if ($count < 1) {
            throw new InvalidArgumentException('Count must be at least 1.');
        }

        if ($widthMm <= 0) {
            throw new InvalidArgumentException('Width must be greater than zero.');
        }

        $lines = [];
        $pitchMm = $ruling->pitchMm();

        for ($band = 0; $band < $count; ++$band) {
            $bandTopMm = $topMm + ($band * $pitchMm);
            $zoneTopMm = $bandTopMm;

            foreach ($ruling->zonesMm as $zoneIndex => $zoneHeightMm) {
                if ($ruling->drawsSideBorder($zoneIndex)) {
                    if ($ruling->leftBorder) {
                        $lines[] = $this->addLine(
                            section: $section,
                            ruling: $ruling,
                            leftMm: $leftMm,
                            topMm: $zoneTopMm,
                            widthMm: 0.0,
                            heightMm: $zoneHeightMm,
                        );
                    }

                    if ($ruling->rightBorder) {
                        $lines[] = $this->addLine(
                            section: $section,
                            ruling: $ruling,
                            leftMm: $leftMm + $widthMm,
                            topMm: $zoneTopMm,
                            widthMm: 0.0,
                            heightMm: $zoneHeightMm,
                        );
                    }
                }

                $zoneTopMm += $zoneHeightMm;
            }

            $lineTopMm = $bandTopMm;
            for ($lineIndex = 0; $lineIndex <= count($ruling->zonesMm); ++$lineIndex) {
                $drawTopLine = $lineIndex === 0 && $ruling->topBorder;
                $drawLine = $ruling->drawsLine($lineIndex) && ($lineIndex > 0 || $drawTopLine);

                if ($drawLine) {
                    $lines[] = $this->addLine(
                        section: $section,
                        ruling: $ruling,
                        leftMm: $leftMm,
                        topMm: $lineTopMm,
                        widthMm: $widthMm,
                        heightMm: 0.0,
                    );
                }

                if (isset($ruling->zonesMm[$lineIndex])) {
                    $lineTopMm += $ruling->zonesMm[$lineIndex];
                }
            }
        }

        return $lines;
    }

    private function addLine(
        Section $section,
        RulingDefinition $ruling,
        float $leftMm,
        float $topMm,
        float $widthMm,
        float $heightMm,
    ): Line {
        return $section->addLine([
            'weight' => $ruling->lineSize / 8.0,
            'color' => $ruling->lineColor,
            'width' => UnitConverter::mmToPoints($widthMm),
            'height' => UnitConverter::mmToPoints($heightMm),
            'positioning' => 'absolute',
            'posHorizontal' => 'left',
            'posVertical' => 'top',
            'posHorizontalRel' => 'page',
            'posVerticalRel' => 'page',
            'wrappingStyle' => 'behind',
            'marginLeft' => UnitConverter::mmToPoints($leftMm),
            'marginTop' => UnitConverter::mmToPoints($topMm),
        ]);
    }
}
