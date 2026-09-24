# Kassensystem v2 – Gastro, Kellner und digitale Abläufe

## Status

Kassensystem `v1.0.0` bleibt die freigegebene Produktbasis. Die V2-Entwicklung ist gestartet; der erste technische Storno-Block ist auf `main` vorhanden. `config/release.php` bleibt bis zu einem echten V2 Release Candidate auf `1.0.0/final`.

Diese Scope-Fassung basiert auf `e68a8df638d0faaae995a8d82918083abd101366`. Der bestehende Tag `v1.0.0` wird nicht verschoben.

Die ursprüngliche V2-Planung vom 2026-09-24 wurde vor der Umsetzung der übrigen Planungs-Issues fachlich ersetzt. Außer V2-001 hatten die alten V2-Planungs-Issues keine Implementierung und keine GitHub-Issue-Nummer.

## Zielbild

V2 macht aus dem bisherigen Kassen-POS ein größeres Gastro-System für einen Schulbetrieb:

- klassische Sofortverkäufe bleiben schnell und robust,
- Kellner können Bereiche, Tische und Vorgänge bedienen,
- alternativ können vorab bezahlte oder kostenlos ausgegebene QR-Tickets für ein Tagesmenü verwendet werden,
- Küche und Bar erhalten eigene Browser-Displays,
- Zahlungen unterstützen Bargeld und PayPal.me mit manueller Bestätigung,
- Preise können über nachvollziehbare Positionsrabatte und Tagesangebote verändert werden,
- Belege können digital per QR-Code abgerufen werden,
- Reporting und CSV bilden die neuen Vorgänge nachvollziehbar ab.

V2 bleibt ein Ein-Standort-System. Lager, Bondrucker, Tischwechsel, Guthaben und Produkt-Scanning werden bewusst auf spätere Versionen verschoben.

## Domänengrenzen

### Bestellung ist nicht Verkauf

Ein Tisch-/Kellnervorgang ist ein eigener Bestellvorgang und noch kein finanzieller Verkauf. Er erhält eine eindeutige Vorgangsnummer. Eine Verkaufs-/Rechnungsnummer entsteht erst, wenn tatsächlich ein Verkauf abgeschlossen wird.

`Sales` bleibt für finanzielle Verkäufe, Zahlungen, Belege und Storno zuständig. Die Gastro-Domain wird getrennt modelliert, damit offene Bestellungen nicht zu halbfertigen Sales werden.

### Sofortzahlung bleibt die Grundregel

Der Schulbetrieb führt keine offene Restaurantrechnung über lange Zeit. Wenn normal verkauft wird, wird sofort bezahlt. Bargeld wird an einer echten Kasse abgeschlossen. PayPal kann am Tisch angeboten werden, gilt aber erst nach manueller Bestätigung durch einen Mitarbeiter als bezahlt.

### Ticket ist eine Menüberechtigung

Ein Ticket ist kein frei verfügbares Geldguthaben. Es berechtigt am jeweiligen Tag zur Zusammenstellung eines definierten Menüs.

- Ticket besitzt einen eindeutigen, schwer erratbaren QR-Code.
- Ticket kann vorab online vorbereitet oder als QR-Code auf Papier ausgegeben werden.
- Ein Ticket wird genau einmal einem Tisch zugeordnet und kann in V2 nicht umgehängt werden.
- Das Ticket bleibt diesem Tisch bis zum Schließen des Tisches zugeordnet.
- Die erlaubten Menü-Slots können nur innerhalb ihrer definierten Mengen eingelöst werden.
- Mehrere Tickets pro Tisch sind erlaubt.
- Ein bezahltes Ticket erzeugt beim Kauf einen normalen Verkauf und Beleg.
- Ein kostenlos ausgegebenes Ticket erzeugt keinen erfundenen Umsatz und kein Fake-Payment.
- Die spätere Menüeinlösung erzeugt keinen zweiten Umsatz.

## V2-A – Gastro-Domain und Menüsystem

### V2-002 Gastro-Domain, Vorgänge und Snapshots

V2 führt eigenständige Gastro-Vorgänge ein. Bereiche und Tische sind konfigurierbar. Zunächst darf pro Tisch genau ein offener Vorgang existieren. Tischwechsel kommt erst später.

Jeder Vorgang erhält eine eigene Vorgangsnummer. Historische Informationen, die für Abrechnung oder Zubereitung relevant sind, werden als Snapshot gespeichert und nicht aus später veränderten Katalogdaten rekonstruiert.

### Menüs und Optionen

V2 unterstützt sowohl normale Produkte mit Optionen als auch echte Menügruppen, zum Beispiel:

- ein Hauptgericht aus mehreren Möglichkeiten,
- eine Beilage aus mehreren Möglichkeiten,
- ein Getränk aus mehreren Möglichkeiten.

Optionen können auf Produkt- oder Kategorieebene angeboten werden. Zusätzlich ist ein kurzer Freitext pro Position möglich.

Optionen dürfen in V2 Aufpreise erzeugen, aber keine versteckten Abschläge. Preisnachlässe laufen ausschließlich über das Rabattsystem.

