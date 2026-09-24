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
- [ ] UI und CSV verwenden dieselbe fachliche Basis.
- [ ] Ticket-Einloesung zaehlt bezahlten Ticketkauf nicht erneut als Umsatz.
- [ ] Bargeldreport bleibt mit Kassenschichten konsistent.
- [ ] Buchhaltungs-CSV ist erweitert, behauptet aber keinen DATEV-/Steuerexport.
