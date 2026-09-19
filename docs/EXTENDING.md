# Foundation erweitern

## Neuer CLI-Befehl

Neue Foundation-Befehle liegen unter `app/Foundation/Console` und werden im `FoundationServiceProvider` registriert. Dateiverändernde Befehle sollten `FoundationCommand` verwenden und Änderungen über `FilePlan` planen.

## Neue Manifestfamilie

1. Speicherort und JSON-Struktur definieren.
2. `ProjectRegistry` um einen Scanner ergänzen.
3. `*:check` Befehl implementieren.
4. Generator und Inspektionsbefehle ergänzen.
5. `app:check` nur dann erweitern, wenn der Check für praktisch jedes Projekt relevant ist.
6. Dokumentation und Tests ergänzen.

## Neue Modul-Artefakte

`ModuleArtifactGenerator::TYPES` und dessen Mapping erweitern. Für häufig genutzte Typen kann zusätzlich ein dünner `module:make:<type>` Wrapper registriert werden.

## Projektprofile

Version 1 nutzt `foundation.json` für projektbezogene Defaults. Falls später Presets benötigt werden, sollten sie als explizite, versionierte Profile implementiert werden und niemals versteckt Fachmodule installieren.
