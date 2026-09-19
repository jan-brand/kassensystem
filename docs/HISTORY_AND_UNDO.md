# History und Undo

## Motivation

Generatoren sind wertvoller, wenn Entwickler ihnen vertrauen können. Neben `--dry-run` speichert die Foundation deshalb bei dateiverändernden Befehlen den vorherigen Inhalt der betroffenen Dateien.

## Anzeigen

```bash
php artisan tooling:history
```

Die History liegt unter `.foundation/history/` und wird nicht committed. Environment-Synchronisation wird bewusst nicht in dieser generischen History gespeichert, damit Secrets nicht zusätzlich in Base64-Historydateien dupliziert werden. Force-Env-Sync verwendet stattdessen die expliziten Env-Backups.

## Letzten Schritt zurücknehmen

```bash
php artisan tooling:undo
```

Der Befehl stellt den vorherigen Inhalt wieder her bzw. entfernt Dateien, die beim letzten Schritt neu erzeugt wurden.

## Grenzen

Undo betrifft Foundation-Dateioperationen. Es ist **kein Ersatz für Git**, keine Datenbank-Rollback-Engine und keine Transaktion über externe Dienste. Git bleibt die primäre Versionskontrolle.

Vor großen Refactorings weiterhin einen Branch bzw. Commit erstellen.
