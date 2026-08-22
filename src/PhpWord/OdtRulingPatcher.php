<?php

declare(strict_types=1);

namespace PfarrTools\RooRuling\PhpWord;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PfarrTools\RooRuling\RulingDefinition;
use ZipArchive;

/**
 * PHPWord's ODT writer currently drops Cell styles. Add the equivalent ODF
 * cell styles after PHPWord has written the package.
 */
final class OdtRulingPatcher
{
    public static function patch(string $filename, RulingDefinition $ruling, int $count): void
    {
        $zip = new ZipArchive();
        if ($zip->open($filename) !== true) {
            throw new \RuntimeException('Could not open ODT file for ruling border patching.');
        }

        $content = self::load($zip, 'content.xml');
        $styles = self::load($zip, 'styles.xml');
        $contentXPath = new DOMXPath($content);
        $contentXPath->registerNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');

        $cells = $contentXPath->query('//table:table[1]/table:table-row/table:table-cell');
        if ($cells === false || $cells->length === 0) {
            $zip->close();
            return;
        }

        $stylesXPath = new DOMXPath($styles);
        $stylesXPath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $automaticStyles = $stylesXPath->query('/office:document-styles/office:automatic-styles')->item(0);
        if (!$automaticStyles instanceof DOMElement) {
            $zip->close();
            throw new \RuntimeException('ODT styles.xml has no automatic-styles element.');
        }

        $styleNames = [];
        $cellIndex = 0;
        foreach ($cells as $cell) {
            if (!$cell instanceof DOMElement) {
                continue;
            }

            $rowIndex = intdiv($cellIndex, 1);
            $bandRow = $rowIndex % (count($ruling->zonesMm) + ($ruling->gapMm > 0 ? 1 : 0));
            $isGap = $ruling->gapMm > 0 && $bandRow === count($ruling->zonesMm);
            $firstZone = $bandRow === 0;
            $styleName = $isGap ? 'RooRulingGap' : 'RooRulingZone';
            if ($firstZone && $ruling->topBorder) {
                $styleName .= 'Top';
            }
            if ($ruling->leftBorder) {
                $styleName .= 'Left';
            }
            if ($ruling->rightBorder) {
                $styleName .= 'Right';
            }

            if (!isset($styleNames[$styleName])) {
                $styleNames[$styleName] = true;
                self::appendStyle($styles, $automaticStyles, $styleName, $ruling, $isGap, $firstZone);
            }

            $cell->setAttributeNS(
                'urn:oasis:names:tc:opendocument:xmlns:table:1.0',
                'table:style-name',
                $styleName,
            );
            ++$cellIndex;
        }

        $zip->addFromString('content.xml', self::save($content));
        $zip->addFromString('styles.xml', self::save($styles));
        $zip->close();
    }

    private static function load(ZipArchive $zip, string $name): DOMDocument
    {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->loadXML($zip->getFromName($name) ?: '');

        return $document;
    }

    private static function appendStyle(
        DOMDocument $document,
        DOMElement $automaticStyles,
        string $name,
        RulingDefinition $ruling,
        bool $isGap,
        bool $firstZone,
    ): void {
        $style = $document->createElementNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'style:style');
        $style->setAttribute('style:name', $name);
        $style->setAttribute('style:family', 'table-cell');
        $properties = $document->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'style:table-cell-properties',
        );

        if (!$isGap) {
            $border = number_format($ruling->lineSize / 20, 3, '.', '').'pt solid #'.$ruling->lineColor;
            $properties->setAttributeNS('http://www.w3.org/1999/XSL/Format', 'fo:border-bottom', $border);
            if ($firstZone && $ruling->topBorder) {
                $properties->setAttributeNS('http://www.w3.org/1999/XSL/Format', 'fo:border-top', $border);
            }
            if ($ruling->leftBorder) {
                $properties->setAttributeNS('http://www.w3.org/1999/XSL/Format', 'fo:border-left', $border);
            }
            if ($ruling->rightBorder) {
                $properties->setAttributeNS('http://www.w3.org/1999/XSL/Format', 'fo:border-right', $border);
            }
        }

        $style->appendChild($properties);
        $automaticStyles->appendChild($style);
    }

    private static function save(DOMDocument $document): string
    {
        return $document->saveXML() ?: '';
    }
}
