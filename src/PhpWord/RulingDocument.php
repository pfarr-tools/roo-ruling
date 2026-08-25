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

    public function createDrawingReferenceSheet(): PhpWord
    {
        $phpWord = new PhpWord();
        $phpWord->getDocInfo()
            ->setCreator('pfarr-tools/roo-ruling')
            ->setTitle('Handwriting rulings with drawing elements');

        foreach (RulingPreset::cases() as $preset) {
            $section = (new ReferencePageFactory())->create($phpWord, $preset);
            $geometry = $preset->referenceGeometry();

            (new DrawingRulingRenderer())->render(
                section: $section,
                ruling: $preset->definition(),
                leftMm: $geometry->leftMm,
                topMm: $geometry->topMm,
                widthMm: $geometry->widthMm,
                count: $geometry->bands,
            );
        }

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

    public function saveDrawingReferenceSheet(string $filename): void
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($extension, ['docx', 'odt'], true)) {
            throw new \InvalidArgumentException('Only .docx and .odt are supported.');
        }

        IOFactory::createWriter(
            $this->createDrawingReferenceSheet(),
            $extension === 'docx' ? 'Word2007' : 'ODText',
        )->save($filename);
    }
}
