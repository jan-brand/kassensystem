## Ziel
Belege ohne Spezialdrucker per QR-Code digital bereitstellen.

## Umfang
- oeffentliche, schwer erratbare Token-URL ohne Login
- QR-Code auf der POS-/Kellneroberflaeche
- konfigurierbare Aufbewahrungsdauer
- keine unnoetigen personenbezogenen Daten
- erneutes Anzeigen/Nachdrucken alter Belege
- eigener Storno-Beleg

## Akzeptanzkriterien
- [ ] Token ist nicht aus Sale-ID/Rechnungsnummer ableitbar.
- [ ] Abgelaufene Belege sind oeffentlich nicht mehr abrufbar.
- [ ] Belegdaten stammen aus historischen Snapshots.
- [ ] QR-/Abruffehler veraendern keinen abgeschlossenen Sale.

## Implementierung V2-008
- Digitale Belege verwenden zufaellige 48-stellige Tokens; gespeichert werden Hash und verschluesselter Token.
- Die oeffentliche Route kommt ohne Login aus und liefert abgelaufene oder unbekannte Tokens als 404.
- Verkauf und Storno besitzen getrennte digitale Belege. Interne Stornogruende und Mitarbeitende werden oeffentlich nicht ausgegeben.
- Die Aufbewahrungsdauer wird zentral in den Systemeinstellungen gepflegt und beim Ausstellen in `expires_at` festgeschrieben.
- POS, Verkaufsadministration und bezahlte Ticket-Sales im Kellner-POS koennen QR und Link erneut anzeigen.
- QR-/Token-Erzeugung ist von der unveraenderlichen Sale-Buchung getrennt; Fehler veraendern den abgeschlossenen Verkauf nicht.
