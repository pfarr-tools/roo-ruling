<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\PhpWord;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PfarrTools\RooRuling\RulingDefinition;

final class RulingRenderer
{
    /**
     * Render a number of handwriting bands into a section.
     *
     * @param list<string|null> $textByBand Optional text, one entry per band. Text is placed
     *                                      in the definition's text zone.
     */
    public function render(
        Section $section,
        RulingDefinition $ruling,
        int $count,
        float $widthMm,
        array $textByBand = [],
        array $fontStyle = [],
        array $paragraphStyle = [],
    ): Table {
        if ($count < 1) {
            throw new \InvalidArgumentException('Count must be at least 1.');
        }

        if ($widthMm <= 0) {
            throw new \InvalidArgumentException('Width must be greater than zero.');
        }

        $widthTwips = UnitConverter::mmToTwips($widthMm);

        $table = $section->addTable([
            'width' => $widthTwips,
            'unit' => TblWidth::TWIP,
            'layout' => 'fixed',
            'cellMarginTop' => 0,
            'cellMarginRight' => 0,
            'cellMarginBottom' => 0,
            'cellMarginLeft' => 0,
        ]);

        for ($band = 0; $band < $count; ++$band) {
            foreach ($ruling->zonesMm as $zoneIndex => $heightMm) {
                $row = $table->addRow(UnitConverter::mmToTwips($heightMm), [
                    'exactHeight' => true,
                    'cantSplit' => true,
                ]);

                $cell = $row->addCell($widthTwips, $this->zoneCellStyle(
                    ruling: $ruling,
                    firstZone: $zoneIndex === 0,
                ));

                if ($zoneIndex === $ruling->textZoneIndex && array_key_exists($band, $textByBand)) {
                    $text = $textByBand[$band];
                    if ($text !== null && $text !== '') {
                        $cell->addText($text, $fontStyle, array_replace([
                            'spaceBefore' => 0,
                            'spaceAfter' => 0,
                        ], $paragraphStyle));
                    }
                }
            }

            if ($band < $count - 1 && $ruling->gapMm > 0) {
                $gapRow = $table->addRow(UnitConverter::mmToTwips($ruling->gapMm), [
                    'exactHeight' => true,
                    'cantSplit' => true,
                ]);
                $gapRow->addCell($widthTwips, [
                    'borderTopSize' => 0,
                    'borderRightSize' => 0,
                    'borderBottomSize' => 0,
                    'borderLeftSize' => 0,
                ]);
            }
        }

        return $table;
    }

    /** @return array<string, int|string> */
    private function zoneCellStyle(RulingDefinition $ruling, bool $firstZone): array
    {
        $style = [
            'borderBottomSize' => $ruling->lineSize,
            'borderBottomColor' => $ruling->lineColor,
        ];

        if ($firstZone && $ruling->topBorder) {
            $style['borderTopSize'] = $ruling->lineSize;
            $style['borderTopColor'] = $ruling->lineColor;
        }

        if ($ruling->leftBorder) {
            $style['borderLeftSize'] = $ruling->lineSize;
            $style['borderLeftColor'] = $ruling->lineColor;
        }

        if ($ruling->rightBorder) {
            $style['borderRightSize'] = $ruling->lineSize;
            $style['borderRightColor'] = $ruling->lineColor;
        }

        return $style;
    }
}
