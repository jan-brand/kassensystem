# Identity

Verantwortlich für Benutzerkonten, Rollen, PIN-Anmeldung und die zentrale Berechtigungsmatrix.

## V1-Regeln

- Benutzer melden sich mit Benutzername und sechsstelliger PIN an.
- PINs werden ausschließlich gehasht gespeichert.
- Benutzer werden deaktiviert statt gelöscht.
- Rollen: Kassierer, Leitung, Administrator.
- Der letzte aktive Administrator darf nicht deaktiviert oder herabgestuft werden.
- Berechtigungen werden über `Permission`, `AuthorizationService` und Laravel Gates ausgewertet.
- Deaktivierte Benutzer erhalten unabhängig von ihrer Rolle keine Berechtigung.

## Berechtigungsmatrix

Kassierer:
`pos.access`, `sales.create`, `cash_sessions.open`, `cash_sessions.close`, `cash_movements.create`.

Leitung:
alle Kassierer-Rechte plus `administration.access`, `catalog.manage`, `users.cashiers.manage`,
`sales.view`, `cash_sessions.view` und `reports.view`.

Administrator:
alle definierten Berechtigungen, zusätzlich insbesondere `users.roles.manage`, `audit.view`
und `settings.manage`.
