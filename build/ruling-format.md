# Ruling geometry and implementation notes

## Terminology

The English stationery/printing term for German **Lineatur** is **ruling**. In this package the more specific phrase **handwriting ruling** is used where useful.

## Source material

The package presets were derived from three supplied legacy Microsoft Word `.doc` files:

- `deutschlinie1a4.doc` — Grade 1
- `deutschlinie2a4.doc` — Grade 2
- `deutschlinie3a4.doc` — Grade 3

The files contain no useful extractable text; the geometry is visual. They were rendered by LibreOffice to PDF and then rasterised at 300 dpi. Horizontal line positions were detected from the rendered page. At 300 dpi, 1 mm corresponds to approximately 11.811 pixels.

## Measured vertical geometry

### Grade 1

Each band has four horizontal lines, producing three zones. Repeating distances between detected lines are approximately:

- 47 px = 3.98 mm
- 58.5 px = 4.95 mm
- 47.5 px = 4.02 mm
- 59.5 px to the next band = 5.04 mm

This is clearly an intended **4 / 5 / 4 mm** band with a **5 mm** inter-band gap.

Reference sheet: 14 bands (56 horizontal lines).

### Grade 2

Repeating distances are approximately:

- 35 px = 2.96 mm
- 47 px = 3.98 mm
- 35.5 px = 3.01 mm
- 24.5 px to the next band = 2.07 mm

This is clearly an intended **3 / 4 / 3 mm** band with a **2 mm** inter-band gap.

Reference sheet: 20 bands (80 horizontal lines).

### Grade 3

Grade 3 uses the same 3 / 4 / 3 mm zones and 2 mm inter-band gap as Grade 2. Only the two horizontal lines enclosing the middle 4 mm zone are drawn. The visible repeating distances are therefore:

- approximately 47 px = 4.0 mm inside the writing band
- approximately 94 px = 8.0 mm to the next visible line

The package therefore uses **3 / 4 / 3 mm** zones and a **2 mm** gap, with only horizontal line indexes **1** and **2** enabled (the boundaries of the middle zone).

Reference sheet: 22 bands (44 horizontal lines).

## Measured reference-page placement

These values are intentionally kept separate from the ruling itself. They reproduce the supplied sheets approximately, but callers can render the same ruling at any width and position.

| Preset | Left offset | Top line | Width | Bands |
|---|---:|---:|---:|---:|
| Grade 1 | ~19.0 mm | ~22.0 mm | ~170.0 mm | 14 |
| Grade 2 | ~15.0 mm | ~29.0 mm | ~178.0 mm | 20 |
| Grade 3 | ~19.9 mm | ~25.7 mm | ~171.0 mm | 22 |

The horizontal measurements come from the LibreOffice render and can differ slightly from the original Word drawing coordinates because the source format is legacy binary `.doc`.

## Why tables instead of drawing shapes

The legacy files visually behave like line drawings. For generated DOCX/ODT, a fixed-layout table is a better abstraction:

- vector lines, no raster degradation;
- editable document content;
- exact row heights in WordprocessingML;
- table borders are supported by PHPWord's DOCX and ODT writers;
- easier to insert into larger generated worksheets;
- geometry stays independent of the output format.

The renderer creates one table. Each writing zone becomes an exact-height row. The first zone receives a top border; every zone receives a bottom border. Left/right borders are repeated on each zone row. The gap is an exact-height borderless row.

## Text placement

Grade 1, 2, and 3 designate the middle zone as the initial text zone. This is sufficient for ordinary typed prompts but is not yet a full handwriting-baseline engine. A future refinement may render sample glyphs across multiple zones so ascenders and descenders can cross zone boundaries naturally.

## Output formats

`RulingDocument` uses PHPWord writers directly:

- `.docx` → `Word2007`
- `.odt` → `ODText`

No LibreOffice conversion is used in production generation.


## Grade 4 and beyond

For Grade 4 and higher, Roo uses a conventional simple ruled line rather than a handwriting zone system. This preset is defined by the application requirement rather than by the supplied legacy Word templates.

- one horizontal baseline every **10 mm**;
- no top line;
- no left or right borders;
- each 10 mm-high row ends in the writing line;
- pitch: **10 mm**.

The preset is exposed as `RulingPreset::Grade4Plus`. Text belongs in the 10 mm row above the baseline.
