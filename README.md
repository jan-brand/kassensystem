# Webapp Foundation

Ein neutrales Laravel-Grundkonstrukt für sehr unterschiedliche Webanwendungen. Der Schwerpunkt liegt nicht auf vorgefertigten Fachfunktionen, sondern auf **Architektur, Generatoren, Designsystem, Surfaces, Pages, Umgebungsmanagement, Diagnose und sicherem Refactoring**.

Die Foundation ist bewusst kein Vereinsportal-, CRM-, Shop- oder SaaS-Template. Fachmodule werden erst im jeweiligen Projekt erzeugt.

## Kernidee

Die Anwendung kennt ihre eigene Struktur. Manifeste beschreiben Module, Surfaces, Pages, Designbausteine, Navigation und Permissions. Konsolenbefehle können diese Struktur erzeugen, prüfen, anzeigen und teilweise sicher verändern.

```text
Tokens → Components → Patterns → Templates → Pages
                                  ↑
                              Surfaces

Modules → Actions / Queries / Models / Jobs / Policies / …

.env.example → env:sync → .env / .env.testing / weitere Ziele
```

## Enthaltene Verbesserungen

Neben den ursprünglich geplanten Generatoren sind drei zusätzliche Qualitätsfunktionen eingebaut:

- **Interaktive Projektkonfiguration** über `app:configure`, damit sich Defaults pro Projekt personalisieren lassen.
- **Lokales Foundation Dashboard** unter `/__foundation`, das Module, Pages, Surfaces, Designbausteine und Permissions sichtbar macht.
- **Generator-Historie mit Undo** über `tooling:history` und `tooling:undo`. Dateiverändernde Foundation-Befehle protokollieren ihren vorherigen Zustand und können den letzten Schritt zurücknehmen.

Dazu kommen Dry-Run-Unterstützung, Architekturchecks, Design-Abhängigkeitsprüfung und ein geschützter `.env`-Synchronisierer.

## Voraussetzungen

- PHP 8.4+
- Composer 2
- Node.js 22+ empfohlen
- npm
- standardmäßig SQLite; MySQL kann normal über `.env` konfiguriert werden

## Installation

Alternativ steht unter Linux/macOS `./bin/setup.sh` und unter Windows PowerShell `./bin/setup.ps1` bereit. Manuell:

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan env:sync --yes
npm install
npm run build
php artisan app:init
php artisan app:doctor
php artisan serve
```

Danach:

- Anwendung: `http://127.0.0.1:8000`
- lokales Architektur-Dashboard: `http://127.0.0.1:8000/__foundation`

Das Dashboard wird standardmäßig nur im `local` Environment registriert.

## Typischer Start eines neuen Projekts

```bash
php artisan app:configure
php artisan surface:make portal --prefix=app
php artisan module:make Projects
php artisan module:make:model Projects Project
php artisan module:make:action Projects CreateProject
php artisan module:make:query Projects FindProjects
php artisan design:make component Button
php artisan design:make pattern PageHeader --uses=Button
php artisan design:make template AdministrationList --uses=PageHeader
php artisan page:make projects.index --surface=portal --uri=/projects
php artisan app:check
```

## Environment-Synchronisation

Normaler Modus erhält vorhandene Werte:

```bash
php artisan env:sync
```

Beispiel: Steht in `.env` bereits `DB_PASSWORD=secret`, bleibt dieser Wert bestehen, während neue Schlüssel und die Struktur aus `.env.example` übernommen werden.

Destruktiver Reset auf Beispielwerte:

```bash
php artisan env:sync --force
```

`--force` legt vor dem Überschreiben automatisch eine Sicherung unter `.foundation/env-backups/` an. Für Automatisierung kann zusätzlich `--yes` verwendet werden. Vor jeder Veränderung ist `--dry-run` verfügbar.

## Qualitätsprüfung

```bash
php artisan app:check
php artisan quality:check
```

Oder über Composer:

```bash
composer qa
```

## Dokumentation

Die ausführliche Dokumentation liegt unter `docs/`:

- `docs/ARCHITECTURE.md`
- `docs/CLI_REFERENCE.md`
- `docs/DESIGN_SYSTEM.md`
- `docs/ENVIRONMENT.md`
- `docs/MODULES.md`
- `docs/PAGES_AND_SURFACES.md`
- `docs/GENERATOR_CONTRACT.md`
- `docs/HISTORY_AND_UNDO.md`
- `docs/DEVELOPMENT.md`
- `docs/EXTENDING.md`
- `docs/TESTING.md`
- `docs/SECURITY.md`

`php artisan docs:index` erzeugt zusätzlich einen Index, `php artisan docs:build` einen aus den Manifesten generierten Projektkatalog.

## Projektstatus

Dies ist eine funktionsfähige Foundation-Version 1. Sie konzentriert sich auf die strukturellen Werkzeuge. Authentifizierung, konkrete Rollen, Billing, Membership, Produkte, Events, Kunden oder andere Fachfunktionen gehören bewusst **nicht** zum Grundsystem.

## Lizenz

MIT.
