## Ziel
Die neue V2-Domain konsistent in Reporting, Audit und Buchhaltungs-CSV abbilden.

## Reporting
- Bruttoverkauf, Storno, Netto-Umsatz
- Bargeld und PayPal
- Rabatte/Tagesangebote
- Produkte/Kategorien
- Kellner
- Bereiche/Tische/Vorgaenge
- Tickets/Einloesungen
- Zubereitungsstationen und relevante Statuszeiten

## Audit
Privilegierte Aktionen, Ticketzuweisungen, Payment-Bestaetigungen, Rabatte und relevante Statuswechsel werden mit Actor/Referenzen protokolliert.

## Akzeptanzkriterien
- [x] UI und CSV verwenden dieselbe fachliche Basis.
- [x] Ticket-Einloesung zaehlt bezahlten Ticketkauf nicht erneut als Umsatz.
- [x] Bargeldreport bleibt mit Kassenschichten konsistent.
- [x] Buchhaltungs-CSV ist erweitert, behauptet aber keinen DATEV-/Steuerexport.

## Implementierung V2-009
- Der Tagesreport trennt Bruttoverkauf, am Berichtstag gebuchte Vollstornos und Netto-Umsatz.
- Bar- und PayPal-Anteile stammen ausschließlich aus dem unveraenderlichen Payment-Ledger; Barauszahlungen aus Stornos werden separat gezeigt.
- Rabattwirkung wird aus den Positionssnapshots ermittelt und in Tagesangebote und manuelle Rabatte getrennt.
- Produkt- und Kategorienauswertung nutzt historische Sale-Item-Snapshots. Die Kategorie wird ab V2-009 beim Verkauf mitgespeichert.
- Gastro-Vorgaenge werden operativ nach Kellner, Bereich und Tisch ausgewertet und niemals als Umsatz interpretiert.
- Ticket-Ausgaben und -Einloesungen erscheinen separat. Eine Einloesung eines bezahlten Tickets erzeugt keinen zweiten Umsatz.
- Zubereitungsstationen zeigen Starts, Fertigmeldungen und die mittlere Zeit von Start bis Fertig.
- Der Report zeigt die am Berichtstag geschriebenen Audit-Eventtypen. Die Detaildaten bleiben im geschuetzten Audit-Browser.
- Die CSV nutzt dieselbe Tagesbasis wie die UI und ist ausdruecklich eine Buchhaltungs-CSV, kein DATEV- oder Steuerexport.
