# Entwicklung

## Lokaler Ablauf

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan app:init
php artisan app:doctor
composer dev
```

## Vor einem Commit

```bash
php artisan app:check
php artisan quality:check
npm run build
```

## Arbeitsprinzip

Neue Fachlichkeit beginnt mit einem Modul. UI-Grundbausteine entstehen im Designsystem nur, wenn ein realer Anwendungsfall existiert. Pages verbinden Fachlichkeit und UI; Surfaces definieren den Anwendungskontext.

Bevor ein neues Grundkonzept eingeführt wird, prüfen, ob es Component, Pattern, Template, Page, Modul-Action oder Query sein sollte. Das reduziert parallele Architekturmodelle.

## Git

Empfohlen: `main` als geschützter Integrationsbranch, kurzlebige Feature-/Fix-Branches, Pull Requests und Squash Merge. Die mitgelieferte CI erwartet dieses Modell nicht zwingend, ist darauf aber ausgelegt.
