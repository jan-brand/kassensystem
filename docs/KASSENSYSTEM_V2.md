# Kassensystem v2 – Planung

## Status

Kassensystem `v1.0.0` bleibt die abgeschlossene Produktbasis. V2 ist in diesem Stand ausschließlich geplant; `config/release.php` bleibt auf `1.0.0/final`, bis ein echter v2 Release Candidate vorbereitet wird.

Die Planung basiert auf dem nach v1 eingespielten Maintenance-Stand `a35faf33983269029daaa166a63b64434fb586be`. Der bestehende Tag `v1.0.0` wird nicht verschoben.

## Zielbild

V2 erweitert den sicheren Bargeld-POS um nachvollziehbare Rueckabwicklung und eine allgemeine Zahlungsbasis. Darauf koennen optionale Tracks wie unbare Zahlung, Guthaben, Lager und Hardwareintegration aufbauen.

Der V2-Kern erweitert das unveraenderliche Buchungsmodell. Historische v1-Verkaeufe werden nicht umgeschrieben.

## Architekturregeln

- Abgeschlossene v1- und v2-Verkaeufe bleiben unveraenderlich.
- Ein Storno ist eine neue, referenzierte Gegenbuchung; kein Update oder Delete des Originalverkaufs.
- Core V2 startet mit vollstaendigem Verkaufsstorno. Teilstorno einzelner Positionen ist kein Core-Kriterium.
- Geldbetraege bleiben ganzzahlig in Cent.
- Payments werden als Ledger behandelt; 0-Euro-Verkaeufe bleiben ohne erfundenes Payment gueltig.
- Nur Bargeldbuchungen veraendern den erwarteten Kassenbestand.
- Guthaben wird ueber nachvollziehbare Buchungen, nicht ueber stille Saldo-Aenderungen, gefuehrt.
- Bestand wird ueber Bestandsbewegungen nachvollziehbar.
- Hardware- und Payment-Provider werden hinter Schnittstellen gekapselt.
- Reporting unterscheidet Verkauf, Gegenbuchung, Zahlungsart und Bargeldwirkung.
- Migrationen sind additiv und halten bestehende v1-Daten unveraendert lesbar.
- Neue privilegierte Aktionen erhalten zentrale Permissions und Audit-Ereignisse.

## Core V2 – P1

### V2-001 Storno

Vollstaendiger Storno als Gegenbuchung mit Referenz auf den Originalverkauf, Actor, Zeitpunkt und Pflichtgrund. Doppelte Gegenbuchungen werden verhindert. Originalverkauf und Storno bleiben gemeinsam nachvollziehbar.

### V2-002 Payment-Architektur

Bargeld wird zu einer expliziten Zahlungsart innerhalb eines allgemeinen Payment-Ledgers. Mehrere Zahlungsbuchungen pro Verkauf sollen technisch abbildbar sein, ohne bestehende v1-Barverkaeufe zu brechen.

### V2-007 Reporting und Audit

Berichte und CSV unterscheiden Bruttoverkauf, Storno, wirksamen Netto-Umsatz, Zahlungsarten und Bargeldauswirkung. Neue Buchungen erhalten nachvollziehbare Audit-Ereignisse.

### V2-008 Migration und Rueckwaertskompatibilitaet

Upgrade einer realistischen v1-Datenbank auf V2 muss reproduzierbar sein. Historische Sales, Payments, Sessions, Audit-Ereignisse und Reports bleiben lesbar und unveraendert.

### V2-009 Acceptance

Automatisierte Upgrade-/Regressionstests plus manueller End-to-End-Test auf Desktop und realem Touch-Geraet.

## Optionale V2-Tracks – P2

P2-Tracks werden vor Implementierung fachlich bestaetigt. Ein offenes P2-Issue blockiert einen Core-V2-RC nicht automatisch.

### V2-003 Unbare Zahlung

Providerneutrale unbare Zahlungsart. Direkte Terminalintegration gehoert erst in den Scope, wenn ein konkreter Anbieter und dessen Anforderungen feststehen.

### V2-004 Kunden-/Schuelerguthaben

Konten mit Aufladung, Abbuchung und Korrektur als Ledger. Vor Umsetzung werden Identifikation, Negativsaldo-Regel, Lebenszyklus und notwendige personenbezogene Daten entschieden.

### V2-005 Lager

Bestandsbewegungen fuer Zugang, Verkauf, Storno und Korrektur. Vor Umsetzung wird entschieden, ob Mengenbestand ausreicht oder Inventur/Mindestbestand benoetigt werden.

### V2-006 Beleg/Hardware

Druck- und Hardwareadapter hinter stabilen Schnittstellen. HTML/Browser-Beleg bleibt Fallback; herstellerspezifische Integration bleibt im Adapter.

## Discovery – P3

### V2-010 Fiskalisierung/TSE

Zunaechst werden konkrete betriebliche und technische Anforderungen dokumentiert. Erst danach wird entschieden, ob Implementierung zu V2 oder zu einer Folgerelease gehoert.

## Reihenfolge

1. V2-001 Storno.
2. V2-002 Payment-Architektur.
3. V2-007 Reporting/Audit.
4. V2-008 Migration/Kompatibilitaet.
5. Ausgewaehlte P2-Tracks.
6. V2-009 vollstaendige Acceptance.
7. Erst danach v2 Release Candidate und Versionspromotion.

## Abhaengigkeiten

- V2-003 und V2-004 haengen von V2-002 ab.
- V2-007 haengt mindestens von V2-001 und V2-002 ab.
- V2-008 begleitet jede Schemaaenderung und ist vor einem RC zwingend.
- V2-009 prueft alle fuer den RC ausgewaehlten P1- und P2-Tracks.
- V2-010 darf ohne geklaerten Scope keine fachliche Architektur erzwingen.

## Definition of Done

- Alle V2-P1-Issues sind geschlossen.
- Ausgewaehlte P2-Tracks sind umgesetzt oder bewusst verschoben.
- Historische v1-Daten wurden nicht nachtraeglich semantisch umgeschrieben.
- Upgrade einer v1-Datenbank ist automatisiert getestet.
- Permissions, Audit, Reporting und Exporte decken neue wirksame Buchungen ab.
- Automatisierte Release-Gates sind gruen.
- Desktop- und Touch-Abnahme sind dokumentiert.
- Produktions- sowie Backup/Restore-Dokumentation ist fuer V2 aktualisiert.
- Keine offenen V2-P0/P1-Blocker fuer den Release Candidate.

## Nicht automatisch Teil von V2

- Teilstorno einzelner Positionen.
- konkrete Karten-/Terminalanbieterintegration ohne ausgewaehlten Anbieter.
- umfangreiche Warenwirtschaft, Einkauf oder Lieferantenverwaltung.
- fiskalische Implementierung ohne dokumentierte Anforderungen.
- Cloud-/Mandantenfaehigkeit oder Mehrstandortbetrieb.
