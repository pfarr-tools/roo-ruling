# Roo Ruling

`pfarr-tools/roo-ruling` ist ein PHP-Paket zur Erzeugung von Lineaturen für Arbeitsblätter und andere Unterrichtsmaterialien mit [PHPWord](https://github.com/PHPOffice/PHPWord).

Das Paket bildet insbesondere die in der Grundschule verwendeten Schreiblineaturen ab und kann sie sowohl in **DOCX-** als auch in **ODT-Dokumenten** erzeugen.

## Installation

```bash
composer require pfarr-tools/roo-ruling
````

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

Ab Klasse 3 entfällt die Unterteilung in Ober-, Mittel- und Unterbereich.

Die Lineatur besteht aus einem einfachen Schreibbereich:

* Höhe des Schreibbereichs: ca. 3,8 mm
* Abstand zum nächsten Schreibbereich: ca. 7,7 mm

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

Eine Lineatur kann über ein Preset ausgewählt und anschließend mit dem PHPWord-Renderer in einen Abschnitt eingefügt werden.

```php
use PfarrTools\RooRuling\RulingPreset;
use PfarrTools\RooRuling\PhpWord\RulingRenderer;

$ruling = RulingPreset::Grade1->ruling();

$renderer = new RulingRenderer();

$renderer->add(
    section: $section,
    ruling: $ruling,
    count: 5,
);
```

Für Klasse 2:

```php
$ruling = RulingPreset::Grade2->ruling();
```

Für Klasse 3:

```php
$ruling = RulingPreset::Grade3->ruling();
```

Für Klasse 4 und höher:

```php
$ruling = RulingPreset::Grade4Plus->ruling();
```

## Text auf der Lineatur

Die Lineatur ist nicht lediglich eine Hintergrundgrafik. Sie wird mit nativen Dokumentelementen erzeugt.

Dadurch kann Text direkt in die Lineatur eingefügt werden.

Beispiel:

```php
$renderer->add(
    section: $section,
    ruling: RulingPreset::Grade1->ruling(),
    count: 3,
    text: [
        'Gott spricht zu Abraham.',
        'Abraham macht sich auf den Weg.',
        'Gott begleitet ihn.',
    ],
);
```

Damit können beispielsweise Arbeitsblätter mit vorgeschriebenen Wörtern oder Sätzen erzeugt werden.

## Ganze Seiten erzeugen

Für reine Schreibblätter kann eine Seite mit einer Lineatur gefüllt werden:

```php
$renderer->fillPage(
    section: $section,
    ruling: RulingPreset::Grade1->ruling(),
);
```

Die Anzahl der Schreibzeilen bzw. Schreibbänder richtet sich nach der verwendeten Lineatur und der verfügbaren Seitenhöhe.

## DOCX und ODT

Das Paket ist für beide von PHPWord unterstützten Ausgabeformate vorgesehen:

* Microsoft Word (`.docx`)
* OpenDocument Text (`.odt`)

Die Lineaturen werden nicht als Bilder erzeugt. Stattdessen verwendet der Renderer native Tabellen-, Zeilen- und Rahmeninformationen von PHPWord.

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

