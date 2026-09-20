## Ziel
Die vorhandenen Systemeinstellungen ueber die Administrationsoberflaeche pflegen koennen.

## Umfang
- Cafeteria-/Systemname
- Kassenname
- Logo
- POS-Anzeigeoptionen
- technische Werte wie Waehrung und Zeitzone bleiben zentral konfiguriert
- Aenderungen werden im Audit protokolliert

## Akzeptanzkriterien
- [ ] Manager/Admin kann erlaubte Einstellungen lesen und speichern.
- [ ] Ungueltige Eingaben werden serverseitig validiert.
- [ ] Aenderungen erscheinen ohne manuelle Datenbankeingriffe im POS.
- [ ] `settings.updated` wird im Audit geschrieben.
- [ ] Tests decken Lesen, Speichern, Validierung und Berechtigungen ab.
