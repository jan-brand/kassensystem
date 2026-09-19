# Generator Contract

Alle Foundation-Generatoren sollen sich gleich anfühlen.

## Ablauf

1. Eingaben normalisieren.
2. Regeln validieren.
3. Dateiänderungen als `FilePlan` sammeln.
4. Konflikte erkennen.
5. `--dry-run` ermöglichen.
6. Schreiben bzw. Löschen.
7. vorherige Dateizustände in der History erfassen.
8. abschließende Hinweise ausgeben.

## Gemeinsame Optionen

Dateiverändernde Generatoren unterstützen nach Möglichkeit:

- `--dry-run`: zeigt Änderungen, schreibt nichts.
- `--force`: erlaubt bewusstes Überschreiben bzw. das Umgehen ausgewählter Schutzbarrieren.

Weitere Befehle haben familiespezifische Optionen. Interaktive Entscheidungen werden nur verwendet, wenn Input interaktiv ist.

## Warum FilePlan?

Direkte `file_put_contents`-Aufrufe in Generatoren erschweren Vorschau, Undo und konsistentes Fehlerverhalten. `FilePlan` trennt Planung von Ausführung und speichert den Vorzustand in `.foundation/history`.

## Konservative Refactorings

Automatisches Refactoring endet dort, wo eine sichere Aussage nicht mehr möglich ist. Deshalb verschiebt `module:rename` nicht blind sämtliche Namespaces und `design:rename` warnt bei Blade-Code-Referenzen. Ziel ist nachvollziehbare Automatisierung, nicht aggressive Magie.
