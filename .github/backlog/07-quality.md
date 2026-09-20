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
- [ ] `php vendor/bin/pint --test` laeuft erfolgreich.
- [ ] `php vendor/bin/phpstan analyse --no-progress` laeuft erfolgreich.
- [ ] Keine pauschalen Ignore-Regeln fuer neue Kassensystemfehler.
- [ ] Alle funktionalen Tests bleiben gruen.
