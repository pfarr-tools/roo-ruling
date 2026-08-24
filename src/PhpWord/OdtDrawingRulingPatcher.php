<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\PhpWord;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use PfarrTools\RooRuling\ReferenceGeometry;
use PfarrTools\RooRuling\RulingDefinition;
use RuntimeException;
use ZipArchive;

/**
 * Adds ODF drawing lines after PHPWord's ODText writer has written the archive.
 */
final class OdtDrawingRulingPatcher
{
    /**
     * Patch one drawing-ruling page per supplied ruling and reference geometry.
     *
     * @param list<RulingDefinition> $rulings
     * @param list<ReferenceGeometry> $geometries
     */
    public static function patchReferenceSheet(string $filename, array $rulings, array $geometries): void
    {
        if ($rulings === [] || count($rulings) !== count($geometries)) {
            throw new InvalidArgumentException('Rulings and geometries must be non-empty lists of equal length.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filename) !== true) {
            throw new RuntimeException('Could not open ODT file for drawing-ruling patching.');
        }

        $content = self::load($zip, 'content.xml');
        $xpath = new DOMXPath($content);
        $namespaces = [
            'office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
            'text' => 'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
        ];
        foreach ($namespaces as $prefix => $uri) {
            $xpath->registerNamespace($prefix, $uri);
        }

        $sections = $xpath->query('//text:section');
        if ($sections === false || $sections->length < count($rulings)) {
            $zip->close();
            throw new RuntimeException('ODT does not contain enough sections for drawing rulings.');
        }

        $automaticStyles = $xpath->query('/office:document-content/office:automatic-styles')->item(0);
        if (!$automaticStyles instanceof DOMElement) {
            $zip->close();
            throw new RuntimeException('ODT content.xml has no automatic-styles element.');
        }

        foreach ($rulings as $index => $ruling) {
            $section = $sections->item($index);
            if (!$section instanceof DOMElement) {
                continue;
            }

            $paragraph = $xpath->query('./text:p', $section)->item(0);
            if (!$paragraph instanceof DOMElement) {
                $zip->close();
                throw new RuntimeException('ODT drawing-ruling section has no anchor paragraph.');
            }

            $styleName = 'RooDrawingLine'.($index + 1);
            self::appendLineStyle($content, $automaticStyles, $styleName, $ruling);
            foreach (self::segments($ruling, $geometries[$index]->widthMm, $geometries[$index]->bands) as $lineIndex => $segment) {
                self::appendLine($content, $paragraph, $styleName, $index, $lineIndex, $segment);
            }
        }

        $zip->addFromString('content.xml', self::save($content));
        $zip->close();
    }

    /**
     * @return list<array{x: float, y: float, width: float, height: float}>
     */
    private static function segments(RulingDefinition $ruling, float $widthMm, int $count): array
    {
        $segments = [];
        $pitchMm = $ruling->pitchMm();

        for ($band = 0; $band < $count; ++$band) {
            $bandTopMm = $band * $pitchMm;
            $zoneTopMm = $bandTopMm;

            foreach ($ruling->zonesMm as $zoneIndex => $zoneHeightMm) {
                if ($ruling->drawsSideBorder($zoneIndex)) {
                    if ($ruling->leftBorder) {
                        $segments[] = [
                            'x' => 0.0,
                            'y' => $zoneTopMm,
                            'width' => 0.0,
                            'height' => $zoneHeightMm,
                        ];
                    }
                    if ($ruling->rightBorder) {
                        $segments[] = [
                            'x' => $widthMm,
                            'y' => $zoneTopMm,
                            'width' => 0.0,
                            'height' => $zoneHeightMm,
                        ];
                    }
                }
                $zoneTopMm += $zoneHeightMm;
            }

            $lineTopMm = $bandTopMm;
            for ($lineIndex = 0; $lineIndex <= count($ruling->zonesMm); ++$lineIndex) {
                $drawTopLine = $lineIndex === 0 && $ruling->topBorder;
                if ($ruling->drawsLine($lineIndex) && ($lineIndex > 0 || $drawTopLine)) {
                    $segments[] = [
                        'x' => 0.0,
                        'y' => $lineTopMm,
                        'width' => $widthMm,
                        'height' => 0.0,
                    ];
                }
                if (isset($ruling->zonesMm[$lineIndex])) {
                    $lineTopMm += $ruling->zonesMm[$lineIndex];
                }
            }
        }

        return $segments;
    }

    private static function appendLineStyle(
        DOMDocument $document,
        DOMElement $automaticStyles,
        string $name,
        RulingDefinition $ruling,
    ): void {
        $styleUri = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
        $drawUri = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
        $svgUri = 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0';

        $style = $document->createElementNS($styleUri, 'style:style');
        $style->setAttributeNS($styleUri, 'style:name', $name);
        $style->setAttributeNS($styleUri, 'style:family', 'graphic');
        $properties = $document->createElementNS($styleUri, 'style:graphic-properties');
        $properties->setAttributeNS($drawUri, 'draw:stroke', 'solid');
        $properties->setAttributeNS($drawUri, 'draw:fill', 'none');
        $properties->setAttributeNS($svgUri, 'svg:stroke-color', '#'.$ruling->lineColor);
        $properties->setAttributeNS($svgUri, 'svg:stroke-width', number_format($ruling->lineSize / 8, 3, '.', '').'pt');
        $style->appendChild($properties);
        $automaticStyles->appendChild($style);
    }

    /** @param array{x: float, y: float, width: float, height: float} $segment */
    private static function appendLine(
        DOMDocument $document,
        DOMElement $paragraph,
        string $styleName,
        int $rulingIndex,
        int $lineIndex,
        array $segment,
    ): void {
        $drawUri = 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0';
        $textUri = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
        $svgUri = 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0';

        $line = $document->createElementNS($drawUri, 'draw:line');
        $line->setAttributeNS($textUri, 'text:anchor-type', 'paragraph');
        $line->setAttributeNS($drawUri, 'draw:z-index', '0');
        $line->setAttributeNS($drawUri, 'draw:name', 'RooDrawingLine'.($rulingIndex + 1).'_'.$lineIndex);
        $line->setAttributeNS($drawUri, 'draw:style-name', $styleName);
        $line->setAttributeNS($svgUri, 'svg:x1', number_format($segment['x'] / 10, 4, '.', '').'cm');
        $line->setAttributeNS($svgUri, 'svg:y1', number_format($segment['y'] / 10, 4, '.', '').'cm');
        $line->setAttributeNS($svgUri, 'svg:x2', number_format(($segment['x'] + $segment['width']) / 10, 4, '.', '').'cm');
        $line->setAttributeNS($svgUri, 'svg:y2', number_format(($segment['y'] + $segment['height']) / 10, 4, '.', '').'cm');
        $paragraph->appendChild($line);
    }

    private static function load(ZipArchive $zip, string $name): DOMDocument
    {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->loadXML($zip->getFromName($name) ?: '');

        return $document;
    }

    private static function save(DOMDocument $document): string
    {
        return $document->saveXML() ?: '';
    }
}
