<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\PhpWord;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PfarrTools\RooRuling\RulingPreset;

final class ReferencePageFactory
{
    public function create(PhpWord $phpWord, RulingPreset $preset): Section
    {
        $geometry = $preset->referenceGeometry();
        $rightMm = max(0.0, 210.0 - $geometry->leftMm - $geometry->widthMm);

        return $phpWord->addSection([
            'pageSizeW' => UnitConverter::mmToTwips(210.0),
            'pageSizeH' => UnitConverter::mmToTwips(297.0),
            'marginLeft' => UnitConverter::mmToTwips($geometry->leftMm),
            'marginRight' => UnitConverter::mmToTwips($rightMm),
            'marginTop' => UnitConverter::mmToTwips($geometry->topMm),
            'marginBottom' => UnitConverter::mmToTwips(10.0),
        ]);
    }
}
