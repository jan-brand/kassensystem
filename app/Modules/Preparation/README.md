# Preparation

V2-005 verteilt Hospitality-Positionen nachvollziehbar auf frei definierbare Zubereitungsstationen.

- Produkte koennen mehreren Stationen zugeordnet werden.
- Menuekomponenten verwenden die Stationszuordnung ihres jeweiligen Produkts.
- Pro Quelle und Station entsteht genau ein Arbeitsauftrag mit den Stati `new`, `in_preparation` und `ready`.
- Statuswechsel sind transaktionsgesichert und idempotent fuer Wiederholungen desselben Zielstatus.
- Sobald irgendein Arbeitsauftrag einer Hospitality-Position `in_preparation` oder `ready` ist, sind Aendern und Loeschen dieser Position gesperrt.
- Neue Positionen am gleichen offenen Vorgang erzeugen weiterhin neue Arbeitsauftraege.
- Ein fertiger Teil erzeugt keinen Umsatz und keinen zusaetzlichen Status `served`.
- Browser-Displays zeigen den gesamten betroffenen Vorgang; der Anteil der ausgewaehlten Station wird hervorgehoben.
- Der Benachrichtigungston ist je Station konfigurierbar und wird lokal im Browser erzeugt.
