## Ziel
Den bereits implementierten unveraenderlichen Vollstorno an die finale V2-Geschaeftsregel anpassen.

## Bereits vorhanden
- Gegenbuchung statt Mutation des Originalverkaufs
- Actor, Zeitpunkt und Pflichtgrund
- Schutz vor doppeltem Storno
- Bargeldwirkung und Audit
- Administration und Tests

## Noch umzusetzen
- Produktkennzeichnung fuer Lebensmittel/Verzehrartikel.
- Essen und Getraenke gelten beide als Verzehrartikel.
- Kennzeichnung wird im Sale-Item-Snapshot historisiert.
- Ein Verkauf mit mindestens einem Verzehrartikel darf in V2 nicht vollstaendig storniert werden.
- Gemischte Verkaeufe sind damit ebenfalls nicht stornierbar.
- Teilstorno bleibt ausserhalb von V2.

## Akzeptanzkriterien
- [ ] Historische Stornierbarkeit haengt nicht von spaeteren Produktupdates ab.
- [ ] Reiner Nicht-Verzehr-Verkauf kann weiterhin vollstaendig storniert werden.
- [ ] Verkauf mit Essen oder Getraenk wird blockiert.
- [ ] Bestehende Idempotenz-, Cash- und Audit-Tests bleiben gruen.