Menükomponenten können unterschiedliche Zubereitungsstationen besitzen.

## V2-B – Kellner, Bereiche, Tische und Tickets

### V2-003 Kellnerrolle und Kellner-POS

Identity erhält eine eigene Rolle `waiter`. Das ist bewusst getrennt vom Kassierer. Konkrete Aktionen werden weiterhin über Permissions geschützt.

Mehrere Kellner dürfen denselben Tisch/Vorgang bearbeiten. Die V2-Tischübersicht verwendet große Touch-Kacheln und zeigt mindestens Bereich, Tisch, Belegungsstatus, Vorgangsnummer und relevante Zubereitungszustände.

Ein grafischer Raumplan ist ausdrücklich kein V2-Ziel.

Ob Kellner selbst kassieren dürfen, wird über Berechtigungen und Betriebsmodus entschieden. Bargeld bleibt an einer echten Kasse; PayPal kann am Tisch durchgeführt werden.

### V2-004 QR-Tickets und Menüberechtigungen

Der Ticketmodus deckt Veranstaltungen/Tage ab, an denen ein Menü bereits vorab bezahlt oder als kostenlose Berechtigung ausgegeben wurde. Der Kellner scannt den QR-Code und ordnet das Ticket dem Tisch zu.

Ticket-Einlösung und normale Sofortzahlung dürfen nebeneinander existieren, ohne Umsatz doppelt zu zählen.

## V2-C – Küche und Bar

### V2-005 Zubereitungsstationen und Displays

Produkte bzw. Menükomponenten können an eine oder mehrere Zubereitungsstationen geroutet werden. Typische Stationen sind `Küche` und `Bar`, das Modell darf aber nicht auf genau diese beiden Namen fest verdrahtet sein.

Küche und Bar laufen als normale Browser-Oberflächen. Beide dürfen den vollständigen Vorgang sehen; die für die eigene Station relevanten Positionen werden deutlich hervorgehoben.

Jede Station führt ihren eigenen Teil durch:

`Neu -> In Zubereitung -> Fertig`

Es gibt in V2 keinen zusätzlichen Status `Serviert`.

Gesamtstatus und Stationsstatus werden getrennt ausgewertet. Getränke können deshalb bereits als fertig beim Kellner erscheinen, während Essen noch in Zubereitung ist.

Solange eine betroffene Position noch nicht `In Zubereitung` erreicht hat, darf sie geändert oder entfernt werden. Danach bleiben bestehende Positionen unverändert; Ergänzungen sind weiterhin möglich und erzeugen neue Zubereitungsarbeit.

Kellner erhalten eine eigene Abholbereit-Ansicht. Akustische Hinweise sind konfigurierbar.

## V2-D – Payment und PayPal

### V2-006 Payment-Ledger und PayPal.me

Das v1-Einzelpayment wird zu einem allgemeinen Payment-Ledger weiterentwickelt. Bestehende v1-Barzahlungen bleiben unverändert lesbar.

Praktisch unterstützt V2:

- Bargeld,
- PayPal.me.

Die Architektur soll spätere Kombinationen wie Guthaben plus Restzahlung erlauben, ohne dass V2 bereits das Guthabensystem implementiert.

PayPal.me wird zentral für den Standort konfiguriert. Das System erzeugt einen QR-Code mit dem konkreten Zahlbetrag. Der Kunde scannt ihn, bezahlt auf seinem eigenen Gerät und zeigt dem Kassierer/Kellner die PayPal-Bestätigung.

Ein angezeigter QR-Code ist niemals automatisch eine bestätigte Zahlung. Der Mitarbeiter muss die Zahlung ausdrücklich bestätigen. Es gibt in V2 keine PayPal-API- oder Terminalintegration.

## V2-E – Rabatte und Tagesangebote

### V2-007 Positionsrabatte

Rabatte wirken ausschließlich auf einzelne Verkaufs-/Bestellpositionen. Pro Position darf höchstens ein wirksamer Rabatt existieren.

Unterstützt werden:

- neuer Endpreis,
- fixer Rabattbetrag,
- prozentualer Rabatt.

Zusätzlich gibt es vorbereitete Rabatte bzw. Tagesangebote. Sie können manuell aktiv/inaktiv gesetzt werden, Start-/Endzeitpunkte besitzen und wiederkehrende Wochentage/Zeitfenster verwenden.

Rabattieren wird über eine eigene Permission geschützt. Rabattart, Ausgangspreis, Rabatt und resultierender Preis werden als Snapshot nachvollziehbar gespeichert.

## V2-F – Digitale Belege

### V2-008 QR-Belege

V2 behält den Browserbeleg und ergänzt einen digitalen Abruf:

- jeder freigegebene digitale Beleg erhält einen schwer erratbaren öffentlichen Token,
- Kunde scannt einen QR-Code und öffnet eine öffentliche Beleg-URL ohne Login,
- die öffentliche Darstellung enthält keine unnötigen personenbezogenen Daten,
- Aufbewahrungsdauer ist konfigurierbar,
- alte Belege können erneut angezeigt/nachgedruckt werden,
- ein Storno erhält einen eigenen nachvollziehbaren Storno-Beleg.

