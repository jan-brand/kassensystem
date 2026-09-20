# Audit

Das Audit-Modul protokolliert fachlich relevante, nachvollziehbare Änderungen.

## Regeln

- Audit-Ereignisse werden nach dem Schreiben nicht geändert oder gelöscht.
- `before`, `after` und `metadata` werden als JSON gespeichert.
- sensible Schlüssel wie PIN, Passwort, Token und API-Key werden vor dem Speichern redigiert.
- Benutzerinformationen werden als Snapshot gespeichert, damit spätere Profiländerungen historische Einträge nicht verändern.
