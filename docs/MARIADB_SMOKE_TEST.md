# MariaDB-/MySQL-Smoke-Test

V1 soll lokal mit SQLite entwickelt werden können und später auf MySQL/MariaDB
portabel bleiben. Der Smoke-Test prüft deshalb die komplette Migration gegen eine
separate temporäre Datenbank, ohne die normale Entwicklungsdatenbank zu verändern.

## XAMPP unter Windows

Zuerst sicherstellen, dass MySQL/MariaDB im XAMPP Control Panel läuft.

In einer normalen CMD:

```bat
cd C:\xampp\htdocs\kassensystem
scripts\production\mariadb-smoke.cmd
```

Die XAMPP-Standardkonfiguration verwendet häufig `root` ohne Passwort. Wenn lokal
ein Passwort gesetzt ist, wird es nicht als Kommandozeilenargument übergeben:

```bat
set KASSENSYSTEM_SMOKE_DB_PASSWORD=DEIN_PASSWORT
scripts\production\mariadb-smoke.cmd --user=root
set KASSENSYSTEM_SMOKE_DB_PASSWORD=
```

Andere Serverdaten:

```bat
scripts\production\mariadb-smoke.cmd --host=127.0.0.1 --port=3306 --user=root
```

## Was der Test macht

1. Verbindung zum Datenbankserver aufbauen.
2. Zufällige Datenbank `kassensystem_smoke_*` anlegen.
3. `php artisan migrate:fresh --force --no-interaction` mit isolierten
   MySQL-Umgebungswerten ausführen.
4. `php artisan migrate:status` prüfen.
5. Zentrale v1-Tabellen kontrollieren.
6. Temporäre Datenbank wieder löschen.

Die normale `.env`, die lokale SQLite-Datenbank und vorhandene Datenbanken werden
nicht verändert.

`--keep` lässt die Testdatenbank zu Diagnosezwecken bestehen. Diese Option sollte
nur bewusst verwendet werden.

## Erfolgsbild

Am Ende muss stehen:

```text
[OK] Verbindung zum Datenbankserver
[OK] Temporäre Datenbank erstellt
[OK] Migrationen
[OK] Migrationsstatus
[OK] Kernschema vollständig
[OK] Temporäre Datenbank wieder entfernt

Smoke-Test erfolgreich.
```

Damit ist die technische MySQL-/MariaDB-Migrationskompatibilität für den getesteten
Stand bestätigt. Ein späteres Produktionsdeployment benötigt trotzdem ein echtes
Backup und einen eigenen Preflight.
