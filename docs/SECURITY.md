# Security

## Foundation-spezifische Sicherheitsregeln

- Dashboard standardmäßig nur im lokalen Environment.
- `.env` Dateien niemals committen.
- `env:sync` erhält Werte standardmäßig.
- `env:sync --force` erzeugt ein Backup und verlangt Bestätigung.
- Generatoren schreiben standardmäßig nicht über existierende Dateien, sofern eine Operation nicht explizit als kontrolliertes Update markiert ist.
- `--force` ist bewusst sichtbar und darf nicht als normaler Standard verwendet werden.
- Manifestdaten sind Konfiguration, keine vertrauenswürdigen Benutzereingaben.

## Anwendungssicherheit

Authentifizierung, Autorisierung, Rate Limits, CSRF, Uploadvalidierung, Content Security Policy, Datenschutz und Secret Management sind projektabhängig und müssen im konkreten Produkt ausgearbeitet werden. Die Foundation ersetzt kein Threat Modeling.
