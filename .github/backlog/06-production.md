## Ziel
Einen reproduzierbaren und sicheren Probebetrieb ermoeglichen.

## Umfang
- Backup-Anleitung
- Restore-Anleitung
- Datenbank-Backup vor Updates
- Produktionskonfiguration
- MySQL/MariaDB-Kompatibilitaet pruefen
- Logging und Fehlerseiten
- Health-Check
- Deployment-/Update-Ablauf dokumentieren

## Akzeptanzkriterien
- [ ] Backup und Restore sind dokumentiert und testbar.
- [ ] Anwendung startet mit Produktionskonfiguration ohne Debug-Modus.
- [ ] Migrationen funktionieren auf der vorgesehenen Produktionsdatenbank.
- [ ] Health-Check liefert einen klaren Zustand.
- [ ] Keine Secrets werden ins Repository committed.
