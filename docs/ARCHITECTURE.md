# Architektur

## Zielbild

Die Foundation ist ein modularer Monolith. Ein Deployment, eine Laravel-Anwendung und typischerweise eine Datenbank bleiben erhalten, während fachliche Grenzen bewusst sichtbar gemacht und automatisiert geprüft werden.

```text
Application
├── Foundation        internes Entwicklungsframework
├── Modules           fachliche Einheiten
├── Design System     Tokens, Components, Patterns, Templates
├── Surfaces          eigenständige Anwendungsbereiche
├── Pages             konkrete Seiten
├── Navigation        surfacebezogene Navigation
├── Permissions       zentraler Berechtigungskatalog
├── Tests
└── Docs
```

## Foundation versus Fachlogik

`app/Foundation` enthält ausschließlich Mechanismen, die unabhängig vom konkreten Produkt wiederverwendbar sind: CLI, Registry, Generatoren, Env-Sync, Architekturprüfung, Historie und Diagnose. Konkrete Geschäftslogik liegt unter `app/Modules`.

Beispiele für **nicht** generische Module: `Membership`, `Billing`, `Projects`, `Orders`, `Tickets`, `Events`. Diese werden erst in einem konkreten Projekt erzeugt.

## Manifeste als gemeinsame Sprache

Die Foundation verwendet kleine JSON-Manifeste als maschinenlesbare Beschreibung. Source of Truth bleiben diese Manifeste; generierte Kataloge oder Registry-Snapshots sind abgeleitete Artefakte.

Verwendete Manifesttypen:

- `app/Modules/<Module>/module.json`
- `resources/design/components/<Name>/component.json`
- `resources/design/patterns/<Name>/pattern.json`
- `resources/design/templates/<Name>/template.json`
- `resources/surfaces/<surface>/surface.json`
- `resources/pages/<surface>/<page>/page.json`
- `resources/navigation/<surface>.json`
- `resources/permissions/permissions.json`

## Abhängigkeitsrichtung

Module deklarieren direkte Modulabhängigkeiten. `architecture:check` prüft fehlende Ziele und Zyklen. Die Foundation verhindert nicht jeden beliebigen PHP-Import statisch; dafür ist Larastan/PHPStan vorgesehen. Die Manifestprüfung schafft jedoch eine explizite Architekturkarte.

Das Designsystem hat strengere Hierarchieregeln:

```text
Component → Component erlaubt
Pattern   → Component / Pattern erlaubt
Template  → Component / Pattern erlaubt
Page      → Template / Pattern / Component erlaubt
```

Nicht erlaubt sind Aufwärtsabhängigkeiten, etwa `Component → Pattern`, sowie Template-zu-Template-Verschachtelungen und Zyklen.

## Laufzeitregistrierung von Pages

View-basierte Page-Manifeste werden durch `FoundationServiceProvider` als Laravel-Routen registriert. Die zugehörige Surface liefert Prefix, Domain und Middleware. Komplexe Pages können später auf explizite Controller-/Livewire-/Inertia-Routen umgestellt werden; das Manifest dient dann weiterhin als Architekturmetadatum.

## Modul-Infrastruktur

Der Service Provider lädt pro Modul automatisch:

- `database/migrations`
- `resources/views` unter einem Modul-View-Namespace
- `routes/web.php`, falls vorhanden

Damit kann ein Modul bei wachsender Komplexität weitgehend in sich geschlossen bleiben.

## Entwicklungsdashboard

Das lokale `/__foundation` Dashboard ist eine visuelle Inspektionsfläche. Es zeigt bewusst nur Metadaten aus der Registry und ersetzt weder Tests noch die CLI-Prüfungen.

## Sicherheitsprinzip

Generierte oder lokale Entwicklungsfunktionen dürfen keine Produktionssicherheit schwächen. Das Dashboard ist standardmäßig local-only, `.env`-Werte werden bei normalen Synchronisationen nicht überschrieben und destructive Befehle verlangen bewusste Optionen.
