# Gastro Design System

## Ziel

Dieses Design System ist das technische Fundament für den Gastro-POS. Es ist
Dark-Mode-first, touch-orientiert und trennt konsequent zwischen **Raw Tokens**
und **semantischen Tokens**.

Die vorhandene Foundation-Hierarchie bleibt gültig:

```text
Token → Component → Pattern → Template → Page
```

## Verzeichnisstruktur

```text
resources/
├── design/
│   └── tokens/
│       └── tokens.json               # einzige Quelle für Token-Werte
└── css/
    ├── app.css                       # Vite/Tailwind Einstiegspunkt
    ├── foundation-tokens.css         # generiert, nicht manuell pflegen
    └── design-system/
        ├── index.css                 # Import-Reihenfolge
        ├── foundation/
        │   ├── base.css
        │   ├── typography.css
        │   └── controls.css
        ├── components/
        │   └── README.md
        └── patterns/
            └── README.md
```

Spätere Komponenten kommen nach `resources/css/design-system/components/`.
Patterns kommen nach `resources/css/design-system/patterns/`.

## 1. Token-Quelle

Die kanonische Datei ist:

```text
resources/design/tokens/tokens.json
```

Raw Tokens beschreiben reine Werte:

```json
"raw.color.charcoal.950": "#101412",
"raw.color.mustard.500": "#C79A38"
```

Semantische Tokens beschreiben die Funktion:

```json
"bg.main": "var(--raw-color-charcoal-950)",
"accent.active": "var(--raw-color-mustard-500)",
"action.success": "var(--raw-color-green-600)"
```

Komponenten dürfen semantische Tokens verwenden. Raw Tokens sollen innerhalb
von Komponenten nicht direkt angesprochen werden.

## 2. CSS aus Tokens erzeugen

Nach jeder Änderung an `tokens.json`:

```bat
cd C:\xampp\htdocs\kassensystem
php artisan design:token:sync --force
```

Dadurch wird aktualisiert:

```text
resources/css/foundation-tokens.css
```

Danach:

```bat
npm run build
```

## 3. Theme aktivieren

Die Foundations sind absichtlich opt-in, damit das bestehende POS nicht durch
das Anlegen des Design Systems ungeplant komplett umgestaltet wird.

Zum Aktivieren auf einer Surface:

```html
<html lang="de" data-theme="gastro-dark">
```

Für das POS wäre die spätere Aktivierung in:

```text
resources/views/layouts/pos.blade.php
```

Für die Administration separat in:

```text
resources/views/layouts/administration.blade.php
```

So kann der POS zuerst migriert werden, bevor die Administration folgt.

## 4. Semantische Tokens in CSS

Beispiel für eine selbst geschriebene Produktkachel:

```css
.ds-product-tile {
    min-height: var(--touch-comfort);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-lg);
    background: var(--bg-panel);
    color: var(--text-primary);
}

.ds-product-tile[aria-pressed='true'] {
    border-color: var(--accent-active);
    background: var(--accent-active);
    color: var(--text-on-accent);
}
```

Nicht so:

```css
/* vermeiden */
.ds-product-tile {
    background: #161b18;
}
```

## 5. Semantische Tokens mit Tailwind 4

`resources/css/app.css` enthält eine `@theme inline` Bridge. Dadurch stehen
unter anderem diese Utilities zur Verfügung:

```text
bg-pos-bg
bg-pos-panel
bg-pos-panel-raised
bg-pos-nav
bg-pos-secondary
text-pos-text
text-pos-text-muted
border-pos-border
bg-pos-accent
bg-pos-success
bg-pos-danger
```

Beispiel:

```html
<button class="bg-pos-success text-pos-text">
    Bezahlen
</button>
```

Bei eigenen Komponenten ist normales CSS mit `var(--...)` vorzuziehen, wenn
die Komponente viele Zustände besitzt. Tailwind bleibt gut für Layout und
situative Composition.

## 6. Gastro-Ergonomie

Die Foundation setzt:

- 48 px Mindesthöhe für interaktive Controls.
- 56 px bei Geräten mit grobem Pointer/Touch.
- sichtbaren `:focus-visible` Fokus in Ocker.
- `touch-action: manipulation` auf Buttons.
- klare Disabled-Zustände.
- Dark `color-scheme`.
- Reduced-Motion-Unterstützung.
- gut sichtbare Text- und Input-Grundfarben.

Diese Werte sind Defaults. Komponenten dürfen größere Touch-Ziele definieren,
aber sollten `--touch-min` nicht unterschreiten.

## 7. Empfohlene Komponenten-Reihenfolge

Als nächstes selbst implementieren:

1. **Button** – neutral, accent, success, danger; Größen md/lg.
2. **FormField/Input** – Label, Hint, Error, Disabled.
3. **SurfaceCard** – Standard-Panel für POS-Flächen.
4. **StatusBadge** – offen, wartet, bezahlt, Fehler.
5. **ProductTile** – Produktname, Preis, aktiv/disabled.
6. **CategoryTab** – horizontale Touch-Navigation.
7. **QuantityStepper** – Minus, Menge, Plus.
8. **CartLine** – Artikelzeile mit Preis und Stepper.
9. **PaymentAction** – besonders große Success-Aktion.
10. **TableCard** – Tischstatus, Auswahl, offene Summe.
11. **BottomSheet** – Smartphone-Warenkorb/Bezahlung.
12. **Toast/Alert** – Erfolg, Warnung, Fehler.

Für die Foundation-Metadaten kann parallel der vorhandene Generator genutzt
werden:

```bat
php artisan design:make component Button
php artisan design:make component ProductTile
php artisan design:make component TableCard
```

## 8. Benennungsregel

Raw:

```text
--raw-color-<familie>-<stufe>
```

Semantisch:

```text
--bg-*
--text-*
--border-*
--accent-*
--action-*
--input-*
--state-*
```

Komponenten-Tokens erst dann einführen, wenn eine echte Komponente sie
benötigt:

```text
--button-height-lg
--product-tile-min-height
```

Nicht vorab für hypothetische Komponenten erzeugen.

## 9. Prüfen

Nach Token-/Foundation-Änderungen:

```bat
php artisan design:token:sync --force
php artisan design:check
npm run build
php artisan test tests\Unit\DesignSystemTokensTest.php
```

Danach erst die betroffene Surface im Browser und auf dem echten Touch-Gerät
prüfen.
