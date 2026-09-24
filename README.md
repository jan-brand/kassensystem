# Kassensystem

Tablet-optimiertes Laravel-Kassensystem für Schulcafeteria und Gastronomie. Der v1-Fokus liegt auf einem schnellen, robusten Bargeld-POS mit nachvollziehbarer Kassenführung sowie einer rollenbasierten Administration.

**Release-Stand:** `1.0.0`. Die technische und manuelle v1-Abnahme wurde am 2026-09-23 erfolgreich abgeschlossen. V2 befindet sich aktiv in Entwicklung; Release-Metadaten bleiben bis zu einem V2 Release Candidate auf v1.

## Funktionsumfang v1

- PIN-Anmeldung mit Rollen `cashier`, `manager` und `administrator`
- Tablet-POS mit Kategorien, Produktsuche, Warenkorb und Mengensteuerung
- reine Barzahlung in v1 inklusive Received/Change und 0-Euro-Verkäufen
- Kassenöffnung, Einlagen, Entnahmen und Soll-/Ist-Kassenabschluss
- unveränderliche abgeschlossene Verkäufe, Payments, Kassenbewegungen und Audit-Ereignisse
- Administration für Katalog, Kassierer, Sales/Belegansicht, Reporting/CSV, Einstellungen und Audit
- Demo-Daten für lokale Acceptance-Tests
- SQLite für lokale Entwicklung/Tests sowie MySQL/MariaDB als Produktionsziel
- Backup/Restore, Health-Checks, Production-Check und RC1-Release-Gates

## Technischer Stack

- PHP 8.4+
- Laravel 13
- Livewire 4
- Tailwind CSS 4 / Vite
- Pest
- Larastan / PHPStan
- Laravel Pint

## Lokaler Start unter Windows CMD

```bat
cd /d C:\xampp\htdocs\kassensystem
copy .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan storage:link
php artisan serve
```

Die Anwendung startet anschließend über die konfigurierte `APP_URL`. Der POS leitet nicht angemeldete Benutzer auf den Login um.

## Demo- und Acceptance-Daten

Nur in `local` oder `testing`:

```bat
php artisan app:demo:seed
```

Die Demo-Zugangsdaten und der vollständige manuelle Ablauf stehen in `docs/ACCEPTANCE_V1.md`. Demo-Konten sind für Produktion ausdrücklich nicht vorgesehen; `app:production-check` blockiert bekannte Demo-Benutzernamen.

## Qualität und Release

Vollständiges automatisiertes Release-Gate:

```bat
composer qa:release
```

Technischer Windows-RC1-Preflight inklusive Laravel Optimize/Route-Cache:

```bat
scripts\production\rc1-check.cmd
```

Optional mit isoliertem MariaDB-/MySQL-Migrationstest:

```bat
scripts\production\rc1-check.cmd --with-mariadb
```

Aktueller Final-Release-Status:

```bat
composer release:status
```

Der harte Final-Release-Check ist absichtlich erst nach dokumentierter manueller Acceptance erfolgreich:

```bat
composer release:final-check
```

## Release-Nachweis

Die v1-Abnahme ist abgeschlossen. Der nachvollziehbare Acceptance-Record liegt unter `release/v1-acceptance.json` und verweist auf den manuell geprüften RC1-Commit `d08da7f31553f9c81b79f603243ac6419544b9d0`.

Der finale Repository-Stand muss weiterhin beide Release-Gates ohne Fehler bestehen:

```bat
composer qa:release
composer release:final-check
```

## Dokumentation

Einstiegspunkte:

- `docs/KASSENSYSTEM_V1.md` – fachlicher v1-Umfang
- `docs/KASSENSYSTEM_V2.md` – aktiver v2-Scope für Gastro, Kellner, Tickets, Küche/Bar, Payments und QR-Belege
- `docs/ROADMAP.md` – Abgrenzung von v2, v3 und v4
- `docs/KASSENSYSTEM_V3.md` – bewusst auf v3 verschobene Funktionen
- `docs/KASSENSYSTEM_V4.md` – langfristig vorgemerkter Scope
- `docs/ACCEPTANCE_V1.md` – manuelle und automatisierte Abnahme
- `docs/RELEASE_V1.md` – Release-Candidate- und Final-Release-Ablauf
- `docs/PRODUCTION.md` – Produktion, Backup und Restore
- `docs/MARIADB_SMOKE_TEST.md` – isolierter MySQL-/MariaDB-Test
- `docs/MOBILE_TESTING.md` – Geräteprüfung
- `CHANGELOG.md` – Versionshistorie

Die generische Foundation bleibt als internes Entwicklungswerkzeug erhalten. Projektname, Standardsurface und Modulstruktur sind in `foundation.json` auf das Kassensystem ausgerichtet.

## Lizenz

MIT.
