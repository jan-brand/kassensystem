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

## Implementierung V2-006
- `payments` ist ein unveraenderliches Ledger mit mehreren Eintraegen pro Sale; der v1-Barzugriff bleibt kompatibel.
- Sale-Abschluss validiert Payment-Summe und Sale-Summe in derselben Transaktion. Nur Baranteile veraendern den Bargeld-Sollbestand.
- PayPal.me wird zentral in den Systemeinstellungen gepflegt. Der POS erzeugt einen betragsgebundenen QR-Code lokal ohne externen QR-Dienst.
- Ein angezeigter PayPal.me-QR bucht nichts. Erst die ausdrueckliche Mitarbeiterbestaetigung erzeugt den Payment-Eintrag und ein Audit-Ereignis.
- 0-Euro-Verkaeufe bleiben ohne Payment; PayPal-API/Webhooks bleiben ausserhalb von V2-006.
