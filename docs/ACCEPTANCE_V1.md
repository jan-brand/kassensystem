# Kassensystem v1 – Acceptance Checkliste

Diese Checkliste dokumentiert die abgeschlossene manuelle und automatisierte Abnahme von Kassensystem v1.0.0.

## Sicherheitsregel

Die Demo-Daten sind ausschließlich für `APP_ENV=local` und `APP_ENV=testing` freigegeben.
`app:demo:seed` verweigert andere Umgebungen. Die unten genannten PINs sind reine Demo-Zugangsdaten und dürfen niemals für produktive Konten verwendet werden.

## 1. Fresh Setup

In Windows CMD:

```bat
cd /d C:\xampp\htdocs\kassensystem
composer install
npm install
php artisan migrate:fresh
php artisan app:demo:seed
npm run build
```

Erwartung:

- Migrationen laufen ohne Fehler.
- Der Demo-Seeder meldet drei Benutzer, fünf Kategorien und vierzehn Produkte.
- Eine abgeschlossene Demo-Kassenschicht mit drei Beispielverkäufen wird erzeugt.
- Nach dem Seed ist keine Kasse offen.

Für einen vollständig leeren Transaktionsbestand:

```bat
php artisan migrate:fresh
php artisan app:demo:seed --no-history
```

## 2. Demo-Zugänge

| Rolle | Benutzername | PIN |
| --- | --- | --- |
| Administrator | `demo-admin` | `910001` |
| Manager | `demo-manager` | `910002` |
| Kassierer | `demo-kasse` | `910003` |

Die PINs werden in der Datenbank ausschließlich gehasht gespeichert.

## 3. Automatisierte Abnahme

Der vollständige technische Release-Check wird über einen einzigen Composer-Befehl ausgeführt:

```bat
composer qa:release
```

Dieser Befehl umfasst Pint, PHPStan, die vollständige Test-Suite, `app:check`, den Frontend-Build und zusätzlich die Modul-, Architektur-, Permission-, Navigation-, Page- und Acceptance-Checks.

Für gezielte Fehlersuche können die Gates weiterhin einzeln ausgeführt werden:

```bat
composer qa:static
php artisan app:check
php artisan module:check
php artisan architecture:check
php artisan permission:check
php artisan navigation:check
php artisan page:check
php artisan test tests\Feature\Acceptance
php artisan test
npm run build
```

Alle Befehle müssen ohne Fehler enden.

## 4. Rollen und Login

- [x] Kassierer kann sich mit `demo-kasse` anmelden.
- [x] Manager kann sich mit `demo-manager` anmelden.
- [x] Administrator kann sich mit `demo-admin` anmelden.
- [x] Kassierer sieht keine Administration.
- [x] Manager sieht Administration, aber keine Settings-/Audit-/Rollenfunktionen.
- [x] Administrator erreicht alle vorgesehenen Administrationsbereiche.
- [x] Falsche PINs werden abgelehnt.
- [x] Nach fünf Fehlversuchen greift die konfigurierte Sperre.

## 5. POS Kernablauf

Mit `demo-kasse`:

- [x] Kasse mit einem Anfangsbestand öffnen.
- [x] Kategorien horizontal wechseln.
- [x] Produktsuche verwenden.
- [x] Mehrere Produkte hinzufügen.
- [x] Dasselbe Produkt mehrfach hinzufügen und Mengenaggregation prüfen.
- [x] Menge über `+` und `−` ändern.
- [x] Warenkorb verwerfen und integrierte Statusmeldung prüfen.
- [x] Neuen Warenkorb aufbauen.
- [x] Bezahldialog öffnen.
- [x] Bargeldbetrag größer als Gesamtbetrag eingeben.
- [x] Rückgeld korrekt prüfen.
- [x] Verkauf abschließen.
- [x] Doppel-Tap/Doppelklick erzeugt keinen zweiten Verkauf.
- [x] 0-Euro-Produkt `Gratis Wasser` lässt sich ohne Payment abschließen.

## 6. Bargeldbewegungen

Bei offener Kasse:

- [x] Einlage mit Betrag und Grund erfassen.
- [x] Entnahme mit Betrag und Grund erfassen.
- [x] Entnahme über Sollbestand wird abgelehnt.
- [x] Bewegungen erscheinen im Kassenkontext.
- [x] Actor und Zeit sind im Audit nachvollziehbar.

