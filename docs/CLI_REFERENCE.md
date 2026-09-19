# CLI Reference

Diese Referenz beschreibt die in Version 1 implementierten Foundation-Befehle. Laravel-eigene Befehle bleiben unverändert verfügbar.

## Application

```text
app:init              Foundation initialisieren/auffrischen
app:configure         Projektdefaults interaktiv konfigurieren
app:doctor            lokale Entwicklungsumgebung diagnostizieren
app:check             zentrale Foundation-Prüfungen ausführen
app:info              technische Projektinformationen anzeigen
app:status            kompakten Status anzeigen
```

`app:configure` ist die Personalisierungsebene der Foundation. Es schreibt `foundation.json`, nicht beliebige Fachlogik.

## Environment

```text
env:sync              Struktur aus .env.example synchronisieren
env:check             fehlende Keys in Zielumgebungen prüfen
env:diff              Strukturunterschiede anzeigen
env:backup            explizite Backups erzeugen
env:restore           Backup wiederherstellen
```

Wichtige `env:sync` Optionen: `--target=*`, `--force`, `--prune`, `--backup`, `--yes`, `--dry-run`.

## Modules

```text
module:make
module:list
module:show
module:check
module:graph
module:rename
module:remove
module:make:artifact
```

Ergonomische Artefaktbefehle:

```text
module:make:model
module:make:action
module:make:query
module:make:service
module:make:event
module:make:listener
module:make:job
module:make:policy
module:make:request
module:make:exception
module:make:enum
module:make:contract
module:make:dto
module:make:migration
module:make:factory
module:make:seeder
module:make:command
module:make:controller
```

## Designsystem

```text
design:make            component | pattern | template
design:list
design:show
design:check
design:graph
design:uses
design:unused
design:rename
design:remove
```

Tokens:

```text
design:token:make
design:token:list
design:token:show
design:token:check
design:token:sync
design:token:rename
design:token:remove
```

## Pages und Surfaces

```text
surface:make
surface:list
surface:show
surface:check
surface:rename
surface:remove

page:make
page:list
page:show
page:check
page:move
page:rename
page:remove
```

## Navigation und Permissions

```text
navigation:add
navigation:list
navigation:check
navigation:remove

permission:make
permission:list
permission:sync
permission:check
permission:remove
```

## Routes

```text
route:manifest
route:check
route:unused
```

Für den vollständigen Laravel-Routenbestand zusätzlich `php artisan route:list` verwenden.

## Architektur

```text
architecture:check
architecture:graph
architecture:cycles
architecture:dependencies
```

## Manifeste und Generated Artifacts

```text
manifest:build
manifest:check
manifest:diff

generated:clear
generated:rebuild
generated:check
```

`manifest:build` erzeugt einen Registry-Snapshot unter `storage/framework/foundation/registry.json`. Source of Truth bleiben die Originalmanifeste.

## Dokumentation

```text
docs:build             Manifestkatalog generieren
docs:check             Pflichtdokumente prüfen
docs:index             docs/INDEX.md erzeugen
docs:unused            nicht indexierte Dokumente finden
```

## Qualität

```text
quality:check
quality:fix
```

`quality:check` bündelt Foundation-Check, Tests sowie im vollständigen Modus Pint und PHPStan.

## Tooling History

```text
tooling:history
tooling:undo
```

## Beispiele

```bash
# Neue Fachlichkeit
php artisan module:make Customers
php artisan module:make:model Customers Customer
php artisan module:make:action Customers CreateCustomer

# Neue Oberfläche und Page
php artisan surface:make staff --prefix=staff --middleware=web --middleware=auth
php artisan permission:make customers.read
php artisan page:make customers.index --surface=staff --permission=customers.read
php artisan navigation:add staff Kunden staff.customers.index --permission=customers.read

# Designsystem
php artisan design:make component Button
php artisan design:make pattern EmptyState --uses=Button
php artisan design:check

# Sicherheit vor Änderung
php artisan page:make reports.index --surface=staff --dry-run
php artisan tooling:history
php artisan tooling:undo
```
