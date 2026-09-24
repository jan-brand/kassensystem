## Ziel
Bestellung/Vorgang fachlich vom finanziellen Verkauf trennen und die Basis fuer Kellner, Menues und Zubereitung schaffen.

## Umfang
- konfigurierbare Bereiche und Tische
- genau ein offener Vorgang pro Tisch in V2
- eigene eindeutige Vorgangsnummer vor dem Verkauf
- Sale-/Rechnungsnummer erst beim tatsaechlichen Verkauf
- Menues mit Auswahlgruppen
- Produkt-/Kategorieoptionen
- Optionen duerfen Aufpreise erzeugen
- Freitext pro Position und Notiz pro Vorgang
- historische Snapshots fuer Namen, Preise, Optionen und fachlich relevante Kennzeichen

## Akzeptanzkriterien
- [ ] Offene Bestellung erzeugt noch keinen Sale und keinen Umsatz.
- [ ] Ein Tisch kann in V2 nicht zwei offene Vorgaenge besitzen.
- [ ] Menue-Slots koennen nur innerhalb ihrer Regeln belegt werden.
- [ ] Historische Vorgangsdaten bleiben trotz Katalogaenderungen nachvollziehbar.
- [ ] Architektur verhindert eine zweite Wahrheit fuer Sale-Summen.
