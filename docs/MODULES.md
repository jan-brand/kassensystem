# Module

## Zweck

Module kapseln Fachlichkeit. Die Foundation erzeugt keine festen Produktmodule, sondern stellt Regeln und Generatoren bereit.

## Minimaler Zustand

```text
app/Modules/Projects/
├── README.md
└── module.json
```

Erst benötigte Bausteine werden ergänzt. Leere Architekturordner auf Vorrat sollen vermieden werden.

## Manifest

Beispiel:

```json
{
  "name": "Projects",
  "description": "Projektverwaltung",
  "depends_on": ["Identity"],
  "exports": []
}
```

`depends_on` beschreibt explizite Modulabhängigkeiten. `exports` ist bereits vorgesehen, wird in Version 1 aber noch nicht als PHP-Sichtbarkeitsgrenze erzwungen.

## Artefakttypen

Die Foundation unterstützt `model`, `action`, `query`, `service`, `event`, `listener`, `job`, `policy`, `request`, `exception`, `enum`, `contract`, `dto`, `migration`, `factory`, `seeder`, `command` und `controller`.

Beispiele:

```bash
php artisan module:make Projects
php artisan module:make:model Projects Project
php artisan module:make:action Projects CreateProject
php artisan module:make:query Projects FindProjects
php artisan module:make:job Projects RecalculateProjectStats
```

Alternativ generisch:

```bash
php artisan module:make:artifact Projects service ProjectNumberGenerator
```

## Semantik

- **Action**: ein expliziter zustandsverändernder Use Case.
- **Query**: liest oder ermittelt Daten ohne fachlichen Zustand zu verändern.
- **Service**: wiederverwendbare technische oder fachliche Logik, die nicht sinnvoll eine einzelne Action/Query ist.
- **Event**: beschreibt ein bereits eingetretenes Ereignis.
- **Job**: asynchron bzw. queuefähig auszuführende Arbeit.
- **Policy**: Autorisierungsentscheidung.
- **DTO**: transportiert klar definierte Daten ohne versteckten Zustand.

## Migrationen

Modulmigrationen werden unter `app/Modules/<Module>/database/migrations` angelegt und vom Foundation Service Provider geladen. Dadurch bleibt der Datenbankteil eines Moduls näher an der Fachlichkeit.

## Entfernen und Umbenennen

Die Foundation arbeitet absichtlich konservativ. `module:remove` löscht standardmäßig nur das Manifest und nicht rekursiv beliebigen Quellcode. `module:rename` verschiebt ebenfalls nur das Manifest automatisch. Namespace-Refactorings werden nicht blind durchgeführt.

Vor Änderungen:

```bash
php artisan module:graph
php artisan architecture:dependencies Projects
php artisan tooling:history
```
