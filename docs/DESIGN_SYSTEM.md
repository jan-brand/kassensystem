# Designsystem

## Hierarchie

```text
Token → Component → Pattern → Template → Page
```

Layouts stehen quer dazu und definieren den äußeren Rahmen einer Surface.

## Tokens

Tokens sind atomare Designwerte: Farbe, Typografie, Abstand, Radius, Schatten, Breakpoints oder Animationen. Sie dürfen keine fachliche Bedeutung wie `invoice-overdue-red` tragen, sondern sollten semantisch und wiederverwendbar sein.

```bash
php artisan design:token:make color.primary '#0f172a'
php artisan design:token:make spacing.section '4rem'
php artisan design:token:sync
```

`design:token:sync` erzeugt `resources/css/foundation-tokens.css`.

## Components

Components sind die kleinsten wiederverwendbaren Bausteine. Beispiele: Button, Input, Badge, Alert, Modal, Card. Varianten gehören in dieselbe Component statt in neue Komponenten wie `PrimaryButton` und `DangerButton`.

```bash
php artisan design:make component Button
```

## Patterns

Patterns kombinieren Components zu wiederkehrenden Strukturen, z. B. `PageHeader`, `SearchAndFilter`, `EmptyState` oder `FormActions`.

```bash
php artisan design:make pattern PageHeader --uses=Button
```

## Templates

Templates definieren abstrakte Seitentypen, z. B. `AdministrationList`, `Detail`, `Form`, `Dashboard`. Sie enthalten keine konkrete Fachlichkeit.

```bash
php artisan design:make template AdministrationList --uses=PageHeader
```

## Prüfung

```bash
php artisan design:check
php artisan design:graph
php artisan design:uses Button
php artisan design:unused
```

`design:check` prüft insbesondere fehlende Referenzen, ungültige Hierarchierichtungen, Template-Verschachtelung und zyklische Designabhängigkeiten.

## Status

Manifeste können z. B. `experimental`, `stable` oder `deprecated` verwenden. Der Status ist bewusst Metadatum; projektspezifische Release-Regeln können später darauf aufbauen.

## Accessibility

Generatoren erzeugen absichtlich nur neutrale Grundgerüste. Bei echten Components gehören Fokuszustände, Keyboard-Navigation, Labels, ARIA nur bei tatsächlicher Notwendigkeit, Kontrast und Fehlermeldungen in die fachliche Implementierung und Dokumentation. Das Designsystem soll Semantik nicht durch rein visuelle Komponenten ersetzen.
