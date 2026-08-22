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
        self::patchTables($filename, [$ruling], [$count]);
    }

    /**
     * Patch the first tables in an ODT with the supplied ruling definitions.
     *
     * @param list<RulingDefinition> $rulings
     * @param list<int> $counts
     */
    public static function patchTables(string $filename, array $rulings, array $counts): void
    {
        if (count($rulings) !== count($counts) || $rulings === []) {
            throw new \InvalidArgumentException('Rulings and counts must be non-empty lists of equal length.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filename) !== true) {
            throw new \RuntimeException('Could not open ODT file for ruling border patching.');
        }

        $content = self::load($zip, 'content.xml');
        $styles = self::load($zip, 'styles.xml');
        $contentXPath = new DOMXPath($content);
        $contentXPath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $contentXPath->registerNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');

        $tables = $contentXPath->query('//table:table');
        if ($tables === false || $tables->length < count($rulings)) {
            $zip->close();
            throw new \RuntimeException('ODT does not contain enough ruling tables to patch.');
        }

        $automaticStyles = $contentXPath->query('/office:document-content/office:automatic-styles')->item(0);
        if (!$automaticStyles instanceof DOMElement) {
            $zip->close();
            throw new \RuntimeException('ODT content.xml has no automatic-styles element.');
        }

        foreach ($rulings as $tableIndex => $ruling) {
            $table = $tables->item($tableIndex);
            if (!$table instanceof DOMElement) {
                continue;
            }

            self::patchTable(
                content: $content,
                table: $table,
                automaticStyles: $automaticStyles,
                ruling: $ruling,
                prefix: count($rulings) === 1 ? 'RooRuling' : 'RooRuling'.($tableIndex + 1),
            );
        }

        $zip->addFromString('content.xml', self::save($content));
        $zip->addFromString('styles.xml', self::save($styles));
        $zip->close();
    }

    private static function patchTable(
        DOMDocument $content,
        DOMElement $table,
        DOMElement $automaticStyles,
        RulingDefinition $ruling,
        string $prefix,
    ): void {
        $rows = [];
        foreach ($table->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'table-row') {
                $rows[] = $child;
            }
        }

        $styleNames = [];
        foreach ($rows as $rowIndex => $row) {
            $bandRow = $rowIndex % (count($ruling->zonesMm) + ($ruling->gapMm > 0 ? 1 : 0));
            $isGap = $ruling->gapMm > 0 && $bandRow === count($ruling->zonesMm);
            $firstZone = $bandRow === 0;
            $heightMm = $isGap ? $ruling->gapMm : $ruling->zonesMm[$bandRow];

            $rowStyleName = $prefix.($isGap ? 'GapRow' : 'ZoneRow').str_replace('.', '_', (string) $heightMm);
            if (!isset($styleNames[$rowStyleName])) {
                $styleNames[$rowStyleName] = true;
                self::appendRowStyle($content, $automaticStyles, $rowStyleName, $heightMm);
            }
            $row->setAttributeNS(
                'urn:oasis:names:tc:opendocument:xmlns:table:1.0',
                'table:style-name',
                $rowStyleName,
            );

            foreach ($row->childNodes as $cell) {
                if (!$cell instanceof DOMElement || $cell->localName !== 'table-cell') {
                    continue;
                }

                $styleName = $prefix.($isGap ? 'Gap' : 'Zone');
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
                    self::appendStyle($content, $automaticStyles, $styleName, $ruling, $isGap, $firstZone);
                }
                $cell->setAttributeNS(
                    'urn:oasis:names:tc:opendocument:xmlns:table:1.0',
                    'table:style-name',
                    $styleName,
                );
            }
        }
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
            $foNamespace = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
            $properties->setAttributeNS($foNamespace, 'fo:border-bottom', $border);
            if ($firstZone && $ruling->topBorder) {
                $properties->setAttributeNS($foNamespace, 'fo:border-top', $border);
            }
            if ($ruling->leftBorder) {
                $properties->setAttributeNS($foNamespace, 'fo:border-left', $border);
            }
            if ($ruling->rightBorder) {
                $properties->setAttributeNS($foNamespace, 'fo:border-right', $border);
            }
        }

        $style->appendChild($properties);
        $automaticStyles->appendChild($style);
    }

    private static function appendRowStyle(
        DOMDocument $document,
        DOMElement $automaticStyles,
        string $name,
        float $heightMm,
    ): void {
        $style = $document->createElementNS('urn:oasis:names:tc:opendocument:xmlns:style:1.0', 'style:style');
        $style->setAttribute('style:name', $name);
        $style->setAttribute('style:family', 'table-row');
        $properties = $document->createElementNS(
            'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
            'style:table-row-properties',
        );
        $properties->setAttribute('style:row-height', number_format($heightMm / 10, 4, '.', '').'cm');
        $properties->setAttribute('style:use-optimal-row-height', 'false');
        $style->appendChild($properties);
        $automaticStyles->appendChild($style);
    }

    private static function save(DOMDocument $document): string
    {
        return $document->saveXML() ?: '';
    }
}
