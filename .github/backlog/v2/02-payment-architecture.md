## Ziel
Bargeldzahlung in ein allgemeines, erweiterbares Payment-Ledger ueberfuehren.

## Umfang
- explizite Zahlungsart pro Payment
- bestehende v1-Barzahlungen bleiben kompatibel
- mehrere Payment-Buchungen pro Verkauf technisch moeglich
- klare Summe wirksamer Payments
- Bargeldwirkung getrennt von nicht-barer Zahlung
- 0-Euro-Verkaeufe ohne Payment
- providerneutrale Contracts

## Akzeptanzkriterien
- [ ] Bestehende v1-Barverkaeufe bleiben unveraendert lesbar.
- [ ] Neue Barverkaeufe verhalten sich funktional wie in v1.
- [ ] Nicht-bare Payment-Typen veraendern den Kassenbestand nicht.
- [ ] Payment-Summe und Verkaufssumme werden fachlich validiert.
- [ ] Payment-Buchungen bleiben nach Abschluss unveraenderlich.
- [ ] Tests decken Migration, Barzahlung, 0-Euro und Mehrfach-Payments ab.
