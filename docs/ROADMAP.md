# Produkt-Roadmap

## Leitlinie

`v1.0.0` ist die stabile Bargeld-POS-Basis. V2 erweitert das System gezielt zu einem Gastro- und Veranstaltungsbetrieb für einen einzelnen Standort. Funktionen, die zusätzliche Hardware, Warenwirtschaft, standortübergreifende Daten oder komplexere Restaurantabläufe benötigen, werden bewusst später umgesetzt.

Die Roadmap beschreibt Produkt-Scope, keine Release-Termine.

## V2 – Gastro/Kellner/QR

V2 umfasst:

- unveränderlichen Vollstorno für zulässige Nicht-Verzehr-Verkäufe,
- Gastro-Vorgänge mit eigener Vorgangsnummer,
- Bereiche und Tische,
- eigene Kellnerrolle und Touch-Kellner-POS,
- Tagesmenüs, Optionen und QR-Tickets,
- Küche-/Bar-Displays mit unabhängigen Stationsstatus,
- Payment-Ledger mit Bargeld und PayPal.me,
- Positionsrabatte und Tagesangebote,
- öffentliche digitale QR-Belege,
- erweitertes Reporting/Audit/Buchhaltungs-CSV,
- V1-Upgrade und vollständige Acceptance.

Nicht in V2: Lager, Guthaben, Bondrucker, Produkt-Scanning, Tischwechsel, mehrere Standorte, grafischer Raumplan, MwSt/Pfand/TSE und Teilstorno.

Details: `docs/KASSENSYSTEM_V2.md`.

## V3 – Betriebsausbau und Hardware

V3-Kandidaten sind bewusst aus V2 verschoben:

- Teilstorno und späteres Splitten von Zahlungen/Positionen,
- Kunden-/Schülerguthaben mit Barcode oder NFC; Saldo niemals negativ,
- Lager und Bestandsbewegungen,
- Warnung bei Systembestand 0 mit bewusster Bestätigung statt blindem Verkauf,
- Bondrucker-/Hardwareadapter,
- mehrere Standorte,
- Produkt-Barcodes/QR-Scanning,
- Tischwechsel.

Diese Punkte werden erst vor V3 fachlich finalisiert. Details: `docs/KASSENSYSTEM_V3.md`.

## V4 – komplexere Raum- und Restaurantabläufe

Fest vorgemerkt ist ein grafischer Raumplan für Bereiche/Tische. Weitere komplexe Restaurantfunktionen werden erst nach Erfahrungen mit V2/V3 priorisiert.

Details: `docs/KASSENSYSTEM_V4.md`.

## Außerhalb der aktuellen Roadmap

Aktuell nicht als Release-Ziel geplant:

- TSE/Fiskalisierung,
- Pfand/Pfandrückgabe,
- Mehrwertsteuerlogik,
- direkte Kartenterminalintegration,
- vollständige Warenwirtschaft mit Einkauf/Lieferanten,
- Cloud-Mandantenfähigkeit.

Falls diese Anforderungen später real werden, werden sie zuerst fachlich und rechtlich neu bewertet.
