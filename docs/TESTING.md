# Tests

## Grundsatz

Tests sollen die fachliche Struktur spiegeln. Modulspezifische Feature-Tests können unter `tests/Feature/<Module>` liegen. Reine, isolierte Logik gehört nach `tests/Unit`.

## Foundation Tests

Die mitgelieferte Suite enthält einen Unit-Test für den Environment-Synchronisierer. Weitere Foundation-Änderungen sollten insbesondere Generatorplanung, Manifestparser, Dependency-Zyklen und destructive Optionen testen.

```bash
php artisan test
```

## Qualität

```bash
php artisan quality:check
```

Der vollständige Check setzt installierte Dev-Abhängigkeiten voraus.
