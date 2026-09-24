# Lokales Issue-System

Dieses Verzeichnis ist die im Repository versionierte Quelle für den Projekt-Backlog.
Eine Internetverbindung oder GitHub CLI ist für den normalen Arbeitsablauf nicht erforderlich.

Die Metadaten stehen in `issues/issues.json`. Ausführliche Roadmap-Beschreibungen liegen unter
`.github/backlog/`. Neue lokale Einzel-Issues können weiterhin unter `issues/backlog/` angelegt werden.

GitHub kann den Backlog spiegeln; für die Entscheidung, was im aktuellen Repository geplant,
in Arbeit oder abgeschlossen ist, bleibt jedoch der committed Stand von `issues/issues.json`
maßgeblich.

## Aufruf aus CMD

```bat
scripts\issues\issues.cmd list
scripts\issues\issues.cmd next
scripts\issues\issues.cmd show V2-001
scripts\issues\issues.cmd start V2-001
scripts\issues\issues.cmd close V2-001
scripts\issues\issues.cmd reopen V2-001
scripts\issues\issues.cmd new
scripts\issues\issues.cmd stats
scripts\issues\issues.cmd doctor
```

`list` zeigt standardmäßig alle nicht geschlossenen Issues. Beispiele:

```bat
scripts\issues\issues.cmd list --milestone=v2
scripts\issues\issues.cmd list --priority=P1
scripts\issues\issues.cmd list --status=in-progress
scripts\issues\issues.cmd list --area=reporting
scripts\issues\issues.cmd list --all
```

## Arbeitsweise

1. Für aktive Entwicklung zuerst den gewünschten Milestone prüfen, aktuell `v2`.
2. `next` zeigt die nächste konkrete Aufgabe; Epics werden übersprungen.
3. Vor Beginn wird die Aufgabe mit `start <ID>` auf `in-progress` gesetzt.
4. Die Beschreibung wird mit `show <ID>` gelesen.
5. Nach Umsetzung, Tests, Dokumentation und erfolgreichem CI-Lauf wird sie mit `close <ID>` geschlossen.
6. Änderungen an Registry und Body-Dateien werden zusammen mit der fachlichen Arbeit versioniert.

V3- und V4-Issues sind Roadmap-Vormerkungen. Sie werden nicht vorgezogen, solange V2 aktiv ist.

## GitHub Issue Templates

Unter `.github/ISSUE_TEMPLATE/` stehen strukturierte Formulare für Fehler, Features,
technische Aufgaben und QA-Aufgaben bereit. Sicherheitslücken sollen nicht als öffentliches
Issue angelegt werden; dafür sind die vertraulichen Security Advisories vorgesehen.
