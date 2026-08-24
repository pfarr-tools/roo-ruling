# Roo Ruling

`pfarr-tools/roo-ruling` ist ein PHP-Paket zur Erzeugung von Lineaturen für Arbeitsblätter und andere Unterrichtsmaterialien mit [PHPWord](https://github.com/PHPOffice/PHPWord).

Das Paket bildet insbesondere die in der Grundschule verwendeten Schreiblineaturen ab und kann sie sowohl in **DOCX-** als auch in **ODT-Dokumenten** erzeugen.

## Installation

```bash
composer require pfarr-tools/roo-ruling
```

Voraussetzung ist PHP 8.3 oder neuer.

Das Paket verwendet `phpoffice/phpword` zur Dokumentenerzeugung.

## Unterstützte Lineaturen

### Klasse 1

Die Lineatur besteht aus vier Linien und damit drei Schreibbereichen:

* Oberer Bereich: 4 mm
* Mittlerer Bereich: 5 mm
* Unterer Bereich: 4 mm
* Abstand zur nächsten Lineatur: 5 mm

Die Lineatur entspricht damit dem Schema:

```text
──────────────────────────────
          4 mm
──────────────────────────────
          5 mm
──────────────────────────────
          4 mm
──────────────────────────────

          5 mm Abstand
```

### Klasse 2

Die Lineatur verwendet ebenfalls vier Linien, ist aber kleiner:

* Oberer Bereich: 3 mm
* Mittlerer Bereich: 4 mm
* Unterer Bereich: 3 mm
* Abstand zur nächsten Lineatur: 2 mm

```text
──────────────────────────────
          3 mm
──────────────────────────────
          4 mm
──────────────────────────────
          3 mm
──────────────────────────────

          2 mm Abstand
```

### Klasse 3

Klasse 3 verwendet dieselben Maße wie Klasse 2, aber es werden nur die beiden Linien gezeichnet, die den mittleren Schreibbereich einschließen:

* oberer Bereich: 3 mm, ohne Linie
* mittlerer Bereich: 4 mm, obere und untere Linie
* unterer Bereich: 3 mm, ohne Linie
* Abstand zur nächsten Lineatur: 2 mm
* seitliche Begrenzungslinien nur am mittleren Bereich

### Klasse 4 und höher

Ab Klasse 4 wird eine einfache Schreiblinie verwendet.

* Eine horizontale Linie pro Schreibzeile
* Linienabstand: 10 mm
* Keine zusätzlichen Hilfslinien
* Keine seitlichen Begrenzungslinien

```text
Text steht auf dieser Linie
──────────────────────────────
          10 mm
Text steht auf dieser Linie
──────────────────────────────
          10 mm
Text steht auf dieser Linie
──────────────────────────────
```

## Verwendung

In einem eigenen Projekt wird zunächst ein PHPWord-Abschnitt angelegt. Ein Preset liefert die Geometrie als `RulingDefinition`; der Renderer fügt daraus eine native Tabelle mit exakten Zeilenhöhen und Rahmenlinien ein.

```php
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PfarrTools\RooRuling\RulingPreset;
use PfarrTools\RooRuling\PhpWord\RulingRenderer;

$phpWord = new PhpWord();
$section = $phpWord->addSection();

$preset = RulingPreset::Grade1;
(new RulingRenderer())->render(
    section: $section,
    ruling: $preset->definition(),
    count: 5,
    widthMm: 170.0,
);

IOFactory::createWriter($phpWord, 'Word2007')->save(__DIR__.'/ruling.docx');
```

Für Lineaturen aus einzelnen, frei positionierbaren PHPWord-Zeichenelementen
steht `DrawingRulingRenderer` zur Verfügung. Die Koordinaten werden vom
Seitenrand oben links aus in Millimetern angegeben:

```php
use PfarrTools\RooRuling\PhpWord\DrawingRulingRenderer;

(new DrawingRulingRenderer())->render(
    section: $section,
    ruling: RulingPreset::Grade3->definition(),
    leftMm: 19.9,
    topMm: 25.7,
    widthMm: 171.0,
    count: 22,
);
```

Der Renderer verwendet native PHPWord-Linienelemente mit derselben
Linienfarbe und -stärke wie der Tabellenrenderer. Die Zonen und Abstände der
Definition bleiben unverändert; bei Klasse 3 liegt die erste sichtbare Linie
deshalb erst 3 mm unterhalb der angegebenen oberen Koordinate.

Für ODT wird die von PHPWord geschriebene Datei anschließend um native
ODF-`draw:line`-Elemente und deren Grafikstile ergänzt. Dadurch bleiben die
Linien auch dort als Zeichenelemente bearbeitbar.

`widthMm` und alle Maße der Definition werden in Millimetern angegeben. Die vier verfügbaren Presets sind:

```php
RulingPreset::Grade1;
RulingPreset::Grade2;
RulingPreset::Grade3;
RulingPreset::Grade4Plus;
```

### Höhe einer Lineatur berechnen

Mit `heightMm()` lässt sich die Gesamthöhe einer gerenderten Lineatur in
Millimetern berechnen. Der Abstand zwischen zwei Schreibbereichen wird dabei
nur zwischen den Bereichen berücksichtigt, nicht hinter dem letzten Bereich:

```php
$heightMm = RulingPreset::Grade1->definition()->heightMm(5);
// 5 * 13 mm Schreibbereiche + 4 * 5 mm Zwischenräume = 85 mm
```

Die Anzahl muss mindestens `1` betragen.

## Text auf der Lineatur

Die Lineatur ist nicht lediglich eine Hintergrundgrafik. Sie wird mit nativen Dokumentelementen erzeugt.

Dadurch kann Text direkt in die Lineatur eingefügt werden.

Beispiel:

```php
(new RulingRenderer())->render(
    section: $section,
    ruling: RulingPreset::Grade1->definition(),
    count: 3,
    widthMm: 170.0,
    textByBand: [
        'Gott spricht zu Abraham.',
        'Abraham macht sich auf den Weg.',
        'Gott begleitet ihn.',
    ],
);
```

Damit können beispielsweise Arbeitsblätter mit vorgeschriebenen Wörtern oder Sätzen erzeugt werden.

## DOCX und ODT

Das Paket ist für beide von PHPWord unterstützten Ausgabeformate vorgesehen:

* Microsoft Word (`.docx`)
* OpenDocument Text (`.odt`)

Die Lineaturen werden nicht als Bilder erzeugt. Stattdessen verwendet der Renderer native Tabellen-, Zeilen- und Rahmeninformationen von PHPWord.

Für ODT-Dateien wird das Dokument zunächst mit PHPWord gespeichert und anschließend mit `OdtRulingPatcher::patch()` ergänzt. PHPWord 1.4 schreibt Zellrahmen und exakte Zeilenhöhen beim ODT-Export nicht vollständig mit; der Patcher ergänzt gültige, editierbare ODF-Zell- und Zeilenstile.

```php
use PfarrTools\RooRuling\PhpWord\OdtRulingPatcher;

$filename = __DIR__.'/ruling.odt';
IOFactory::createWriter($phpWord, 'ODText')->save($filename);
OdtRulingPatcher::patch($filename, $preset->definition(), count: 5);
```

Wenn mehrere Lineaturtabellen in einem ODT enthalten sind, können sie in einem Schritt gepatcht werden:

```php
OdtRulingPatcher::patchTables(
    filename: $filename,
    rulings: [RulingPreset::Grade1->definition(), RulingPreset::Grade2->definition()],
    counts: [5, 5],
);
```

Für die fertigen Referenzseiten des Pakets kann alternativ `RulingDocument::saveReferenceSheet()` verwendet werden. Diese Methode erzeugt jeweils ein vollständiges Dokument für ein Preset.

Dadurch bleiben die Dokumente bearbeitbar und die Lineaturen können mit anderen PHPWord-Inhalten kombiniert werden.

## Maßeinheiten

Die Definitionen der Lineaturen verwenden Millimeter.

Bei der Ausgabe rechnet das Paket diese intern in die von Word verwendeten Twips um:

```text
1 inch = 25,4 mm
1 inch = 1440 Twips
```

Die Umrechnung erfolgt zentral über den `UnitConverter`.

## Eigene Lineaturen

Die mitgelieferten Klassenstufen sind lediglich Presets.

Die eigentliche Lineatur ist unabhängig von einer Klassenstufe definiert. Dadurch können weitere Lineaturen ergänzt werden, ohne den PHPWord-Renderer zu verändern.

Beispielsweise können künftig zusätzliche Varianten unterstützt werden:

* individuelle Schreiblineaturen
* einfache Linien mit anderen Abständen
* besondere Lineaturen für Fördermaterial
* karierte Raster
* weitere schul- oder landesspezifische Lineaturen

Die Trennung zwischen **Lineaturdefinition** und **Renderer** ist bewusst gewählt: Eine neue Lineatur sollte im Regelfall lediglich eine neue Definition benötigen.

## Referenzdokumente

Unter `examples/` befindet sich ein Generator für Referenzdokumente.

Nach der Installation der Composer-Abhängigkeiten können damit DOCX- und ODT-Dateien für die verschiedenen Lineaturen erzeugt werden.

```bash
composer install
php examples/generate.php
```

Das Skript erzeugt zusätzlich `examples/output/drawing-rulings.docx` und
`examples/output/drawing-rulings.odt` mit allen vier Lineaturtypen als
Zeichenelemente.

Diese Dokumente können anschließend beispielsweise mit Microsoft Word oder LibreOffice geöffnet und mit den ursprünglichen Vorlagen verglichen werden.

## Entwicklung

Tests können mit PHPUnit ausgeführt werden:

```bash
composer test
```

Vor Änderungen an der Geometrie einer Lineatur sollten insbesondere die erzeugten DOCX- und ODT-Referenzdokumente kontrolliert werden.

Die beiden Ausgabeformate können sich bei Tabellenhöhen, Absatzabständen und Rahmen geringfügig unterschiedlich verhalten. Der Renderer soll deshalb keine formatspezifischen Annahmen treffen, solange diese nicht notwendig sind.

Weitere Informationen zur internen Modellierung und zu den ermittelten Maßen befinden sich unter:

```text
build/ruling-format.md
```

Die weitere Entwicklungsplanung befindet sich unter:

```text
build/masterplan.md
```

## Lizenz

Copyright © Pfarr.Tools

Dieses Projekt ist freie Software und steht unter der **GNU General Public License Version 3 oder – nach Ihrer Wahl – jeder späteren Version (GPL-3.0-or-later)**.

Sie dürfen das Programm unter den Bedingungen der GNU General Public License, wie sie von der Free Software Foundation veröffentlicht wurde, weitergeben und/oder verändern; entweder gemäß Version 3 der Lizenz oder (nach Ihrer Wahl) jeder späteren Version.

Dieses Programm wird in der Hoffnung veröffentlicht, dass es nützlich sein wird, jedoch **ohne jede Gewährleistung**; auch ohne die implizite Gewährleistung der **Marktreife** oder der **Eignung für einen bestimmten Zweck**.

Den vollständigen Lizenztext finden Sie in der Datei [`LICENSE`](LICENSE).
