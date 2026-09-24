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

## Technische Leitplanken
- Stationen werden als eigene Domain gepflegt; Produktzuordnungen gelten auch fuer dasselbe Produkt als Menuekomponente.
- Eine Position erzeugt pro adressierter Station genau einen Arbeitsauftrag je Produktquelle.
- Statuswechsel sind transaktionsgesichert und nur `Neu -> In Zubereitung -> Fertig` erlaubt; Wiederholung desselben Zielstatus ist idempotent.
- Ein fertiger Teil erzeugt keinen Umsatz und keinen Status `Serviert`.
- Entfernte Stationszuordnungen wirken nur auf neue Arbeit; bereits erzeugte Arbeitsauftraege bleiben historisch erhalten.
- Browser-Displays zeigen den gesamten Tischvorgang und heben den eigenen Stationsanteil hervor.

## Akzeptanzkriterien
- [ ] Kueche und Bar koennen unabhaengig fertig melden.
- [ ] Getraenke koennen fertig sein, waehrend Essen noch laeuft.
- [ ] Kein Status `Serviert` in V2.
- [ ] Concurrency/Doppelaktionen erzeugen keine widerspruechlichen Status.
- [ ] Positionen sind ab `In Zubereitung` gegen Aendern/Loeschen gesperrt.
- [ ] Kellner sehen fertige Teile stationsuebergreifend ohne einen zusaetzlichen Serviert-Status.