Bondrucker und Herstelleradapter gehören nicht zu V2.

## V2-G – Reporting, Audit und Export

### V2-009 Reporting

V2-Reporting unterscheidet mindestens:

- Bruttoverkäufe, Stornos und wirksamen Netto-Umsatz,
- Bargeld und PayPal,
- Rabatte und Tagesangebote,
- Produkte und Kategorien,
- Kellner,
- Bereiche, Tische und Vorgänge,
- Tickets und Einlösungen,
- Zubereitungsstationen und Statuszeiten.

UI und CSV verwenden dieselbe fachliche Berechnungsbasis. Der Buchhaltungs-CSV wird erweitert, ohne einen DATEV- oder Steuerexport zu behaupten.

Audit protokolliert privilegierte Änderungen, Payment-Bestätigungen, Ticketzuweisungen, Rabatte und relevante Gastro-Statuswechsel mit Actor und Referenzen.

## V2-H – Migration und Acceptance

### V2-010 V1-Upgrade

Migrationen bleiben additiv. Eine realistische V1-Datenbank muss auf V2 aktualisiert werden können, ohne bestehende Sales, Payments, CashSessions, Audit-Ereignisse oder Belege semantisch umzuschreiben.

SQLite sowie MySQL/MariaDB werden getestet.

### V2-011 Acceptance

Vor einem V2 Release Candidate werden mindestens geprüft:

- klassische Kasse,
- mehrere Kassen am selben Standort,
- Kellner-POS auf realistischem Touch-Gerät,
- Bereiche/Tische/Vorgänge,
- Ticketkauf, kostenloses Ticket und QR-Einlösung,
- Küche und Bar auf getrennten Browsergeräten,
- Bargeld und PayPal.me,
- Rabatte/Tagesangebote,
- digitale Belege,
- Reporting/CSV,
- V1-Upgrade,
- Backup/Restore.

## V2-001 Storno – noch offen

Der unveränderliche Vollstorno ist technisch bereits auf `main` implementiert, V2-001 bleibt aber offen, bis die endgültige V2-Regel umgesetzt ist:

- Produkte erhalten eine Lebensmittel-/Verzehrartikel-Kennzeichnung.
- Essen und Getränke zählen beide als Verzehrartikel.
- Die Kennzeichnung wird beim Verkauf historisch gesnapshottet.
- Enthält ein Verkauf mindestens einen Verzehrartikel, ist der Vollstorno in V2 gesperrt.
- Gemischte Verkäufe sind damit ebenfalls nicht stornierbar.
- Teilstorno ist kein V2-Ziel.

## Reihenfolge

1. V2-001 Storno um Verzehrartikel-Regel härten.
2. V2-002 Gastro-Domain und Vorgangsmodell.
3. V2-003 Kellnerrolle, Bereiche, Tische und Kellner-POS.
4. V2-004 Ticket-/Menümodell.
5. V2-005 Küche-/Bar-Workflow.
6. V2-006 Payment-Ledger und PayPal.me.
7. V2-007 Rabatte/Tagesangebote.
8. V2-008 digitale Belege.
9. V2-009 Reporting/Audit/CSV.
10. V2-010 Upgrade-Kompatibilität vollständig absichern.
11. V2-011 vollständige Acceptance.
12. Erst danach V2 Release Candidate und Versionspromotion.

## Ausdrücklich nicht in V2

- Teilstorno oder Positionsstorno,
- Split-Payment nach einzelnen Positionen,
- Kunden-/Schülerguthaben,
- Lager/Bestandsführung,
- Produkt-Barcode-/NFC-Scanning,
- Bondrucker,
- Tischwechsel,
- mehrere Standorte,
- grafischer Raumplan,
- Pfand/Pfandrückgabe,
- Mehrwertsteuerlogik,
- TSE/Fiskalisierung,
- direkte PayPal-API- oder Kartenterminalintegration.

Diese Punkte sind in `docs/ROADMAP.md` den späteren Versionen zugeordnet.

## Definition of Done

- Alle V2-P1-Issues sind geschlossen.
- Keine offenen V2-P0/P1-Blocker.
- Historische v1-Daten bleiben unverändert lesbar.
- V1 -> V2 Upgrade ist automatisiert getestet.
- Permissions und Audit decken neue privilegierte Aktionen ab.
- Zahlungen, Tickets, Rabatte und Vorgänge werden nicht doppelt als Umsatz gezählt.
- Küche/Bar und Kellner-POS sind auf getrennten Browsergeräten praktisch getestet.
- Digitale Belege sind ohne Login abrufbar, aber nicht erratbar.
- Reporting und Buchhaltungs-CSV bilden die V2-Domain konsistent ab.
- Backup/Restore und Produktionscheck sind für V2 aktualisiert.
- Desktop- und Touch-Acceptance sind dokumentiert.
