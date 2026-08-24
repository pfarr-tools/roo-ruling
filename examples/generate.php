<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use PfarrTools\RooRuling\PhpWord\RulingDocument;
use PfarrTools\RooRuling\RulingPreset;

$output = __DIR__.'/output';
if (!is_dir($output)) {
    mkdir($output, 0777, true);
}

$document = new RulingDocument();

foreach (RulingPreset::cases() as $preset) {
    foreach (['docx', 'odt'] as $extension) {
        $path = sprintf('%s/%s.%s', $output, $preset->value, $extension);
        $document->saveReferenceSheet($preset, $path);
        fwrite(STDOUT, "Wrote {$path}\n");
    }
}

foreach (['docx', 'odt'] as $extension) {
    $drawingPath = $output.'/drawing-rulings.'.$extension;
    $document->saveDrawingReferenceSheet($drawingPath);
    fwrite(STDOUT, "Wrote {$drawingPath}\n");
}
