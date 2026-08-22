<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\PhpWord;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PfarrTools\RooRuling\RulingPreset;

final class RulingDocument
{
    public function createReferenceSheet(RulingPreset $preset): PhpWord
    {
        $phpWord = new PhpWord();
        $phpWord->getDocInfo()
            ->setCreator('pfarr-tools/roo-ruling')
            ->setTitle('Handwriting ruling '.$preset->value);

        $section = (new ReferencePageFactory())->create($phpWord, $preset);
        $geometry = $preset->referenceGeometry();

        (new RulingRenderer())->render(
            section: $section,
            ruling: $preset->definition(),
            count: $geometry->bands,
            widthMm: $geometry->widthMm,
        );

        return $phpWord;
    }

    public function saveReferenceSheet(RulingPreset $preset, string $filename): void
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $writerType = match ($extension) {
            'docx' => 'Word2007',
            'odt' => 'ODText',
            default => throw new \InvalidArgumentException('Only .docx and .odt are supported.'),
        };

        IOFactory::createWriter($this->createReferenceSheet($preset), $writerType)->save($filename);
    }
}
