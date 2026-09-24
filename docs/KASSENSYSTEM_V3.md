# Kassensystem v3 – vorgemerkter Scope

V3 ist noch keine aktive Entwicklung. Die folgenden Themen wurden bewusst aus V2 verschoben, damit V2 als großer, aber beherrschbarer Gastro-Release abgeschlossen werden kann.

## Kandidaten

### Teilstorno und Split-Zahlung

V2 bleibt beim Vollstorno. V3 soll fachlich klären und umsetzen, wie einzelne Positionen/Mengen storniert und Verkäufe bzw. Zahlungen sinnvoll geteilt werden.

### Schüler-/Kundenguthaben

Guthaben wird als unveränderliches Ledger geplant. Identifikation soll über Barcode oder NFC möglich sein. Ein Saldo darf niemals negativ werden. Aufladung, Korrektur, Sperrung und Restzahlung werden vor Umsetzung exakt definiert.

### Lager

Bestand wird aus Bewegungen aufgebaut. Verkauf reduziert, zulässige Rückabwicklung erhöht, manuelle Korrektur benötigt Actor und Grund.

Wenn der Systembestand 0 erreicht, soll der POS nicht blind blockieren: Mitarbeiter werden gewarnt und müssen bewusst bestätigen können, dass physisch noch Ware vorhanden ist, damit ein fehlerhafter Systembestand den Betrieb nicht stoppt. Die Abweichung muss nachvollziehbar werden.

### Bondrucker

V3 kann konkrete Beleg-/Hardwareadapter ergänzen. Der digitale V2-Beleg bleibt Fallback.

### Mehrere Standorte

V2 bleibt ein Standort mit mehreren Kassen. V3 soll klären, welche Daten standortgebunden sind und wie Berechtigungen, Kassen, Katalog und Reporting getrennt werden.

### Produkt-Scanning

Barcode-/QR-Erfassung von Produkten wird für den späteren Einsatz auch mit Schulbedarf vorbereitet. Das darf nicht mit den V2-Menü-/Ticket-QRs verwechselt werden.

### Tischwechsel

V2 bindet einen offenen Vorgang an seinen Tisch. Tischwechsel kommt erst in V3 und muss Vorgangs- sowie Zubereitungshistorie unverändert nachvollziehbar halten.

## Nicht automatisch Teil von V3

Ein grafischer Raumplan ist für V4 vorgesehen. TSE, MwSt und Pfand werden nicht allein durch den Start von V3 in den Scope aufgenommen.
