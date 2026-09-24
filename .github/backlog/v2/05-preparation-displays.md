## Ziel
Bestellungen nachvollziehbar an Kueche, Bar und spaeter weitere Zubereitungsstationen verteilen.

## Umfang
- frei definierbare Zubereitungsstationen
- Produkt/Menuekomponente kann mehrere Stationen adressieren
- Browser-Displays
- gesamter Vorgang sichtbar, eigener Stationsanteil hervorgehoben
- Stationsstatus: Neu -> In Zubereitung -> Fertig
- Gesamtstatus plus Teilstatus
- konfigurierbarer Benachrichtigungston
- Kellneransicht fuer fertige Teile

## Aenderungsregel
Bis eine betroffene Position `In Zubereitung` erreicht, darf sie geaendert oder entfernt werden. Danach bleibt sie unveraendert. Neue Positionen duerfen weiterhin ergaenzt werden und erzeugen neue Zubereitungsarbeit.

## Akzeptanzkriterien
- [ ] Kueche und Bar koennen unabhaengig fertig melden.
- [ ] Getraenke koennen fertig sein, waehrend Essen noch laeuft.
- [ ] Kein Status `Serviert` in V2.
- [ ] Concurrency/Doppelaktionen erzeugen keine widerspruechlichen Status.
