# Kassensystem v1 – Funktionsumfang

Das Kassensystem v1 ist ein tablet-orientiertes Bargeld-POS mit rollenbasierter Administration für eine Schulcafeteria bzw. einen kleinen gastronomischen Betrieb.

## Release-Status

Der Repository-Stand ist als `1.0.0-rc.1` vorbereitet. Die automatisierten Release-Gates und der RC1-Preflight sind Bestandteil des Projekts. Der finale Stand `v1.0.0` wird erst nach der dokumentierten manuellen Acceptance freigegeben.

Der maschinenlesbare Acceptance-Record liegt unter `release/v1-acceptance.json`.

## Identity und Berechtigungen

- PIN-basierte Anmeldung mit gehashten PINs.
- Rollen `cashier`, `manager` und `administrator`.
- zentrale Permission-Matrix und serverseitige Durchsetzung.
- Aktivierung/Deaktivierung von Benutzern.
- Login-Sperre nach konfigurierbaren Fehlversuchen.
- Benutzerwechsel im POS nur ohne offenen Warenkorb.

## Catalog und POS

- Kategorien und Produkte mit Sortierung und Aktivstatus.
- Preise werden als Cent-Werte gespeichert.
- 0-Euro-Produkte werden unterstützt.
- Tablet-POS mit Kategorienavigation, Produktsuche, Warenkorb und Mengensteuerung.
- ein offener Warenkorb pro Kassenplatz.
- Barzahlung mit erhaltenem Betrag und Rückgeld.
- abgeschlossene Verkäufe verwenden gespeicherte Produktnamen und Preise als Snapshots.
- Schutz gegen Doppelabschluss eines Verkaufs.

## CashRegister

- Kassenöffnung mit Anfangsbestand.
- Einlagen und Entnahmen mit Pflichtgrund.
- Entnahmen dürfen den erwarteten Bargeldbestand nicht negativ machen.
- Anzeige von Startbestand, Barumsatz, Einlagen, Entnahmen und Sollbestand.
- Abschlussmodus sperrt neue Verkäufe und Kassenbewegungen.
- Soll-/Ist-Abschluss mit Pflichtkommentar bei Differenz.
- abgeschlossene Kassenschichten und Kassenbewegungen sind unveränderlich.

## Administration

Die Administration umfasst:

- Katalogverwaltung,
- Kassierer-/Benutzerverwaltung im Rahmen der Rolle,
- Verkaufssuche und Belegdetails,
- Tagesreporting und CSV-Export,
- Kassen-/Schichtauswertungen,
- Systemeinstellungen für Cafeteria, Kassenname, Logo und POS-Darstellung,
- Audit-Protokoll für Administratoren.

## Audit und Nachvollziehbarkeit

Relevante Vorgänge werden mit Actor, Subject und fachlichen Änderungen protokolliert. Sensible Felder wie PINs und Secrets werden aus Audit-Payloads entfernt. Audit-Datensätze sind unveränderlich.

## Produktion und Betrieb

- lokale Entwicklung und Tests mit SQLite,
- Produktionsziel MySQL/MariaDB,
- Datenbank-Backup und Restore,
- `/up` als Framework-Health-Check und `/health` mit Datenbankprüfung,
- Produktionscheck für Environment, Datenbank, Migrationen, Storage, Demo-Konten und Release-Artefakte,
- RC1-Preflight inklusive `artisan optimize`,
- optionaler isolierter MariaDB-/MySQL-Migrationstest.

## Modulabhängigkeiten

`Audit -> []`

`Identity -> Audit`

`Catalog -> Identity, Audit`

`CashRegister -> Identity, Audit`

`Sales -> Identity, Catalog, CashRegister, Audit`

`Reporting -> Sales, Catalog, CashRegister, Identity`

`Settings -> Identity, Audit`

Die Kassenlogik kennt das Sales-Modul nicht. Der Barumsatz wird beim Abschluss eines Verkaufs auf der Kassenschicht fortgeschrieben. Dadurch bleibt der fachliche Modulgraph azyklisch.

## Bewusst nicht Bestandteil von v1

- Storno bereits abgeschlossener Verkäufe.
- Kartenzahlung, Schülerguthaben oder weitere Zahlungsarten.
- Lager- und Bestandsführung.
- TSE-/Fiskalisierungsintegration.
- spezialisierte Bondrucker- oder Hardware-Anbindungen.

Erweiterungen dieser Art gehören in eine Folgerelease und verändern nicht den v1-Release-Scope.
