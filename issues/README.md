# Lokales Issue-System

Dieses Verzeichnis ist die lokale, offline nutzbare Quelle fuer den Projekt-Backlog.
Eine Internetverbindung oder GitHub CLI ist fuer den normalen Arbeitsablauf nicht erforderlich.

Die Metadaten stehen in `issues/issues.json`. Die ausfuehrlichen Beschreibungen der initialen
v1-Aufgaben liegen weiterhin unter `.github/backlog/` und werden vom lokalen CLI angezeigt.
Neue lokale Issues erhalten ihre Beschreibung unter `issues/backlog/`.

## Aufruf aus CMD

```bat
scripts\issues\issues.cmd list
scripts\issues\issues.cmd next
scripts\issues\issues.cmd show V1-001
scripts\issues\issues.cmd start V1-001
scripts\issues\issues.cmd close V1-001
scripts\issues\issues.cmd reopen V1-001
scripts\issues\issues.cmd new
scripts\issues\issues.cmd stats
scripts\issues\issues.cmd doctor
```

`list` zeigt standardmaessig alle nicht geschlossenen Issues. Beispiele fuer Filter:

```bat
scripts\issues\issues.cmd list --priority=P1
scripts\issues\issues.cmd list --status=in-progress
scripts\issues\issues.cmd list --area=reporting
scripts\issues\issues.cmd list --all
```

## Arbeitsweise

1. `next` zeigt die naechste konkrete Aufgabe. Epics werden dabei uebersprungen.
2. Vor Beginn wird die Aufgabe mit `start <ID>` auf `in-progress` gesetzt.
3. Die Beschreibung wird mit `show <ID>` gelesen.
4. Nach Umsetzung, Tests und Dokumentation wird sie mit `close <ID>` geschlossen.
5. Aenderungen an `issues/issues.json` und neuen Body-Dateien werden ganz normal mit Git committed.

Damit bleibt der komplette Arbeitsstand lokal versioniert. GitHub kann spaeter als Spiegel
hinzukommen; die lokale Entwicklung haengt nicht davon ab.