## 7. Benutzerwechsel

- [x] Benutzerwechsel ohne offenen Warenkorb funktioniert.
- [x] Benutzerwechsel mit offenem Warenkorb wird blockiert.
- [x] Nach erneutem Login ist die aktive Kassenschicht weiterhin vorhanden.

## 8. Kassenabschluss

- [x] Abschluss mit offenem Warenkorb wird blockiert.
- [x] Abschluss starten.
- [x] Sollbestand entspricht Startbestand + Barumsatz + Einlagen − Entnahmen.
- [x] Abschluss ohne Differenz funktioniert.
- [x] Bei Differenz ist ein Kommentar verpflichtend.
- [x] Geschlossene Session bleibt geschlossen und unveränderlich.

## 9. Administration

Als Manager oder Administrator:

- [x] Katalog zeigt Demo-Kategorien und Demo-Produkte.
- [x] Kassiererverwaltung funktioniert gemäß Rolle.
- [x] Sales-Suche findet abgeschlossene Verkäufe.
- [x] Belegansicht zeigt gespeicherte Produktnamen und Preise als Snapshots.
- [x] Received/Change werden bei Barzahlung korrekt angezeigt.
- [x] 0-Euro-Verkauf wird ohne erfundenes Payment angezeigt.

## 10. Reporting und CSV

- [x] Tagesreport zeigt Anzahl Verkäufe und Umsatz.
- [x] Produktmengen und Produktumsätze sind plausibel.
- [x] Kassenschicht zeigt Startbestand, Einlagen, Entnahmen, Soll, gezählt und Differenz.
- [x] CSV lässt sich exportieren und enthält die erwarteten Daten.
- [x] Die mit `app:demo:seed` erzeugte Demo-Historie enthält drei Verkäufe, davon einen 0-Euro-Verkauf und insgesamt 8,00 EUR Umsatz.

## 11. Audit

Als Administrator:

- [x] Login-/Benutzerereignisse sind auffindbar.
- [x] Verkauf, Kassenöffnung/-abschluss und Bargeldbewegungen sind auffindbar.
- [x] Keine PINs oder Secrets erscheinen in Audit-Payloads.
- [x] Audit-Datensätze sind nur lesbar.

## 12. Backup und Restore

Vor dem Restore immer mit einer lokalen Testdatenbank arbeiten.

```bat
php artisan app:backup
```

- [x] Backup-Datei wird unter `storage\app\backups\database` erstellt.
- [x] Dateigröße ist plausibel.
- [x] Safety-Backup-Verhalten vor Restore wurde geprüft.
- [x] Restore wurde mindestens einmal gegen eine lokale Testdatenbank erfolgreich durchgeführt.
- [x] Nach Restore funktionieren Login, POS und Reporting weiterhin.

## 13. Smartphone / Tablet / Desktop

```bat
scripts\mobile\mobile.cmd doctor
scripts\mobile\mobile.cmd start
```

Jeweils Smartphone, Tablet und Desktop prüfen:

- [x] Login vollständig bedienbar.
- [x] Produktwahl schnell und ohne Fehl-Taps.
- [x] Warenkorb jederzeit erreichbar.
- [x] Bottom Sheet lässt sich sicher bedienen.
- [x] Bezahlen und Rückgeld sind ohne horizontales Scrollen sichtbar.
- [x] Hoch- und Querformat funktionieren.
- [x] Touch-Ziele sind ausreichend groß.
- [x] Administration ist mobil navigierbar.
- [x] Status-/Fehlermeldungen passen visuell in das neutrale POS-Design.

## 14. Release-Gate

V1-008 wurde geschlossen, nachdem:

- [x] diese manuelle Checkliste vollständig durchgeführt wurde,
- [x] `php artisan test` vollständig grün ist,
- [x] `npm run build` grün ist,
- [x] keine offenen P0/P1-Fehler für den Probebetrieb vorhanden sind,
- [x] ein realer Smartphone-/Tablet-Test dokumentiert wurde.

Die manuelle Abnahme wurde vollständig durchgeführt und in `release\v1-acceptance.json` dokumentiert.

Datum: 2026-09-23

Tester: JB

Commit: d08da7f31553f9c81b79f603243ac6419544b9d0

Ergebnis: [x] PASS  [ ] FAIL

Notizen:

Desktop, Smartphone/Tablet, Backup/Restore und Production-Check wurden erfolgreich geprüft.
