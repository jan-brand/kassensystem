# Changelog

## 1.0.0 - 2026-09-23

- RC1 technisch vollständig grün und Composer-Lock synchronisiert.
- Manuelle v1-Abnahme durch JB gegen Commit `d08da7f31553f9c81b79f603243ac6419544b9d0` abgeschlossen.
- Desktop sowie reales Smartphone/Tablet geprüft.
- Backup/Restore praktisch geprüft und Produktionscheck bewertet.
- V1-008 und der v1-Epic V1-000 abgeschlossen.

## 1.0.0-rc.1 - 2026-09-22

### POS und Kassenführung

- tablet-orientierter POS mit Login, Kategorien, Produktsuche, Warenkorb und neutralem Dark-Terminal-Design
- Bargeldzahlung mit Received/Change, 0-Euro-Verkäufen und Doppelabschluss-Schutz
- Kassenöffnung, Einlagen, Entnahmen sowie Soll-/Ist-Abschluss mit Differenzkommentar
- unveränderliche abgeschlossene Verkäufe, Payments und Kassenschichten

### Administration

- Katalog- und Kassiererverwaltung
- Sales-Suche mit Belegdetails und gespeicherten Produkt-/Preis-Snapshots
- Reporting, Produktmengen, Kassenschichten und CSV-Export
- Systemeinstellungen und Audit-Protokoll
- zentrale Rollen- und Permission-Durchsetzung

### Qualität und Betrieb

- Demo-Daten und automatisierte v1-Acceptance-Suite
- Pint und PHPStan/Larastan Level 6 ohne pauschale Kassensystem-Ignores
- SQLite-Backup/Restore sowie MySQL/MariaDB-Backup-Pfade
- Production-Check, Health-Checks und gehärtete Fehlerseiten
- reproduzierbares `composer qa:release`
- Windows-RC1-Preflight inklusive Laravel Optimize/Route-Cache
- Release-Artefaktprüfung und isolierter MariaDB-/MySQL-Smoke-Test
- finaler Release-Guard für die noch ausstehende manuelle Acceptance

## 0.1.0 - 2026-09-19

- initiale neutrale Laravel-Webapp-Foundation
- Module, Pages, Surfaces, Designsystem und Manifeste
- geschützte `.env` Synchronisation mit Force-Backup
- Architektur- und Designchecks
- lokales Foundation Dashboard
- Generator-History und Undo
- ausführliche Dokumentation
