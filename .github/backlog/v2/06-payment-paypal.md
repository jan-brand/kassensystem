## Ziel
Das v1-Payment zu einem Ledger erweitern und PayPal.me als einzige neue V2-Zahlungsart ermoeglichen.

## Umfang
- bestehende v1-Barpayments kompatibel halten
- mehrere Payment-Buchungen technisch ermoeglichen
- Bargeldwirkung getrennt von nicht-baren Payments
- PayPal.me-Konto zentral pro Standort
- QR mit konkretem Betrag
- Mitarbeiter bestaetigt Zahlung nach Sichtpruefung manuell
- angezeigter QR gilt noch nicht als Zahlung
- 0-Euro-Verkauf bleibt ohne erfundenes Payment
- Architektur fuer spaeteres Guthaben + Restzahlung offenhalten

## Nicht im Scope
- PayPal-API/Webhook
- Kartenterminal
- automatisches Erkennen einer PayPal-Zahlung

## Akzeptanzkriterien
- [ ] Payment-Summe und Sale-Summe werden atomar validiert.
- [ ] PayPal veraendert keinen Bargeld-Sollbestand.
- [ ] Manuelle PayPal-Bestaetigung ist auditiert.
- [ ] Doppelbestaetigung erzeugt kein zweites Payment.
