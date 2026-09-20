# Identity

Verantwortlich für Benutzerkonten, Rollen und PIN-Anmeldung.

## V1-Regeln

- Benutzer melden sich mit Benutzername und sechsstelliger PIN an.
- PINs werden ausschließlich gehasht gespeichert.
- Benutzer werden deaktiviert statt gelöscht.
- Rollen: Kassierer, Leitung, Administrator.
- Der letzte aktive Administrator darf nicht deaktiviert oder herabgestuft werden.
