# Audit

Das Audit-Modul protokolliert fachlich relevante, nachvollziehbare Änderungen.

## Regeln

- Audit-Ereignisse werden nach dem Schreiben nicht geändert oder gelöscht.
- `before`, `after` und `metadata` werden als JSON gespeichert.
- sensible Schlüssel wie PIN, Passwort, Token und API-Key werden vor dem Speichern redigiert.
- Benutzerinformationen werden als Snapshot gespeichert, damit spätere Profiländerungen historische Einträge nicht verändern.

## Administration

- Audit-Ereignisse sind unter `/administration/audit` ausschließlich für Administratoren lesbar.
- Der Browser filtert nach Zeitraum, Event-Key, Benutzer und Subject.
- Die Detailansicht ist read-only und zeigt die bereits redigierten `before`-, `after`- und `metadata`-Payloads.
