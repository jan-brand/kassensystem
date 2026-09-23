# Kassensystem v1 – Release Runbook

Dieses Runbook dokumentiert die technische und manuelle Freigabe von Kassensystem `v1.0.0`.

## 1. Ausgangslage

Vor dem Release-Gate muss der Arbeitsbaum bewusst gewählt sein:

```bat
git status --short
git log -1 --oneline
```

Für einen finalen Release Candidate sollte der Arbeitsbaum leer sein. Nicht eingecheckte Änderungen gehören nicht in die Freigabe.

## 2. Automatisches Release-Gate

In Windows CMD:

```bat
cd /d C:\xampp\htdocs\kassensystem
composer qa:release
```

`composer qa:release` führt die statische Analyse, Pint, die vollständige Test-Suite, den Foundation-App-Check, den Frontend-Build, den Release-Artefakt-Check sowie die zusätzlichen Modul-, Architektur-, Permission-, Navigation-, Page- und Acceptance-Checks aus.

Ein Release Candidate darf nur vorbereitet werden, wenn der Befehl mit Exit-Code 0 endet.

Für den vollständigen technischen Windows-RC1-Preflight inklusive `artisan optimize`:

```bat
scripts\production\rc1-check.cmd
```

Optional kann direkt danach auch der isolierte MariaDB-/MySQL-Migrationstest ausgeführt werden:

```bat
scripts\production\rc1-check.cmd --with-mariadb
```

## 3. Demo-Abnahme

Für einen frischen lokalen Abnahmestand:

```bat
php artisan migrate:fresh
php artisan app:demo:seed
```

Achtung: `migrate:fresh` löscht die aktuell konfigurierte Datenbank. Der Befehl darf nur auf einer ausdrücklich dafür vorgesehenen lokalen/Test-Datenbank verwendet werden.

Erwartete Demo-Basis:

- 3 Demo-Benutzer
- 5 Kategorien
- 14 Produkte
- 1 abgeschlossene Demo-Kassenschicht
- 3 abgeschlossene Verkäufe
- 8,00 EUR Gesamtumsatz
- davon 1 Verkauf mit 0,00 EUR

## 4. Manuelle Abnahme bleibt verpflichtend

Automatisierte Tests können Touch-Bedienung, reale Browserdarstellung, Druckdialoge und Geräteverhalten nicht vollständig abdecken.

Die manuelle v1-Abnahme wurde am 2026-09-23 durch JB gegen Commit `d08da7f31553f9c81b79f603243ac6419544b9d0` auf Desktop sowie einem realen Smartphone/Tablet erfolgreich abgeschlossen.

Besonders relevant:

- Login und PIN-Eingabe
- Produktwahl und Touch-Ziele
- mobiler Warenkorb
- Barzahlung und Rückgeld
- Kassenmenü
- Kassenabschluss
- Administration
- Verkaufsbeleg / Drucken
- Reporting / CSV

## 5. Produktionsprüfung

Auf dem vorgesehenen Produktionssystem:

```bat
php scripts\production\release-artifacts.php
php artisan app:production-check
```

Der Produktionscheck muss ohne `FAIL` abgeschlossen werden. Warnungen müssen vor Freigabe bewusst bewertet und dokumentiert werden.

Danach:

```bat
php artisan optimize
```

Falls `optimize` wegen einer projektspezifischen Route oder Konfiguration fehlschlägt, ist dies ein Release-Blocker und darf nicht ignoriert werden.

## 6. Backup vor Inbetriebnahme

Vor dem ersten realen Probebetrieb muss die Backup-Funktion mit der vorgesehenen Datenbank geprüft werden:

```bat
php artisan app:backup
```

Mindestens ein Restore-Test muss vorher auf einer separaten Testdatenbank erfolgreich durchgeführt worden sein.

## 7. Finaler Release

Der dokumentierte Acceptance-Commit ist der inhaltlich manuell geprüfte RC1-Stand. Ein anschließender Finalisierungscommit darf ausschließlich Release-Metadaten, Dokumentation, Issue-Status und Release-Guard-Tests ändern; fachliche Produktlogik erfordert eine erneute manuelle Abnahme.

Für v1 wurde Commit `d08da7f31553f9c81b79f603243ac6419544b9d0` manuell geprüft. Nach dem Finalisierungscommit müssen erneut ausgeführt werden:

```bat
composer qa:release
composer release:final-check
git status --short
git log -1 --oneline
```

Der finale Versionsname ist:

```text
v1.0.0
```

## 8. Release-Status und Final-Guard

Der Repository-Stand ist als `1.0.0` finalisiert. `release\v1-acceptance.json` enthält Datum, Tester, geprüften RC1-Commit und alle manuellen Nachweise. V1-008 und V1-000 sind geschlossen.

Der aktuelle Status muss ohne Blocker sein:

```bat
composer release:status
composer release:final-check
```

Erst wenn beide Befehle sowie `composer qa:release` erfolgreich sind, darf der finale Commit als `v1.0.0` getaggt werden.
