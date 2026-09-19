# Environment Management

## Grundsatz

`.env.example` ist die kanonische **Struktur**, aber nicht die Quelle realer Secrets. Der normale Sync übernimmt Reihenfolge, Kommentare und neue Keys, bewahrt jedoch existierende Zielwerte.

## Normaler Sync

```bash
php artisan env:sync
```

Ohne `--target` werden die in `config/foundation.php` konfigurierten Ziele synchronisiert, standardmäßig `.env` und `.env.testing`.

Beispiel:

`.env.example`:

```dotenv
DB_PASSWORD=
NEW_FEATURE=false
```

`.env` vor Sync:

```dotenv
DB_PASSWORD=really-secret
```

`.env` danach:

```dotenv
DB_PASSWORD=really-secret
NEW_FEATURE=false
```

## Force

```bash
php artisan env:sync --force
```

Force übernimmt die Beispielwerte aus `.env.example`. Existierende Secrets können dadurch verloren gehen. Deshalb erstellt Force automatisch ein Backup in `.foundation/env-backups` und fragt interaktiv nach Bestätigung. In nichtinteraktiven Pipelines muss die Absicht mit `--yes` bestätigt werden.

## Dry Run

```bash
php artisan env:sync --dry-run
```

Zeigt die geplanten Schreiboperationen ohne Änderung.

## Extra Keys

Zielvariablen, die nicht im Template vorkommen, werden standardmäßig am Ende unter `Extra variables preserved by env:sync` erhalten. Mit `--prune` können sie bewusst entfernt werden.

## Diff und Check

```bash
php artisan env:diff .env
php artisan env:check
```

`env:check` schlägt fehl, wenn notwendige Keys fehlen. Extra Keys sind nur Information.

## Backup und Restore

```bash
php artisan env:backup
php artisan env:restore
```

Ohne explizite Backup-Datei verwendet `env:restore` das jüngste Backup. Mit `--target` lässt sich das Ziel festlegen.

## Sicherheitsregeln

- `.env` und `.env.*` sind ignoriert; nur `.env.example` wird versioniert.
- Der CLI-Diff zeigt keine Secret-Werte.
- Normale Synchronisation überschreibt keine existierenden Werte.
- Force ist opt-in und erzeugt ein Backup.
- Produktionssecrets gehören weiterhin in den Secret Store der jeweiligen Plattform.
