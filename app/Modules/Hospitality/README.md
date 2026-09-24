# Hospitality

Das Hospitality-Modul bildet den nicht-finanziellen Gastro-Vorgang ab.

Ein offener Tischvorgang ist ausdrücklich **kein** Sale. Er besitzt eine eigene
Vorgangsnummer und kann Produkte oder Menüs mit historischen Snapshots enthalten.
Erst spätere V2-Blöcke verbinden diese Domain mit Kellner-POS, Tickets,
Zubereitungsstationen und dem finanziellen Verkauf.

V2-002 stellt bereit:

- Bereiche und Tische,
- genau einen offenen Vorgang pro Tisch,
- eigene Vorgangsnummern,
- Produkt- und Kategorieoptionen mit ausschließlich nicht-negativen Aufpreisen,
- Menüs mit Auswahlgruppen,
- Freitextnotizen,
- historische Snapshots für Namen, Preise, Optionen und Verzehrkennzeichen.
