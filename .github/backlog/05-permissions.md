## Ziel
Rollen- und Rechtepruefung konsistent an einer zentralen Stelle abbilden.

## Geplante Rechte
- `pos.access`
- `sales.create`
- `cash_sessions.open`
- `cash_sessions.close`
- `cash_movements.create`
- `administration.access`
- `catalog.manage`
- `users.cashiers.manage`
- `users.roles.manage`
- `sales.view`
- `cash_sessions.view`
- `reports.view`
- `audit.view`
- `settings.manage`

## Akzeptanzkriterien
- [ ] Jede sensible Aktion prueft Berechtigungen serverseitig.
- [ ] Manager koennen nur Kassierer verwalten.
- [ ] Administrator-Funktionen sind klar getrennt.
- [ ] UI blendet nicht erlaubte Aktionen aus, Sicherheit haengt aber nicht davon ab.
- [ ] Tests decken erlaubte und verbotene Rollenpfade ab.
