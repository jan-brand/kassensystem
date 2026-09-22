## Ziel
Den vorhandenen Code ohne grosse Ignore-Listen auf einen sauberen Qualitaetsstand bringen.

## Umfang
- Laravel Pint projektweit
- Eloquent-Properties fuer Larastan
- generische Relation-Typen
- Pagination-Generics
- Foundation-Array-Typen
- bestehende Artisan-Options-Warnungen untersuchen und korrigieren

## Akzeptanzkriterien
- [x] `php vendor/bin/pint --test` laeuft erfolgreich.
- [x] `php vendor/bin/phpstan analyse --no-progress` laeuft erfolgreich.
- [x] Keine pauschalen Ignore-Regeln fuer neue Kassensystemfehler.
- [x] Alle funktionalen Tests bleiben gruen.
