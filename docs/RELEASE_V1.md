# Kassensystem v1 – Release Candidate Runbook

Dieses Runbook bündelt die technische Freigabe für einen v1 Release Candidate. Es ersetzt die manuelle Abnahme in `docs/ACCEPTANCE_V1.md` nicht.

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

`composer qa:release` führt die statische Analyse, Pint, die vollständige Test-Suite, den Foundation-App-Check, den Frontend-Build sowie die zusätzlichen Modul-, Architektur-, Permission-, Navigation-, Page- und Acceptance-Checks aus.

Ein Release Candidate darf nur vorbereitet werden, wenn der Befehl mit Exit-Code 0 endet.

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

Vor `v1.0.0` müssen deshalb die offenen Punkte in `docs/ACCEPTANCE_V1.md` auf einem realen Smartphone oder Tablet und mindestens einem Desktop-Browser geprüft werden.

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

## 7. Release Candidate

Nach erfolgreicher automatischer und manueller Abnahme:

```bat
git status --short
git log -1 --oneline
```

Der dokumentierte Acceptance-Commit muss exakt dem freizugebenden Commit entsprechen.

Erst danach einen Release Candidate bzw. finalen v1-Tag vorbereiten. Der vorgesehene finale Versionsname ist:

```text
v1.0.0
```

V1-008 und anschließend der v1-Epic werden erst geschlossen, wenn die manuelle Abnahme dokumentiert ist.
