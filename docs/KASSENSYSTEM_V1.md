# Kassensystem v1 – fachlicher Kern

Dieser Stand bildet den Backend-Kern für die erste Version des Schulcafeteria-Kassensystems.

## Enthalten

- Audit mit Redaction sensibler Daten und Unveränderlichkeit.
- Benutzer mit Rollen, PIN-Hashing, Aktivierung/Deaktivierung und PIN-Anmeldung.
- Kategorien und Produkte mit Preisen in Cent und 0-Euro-Produkten.
- Kassenplätze, Kassenschichten, Einlagen, Entnahmen und Soll-/Ist-Abschluss.
- Warenkorb und Bargeldverkauf mit Preis-Snapshots, Rückgeld, Verkaufsnummer und Doppelabschluss-Schutz.

## Bewusst noch nicht enthalten

- POS- und Verwaltungsoberfläche.
- Storno abgeschlossener Verkäufe.
- Schülerguthaben und andere Zahlungsmethoden.
- Lagerverwaltung.
- QR- oder Druckbelege.
- TSE.
- Reporting/CSV.

## Modulabhängigkeiten

`Identity -> Audit`

`Catalog -> Audit`

`CashRegister -> Identity, Audit`

`Sales -> Identity, Catalog, CashRegister, Audit`

Die Kassenlogik kennt das Sales-Modul nicht. Der Barumsatz wird beim Abschluss eines Verkaufs auf der Kassenschicht fortgeschrieben. Dadurch bleibt der Modulgraph azyklisch.

## POS-Kassenführung

Die POS-Oberfläche unterstützt neben Verkäufen auch die laufende Bargeldführung:

- Einlagen und Entnahmen mit positivem Betrag und Pflichtgrund,
- Anzeige von Startbestand, Barumsatz, Einlagen, Entnahmen und Sollbestand,
- Beginn des Kassenabschlusses nur ohne offenen Warenkorb,
- Sperre für neue Verkäufe und Kassenbewegungen während des Abschlusses,
- Abbruch eines begonnenen Abschlusses,
- Erfassung des gezählten Bargeldbestands,
- Pflichtkommentar bei Kassendifferenz,
- unveränderlicher abgeschlossener Kassenschicht-Datensatz.
