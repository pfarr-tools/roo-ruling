# AGENTS.md

## Zweck

Dieses Paket erzeugt Lineaturen (engl. *rulings*) für mit PHPWord erzeugte DOCX- und ODT-Dokumente. Es ist bewusst unabhängig von Roo und kann als Composer-Paket wiederverwendet werden.

## Grundregeln

- Produktionscode unter `src/`, Tests unter `tests/`.
- Öffentliche APIs bleiben klein und semantisch: Lineatur-Geometrie wird nicht mit PHPWord-spezifischen Details vermischt.
- Maße werden intern in Millimetern beschrieben und erst im Renderer in Twips umgerechnet.
- Keine Rastergrafiken oder Hintergrundbilder als Lineatur-Ersatz. Die Lineatur muss vektorbasiert und in DOCX/ODT editierbar bleiben.
- Der PHPWord-Renderer verwendet Tabellen mit exakten Zeilenhöhen und gezielt gesetzten Rahmenlinien.
- Neue Lineaturen werden als `RulingDefinition` modelliert; schulstufenspezifische Namen sind Presets, keine Sonderlogik im Renderer.
- DOCX und ODT müssen beide unterstützt bleiben.
- Änderungen an der Geometrie brauchen einen Regressionstest.

## Qualität

Vor einem Release:

1. `composer install`
2. `composer test`
3. `php examples/generate.php`
4. DOCX und ODT in LibreOffice rendern und visuell mit den Referenzen vergleichen.
5. Geometrieabweichungen in `build/ruling-format.md` dokumentieren.
