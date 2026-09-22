# Kassensystem: Produktion, Backup und Restore

Dieses Dokument beschreibt den reproduzierbaren v1-Probebetrieb. Die produktive Zielkonfiguration verwendet MySQL oder MariaDB, `APP_ENV=production` und deaktivierten Debug-Modus.

## Produktionskonfiguration

Produktive Secrets liegen ausschließlich in der `.env` des Servers oder im Secret Store des Hosters und niemals im Repository. Mindestens diese Werte müssen produktionsspezifisch gesetzt werden:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kasse.example.org
APP_KEY=base64:...
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kassensystem
DB_USERNAME=kassensystem
DB_PASSWORD=...

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

KASSENSYSTEM_CURRENCY=EUR
KASSENSYSTEM_TIMEZONE=Europe/Berlin
```

`KASSENSYSTEM_MYSQL_DUMP_BINARY` und `KASSENSYSTEM_MYSQL_CLIENT_BINARY` können gesetzt werden, wenn `mysqldump` und `mysql` nicht im `PATH` liegen. Unter XAMPP ist beispielsweise ein absoluter Pfad unter `C:\xampp\mysql\bin` möglich.

## Produktionscheck

Nach der Konfiguration und nach jedem Deployment:

```bat
php scripts\production\release-artifacts.php
php artisan app:production-check
```

Der Artefakt-Check prüft unter anderem den Vite-Build, Lockfiles, Bootstrap-Cache, Backup-Ziel, Release-Dokumentation und versehentlich öffentlich abgelegte `.env`-Dateien.

Der Produktionscheck prüft zusätzlich Environment, Debug-Modus, APP_KEY/APP_URL, Datenbankzugriff, MySQL/MariaDB-Treiber, ausstehende Migrationen, Demo-Konten, Storage-Schreibrechte, den Public-Storage-Link, Währung, Zeitzone und Repository-Schutz für Secrets/Backups.

`WARN` ist ein Hinweis. `FAIL` beendet den Befehl mit Fehlercode 1 und muss vor dem Probebetrieb behoben werden.

## Health-Check

Laravel stellt weiterhin `/up` als einfachen Framework-Health-Check bereit. Zusätzlich prüft `/health` die Datenbankverbindung:

```text
GET /health
```

Erfolgreich:

```json
{"status":"ok","database":"ok","timestamp":"..."}
```

Bei nicht erreichbarer Datenbank antwortet die Route mit HTTP 503 und gibt bewusst keine Exception- oder Zugangsdaten aus.

## Datenbank-Backup

Vor jedem Update, jeder Migration und jeder manuellen Datenbankänderung:

```bat
php artisan app:backup
```

Standardziel:

```text
storage/app/backups/database/
```

Die Dateien sind über `.gitignore` vom Repository ausgeschlossen. Backups dürfen niemals unter `public/` abgelegt werden.

SQLite wird mit `VACUUM INTO` konsistent gesichert. MySQL/MariaDB verwendet `mysqldump` mit `--single-transaction`. Das Datenbankpasswort wird über die Prozessumgebung übergeben und nicht als Kommandozeilenargument ausgegeben.

Optional können Verbindung und Ziel explizit angegeben werden:

```bat
php artisan app:backup --connection=mysql
php artisan app:backup --path=storage\app\backups\database\vor-update.sql
```

Zusätzlich zur Datenbank muss `storage/app/public/` gesichert werden, wenn dort produktive Uploads wie das Cafeteria-Logo liegen. Diese Dateien gehören nicht in das Datenbank-Backup.

## Restore

Restore ist absichtlich destruktiv und benötigt `--force`:

```bat
php artisan app:restore storage\app\backups\database\20260921_120000_mysql.sql --force
```

Vor dem Restore erzeugt der Befehl automatisch ein Sicherheitsbackup des aktuellen Datenbankstands. Nur wenn die aktuelle Datenbank so beschädigt ist, dass kein Backup mehr möglich ist, darf bewusst darauf verzichtet werden:

```bat
php artisan app:restore <backup> --force --skip-safety-backup
```

Nach einem Restore immer ausführen:

```bat
php artisan migrate:status
php artisan app:production-check
```

## MySQL/MariaDB-Migrationstest vor dem ersten Probebetrieb

Die Migrationskompatibilität wird nicht gegen die produktive Datenbank getestet. Dafür eine leere Testdatenbank auf demselben MySQL-/MariaDB-Typ wie im Betrieb anlegen, die Verbindung temporär darauf zeigen lassen und anschließend ausführen:

```bat
php artisan migrate --force
php artisan migrate:status
php artisan app:production-check
```

Erst wenn alle Migrationen erfolgreich angewendet wurden, darf dieselbe Release-Version produktiv migriert werden. `migrate:fresh` ist auf produktiven oder anderweitig wertvollen Datenbanken verboten.

## Deployment / Update

Vor dem Deployment den technischen RC1-Preflight auf dem freizugebenden Commit ausführen:

```bat
scripts\production\rc1-check.cmd
```

Für den zusätzlichen isolierten MySQL-/MariaDB-Migrationstest:

```bat
scripts\production\rc1-check.cmd --with-mariadb
```

Empfohlener Ablauf:

1. Neue Version vollständig herunterladen bzw. auschecken.
2. `composer install --no-dev --optimize-autoloader` und Frontend-Build vorbereiten.
3. `php artisan app:backup` ausführen und den Pfad notieren.
4. `php artisan down` aktivieren.
5. Dateien der neuen Version bereitstellen.
6. `php artisan migrate --force` ausführen.
7. `php artisan storage:link` sicherstellen.
8. `php artisan optimize` ausführen.
9. `php artisan up` ausführen.
10. `/health`, Login, POS und Administration prüfen.
11. `php artisan app:production-check` ausführen.

Wenn eine Migration oder der Smoke-Test fehlschlägt, Anwendung im Wartungsmodus lassen, Ursache beheben oder das vor dem Update erzeugte Backup wiederherstellen.

## Logs und Fehlerseiten

Produktion verwendet `APP_DEBUG=false`. Technische Details werden in `storage/logs/laravel.log` protokolliert, aber nicht im Browser angezeigt. Für 403, 404, 500 und 503 existieren bewusst einfache deutschsprachige Fehlerseiten ohne Stacktraces oder interne Pfade.

## Backup-Aufbewahrung

Mindestens ein aktuelles Backup muss außerhalb des Webroots und vorzugsweise auf einem zweiten Datenträger oder einem geschützten externen Speicher liegen. Vor Updates erzeugte Backups erst löschen, wenn die neue Version erfolgreich geprüft wurde. Die konkrete Aufbewahrungsfrist ist eine Betriebsentscheidung und wird nicht automatisch durch die Anwendung erzwungen.
