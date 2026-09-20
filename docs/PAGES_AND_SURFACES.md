# Pages und Surfaces

## Surface

Eine Surface ist ein eigenständiger Anwendungsbereich. Beispiele sind `public`, `portal`, `administration`, `staff`, `partner` oder `api`. Die Foundation schreibt keine festen Rollen vor.

Ein Surface-Manifest kann Prefix, Domain, Middleware und Layout festlegen.

```bash
php artisan surface:make portal --prefix=app --middleware=web --middleware=auth
```

## Page

Eine Page ist die erste wirklich konkrete UI-Ebene. Sie verbindet Route, Surface, Layout, optional Permission und Designbausteine.

```bash
php artisan page:make projects.index \
  --surface=portal \
  --uri=/projects \
  --permission=projects.read
```

View-Pages werden automatisch als Routen registriert. Das erzeugte Blade-Template erbt das Layout der Surface.

## Navigation

Navigation ist Metadatum statt hart codierte Liste:

```bash
php artisan navigation:add portal Projekte portal.projects.index --permission=projects.read
php artisan navigation:list portal
php artisan navigation:check
```

Die Navigation kann später in Layouts aus der Registry gerendert werden. Version 1 konzentriert sich zunächst auf Katalog und Konsistenzprüfung.

## Permissions

```bash
php artisan permission:make projects.read
php artisan permission:sync
php artisan permission:check
```

`permission:sync` übernimmt Berechtigungen, die bereits in Page- oder Navigation-Manifesten referenziert sind, in den zentralen Katalog. Das `permission` Feld ist in Version 1 Architekturmetadatum und erzwingt allein noch keinen Zugriffsschutz. Ein konkretes Projekt muss es über Middleware, Policies oder Gates anbinden. Die Foundation implementiert bewusst noch kein Rollenmodell. Ein Projekt kann Laravel Policies, Gates, eigene RBAC-Module oder externe IAM-Lösungen ergänzen.

## Route-Prüfungen

```bash
php artisan route:manifest
php artisan route:check
php artisan route:unused
```

`route:check` prüft doppelte oder fehlende Route-Namen innerhalb der Page-Manifeste. Laravel-eigene Routen bleiben weiterhin mit `php artisan route:list` sichtbar.

## Dynamische Livewire-Seiten

Neben statischen `view`-Handlern können Page-Manifeste einen `livewire`-Handler verwenden. Der `target` ist dann der vollständig qualifizierte Klassenname einer `Livewire\\Component`. Zusätzlich kann ein Page-Manifest ein eigenes `middleware`-Array definieren; diese Middleware wird mit der Middleware der Surface kombiniert.

Beispiel:

```json
{
  "name": "register",
  "surface": "pos",
  "uri": "/",
  "route_name": "pos.register",
  "middleware": ["auth"],
  "handler": {
    "type": "livewire",
    "target": "App\\Surfaces\\Pos\\Livewire\\RegisterScreen"
  }
}
```
