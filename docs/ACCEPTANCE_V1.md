# Kassensystem v1 – Acceptance Checkliste

Diese Checkliste ist für einen realistischen lokalen Probebetrieb vor dem v1 Release Candidate gedacht.

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

- [ ] Kassierer kann sich mit `demo-kasse` anmelden.
- [ ] Manager kann sich mit `demo-manager` anmelden.
- [ ] Administrator kann sich mit `demo-admin` anmelden.
- [ ] Kassierer sieht keine Administration.
- [ ] Manager sieht Administration, aber keine Settings-/Audit-/Rollenfunktionen.
- [ ] Administrator erreicht alle vorgesehenen Administrationsbereiche.
- [ ] Falsche PINs werden abgelehnt.
- [ ] Nach fünf Fehlversuchen greift die konfigurierte Sperre.

## 5. POS Kernablauf

Mit `demo-kasse`:

- [ ] Kasse mit einem Anfangsbestand öffnen.
- [ ] Kategorien horizontal wechseln.
- [ ] Produktsuche verwenden.
- [ ] Mehrere Produkte hinzufügen.
- [ ] Dasselbe Produkt mehrfach hinzufügen und Mengenaggregation prüfen.
- [ ] Menge über `+` und `−` ändern.
- [ ] Warenkorb verwerfen und integrierte Statusmeldung prüfen.
- [ ] Neuen Warenkorb aufbauen.
- [ ] Bezahldialog öffnen.
- [ ] Bargeldbetrag größer als Gesamtbetrag eingeben.
- [ ] Rückgeld korrekt prüfen.
- [ ] Verkauf abschließen.
- [ ] Doppel-Tap/Doppelklick erzeugt keinen zweiten Verkauf.
- [ ] 0-Euro-Produkt `Gratis Wasser` lässt sich ohne Payment abschließen.

## 6. Bargeldbewegungen

Bei offener Kasse:

- [ ] Einlage mit Betrag und Grund erfassen.
- [ ] Entnahme mit Betrag und Grund erfassen.
- [ ] Entnahme über Sollbestand wird abgelehnt.
- [ ] Bewegungen erscheinen im Kassenkontext.
- [ ] Actor und Zeit sind im Audit nachvollziehbar.

## 7. Benutzerwechsel

- [ ] Benutzerwechsel ohne offenen Warenkorb funktioniert.
- [ ] Benutzerwechsel mit offenem Warenkorb wird blockiert.
- [ ] Nach erneutem Login ist die aktive Kassenschicht weiterhin vorhanden.

## 8. Kassenabschluss

- [ ] Abschluss mit offenem Warenkorb wird blockiert.
- [ ] Abschluss starten.
- [ ] Sollbestand entspricht Startbestand + Barumsatz + Einlagen − Entnahmen.
- [ ] Abschluss ohne Differenz funktioniert.
- [ ] Bei Differenz ist ein Kommentar verpflichtend.
- [ ] Geschlossene Session bleibt geschlossen und unveränderlich.

## 9. Administration

Als Manager oder Administrator:

- [ ] Katalog zeigt Demo-Kategorien und Demo-Produkte.
- [ ] Kassiererverwaltung funktioniert gemäß Rolle.
- [ ] Sales-Suche findet abgeschlossene Verkäufe.
- [ ] Belegansicht zeigt gespeicherte Produktnamen und Preise als Snapshots.
- [ ] Received/Change werden bei Barzahlung korrekt angezeigt.
- [ ] 0-Euro-Verkauf wird ohne erfundenes Payment angezeigt.

## 10. Reporting und CSV

- [ ] Tagesreport zeigt Anzahl Verkäufe und Umsatz.
- [ ] Produktmengen und Produktumsätze sind plausibel.
- [ ] Kassenschicht zeigt Startbestand, Einlagen, Entnahmen, Soll, gezählt und Differenz.
- [ ] CSV lässt sich exportieren und enthält die erwarteten Daten.
- [ ] Die mit `app:demo:seed` erzeugte Demo-Historie enthält drei Verkäufe, davon einen 0-Euro-Verkauf und insgesamt 8,00 EUR Umsatz.

## 11. Audit

Als Administrator:

- [ ] Login-/Benutzerereignisse sind auffindbar.
- [ ] Verkauf, Kassenöffnung/-abschluss und Bargeldbewegungen sind auffindbar.
- [ ] Keine PINs oder Secrets erscheinen in Audit-Payloads.
- [ ] Audit-Datensätze sind nur lesbar.

## 12. Backup und Restore

Vor dem Restore immer mit einer lokalen Testdatenbank arbeiten.

```bat
php artisan app:backup
```

- [ ] Backup-Datei wird unter `storage\app\backups\database` erstellt.
- [ ] Dateigröße ist plausibel.
- [ ] Safety-Backup-Verhalten vor Restore wurde geprüft.
- [ ] Restore wurde mindestens einmal gegen eine lokale Testdatenbank erfolgreich durchgeführt.
- [ ] Nach Restore funktionieren Login, POS und Reporting weiterhin.

## 13. Smartphone / Tablet / Desktop

```bat
scripts\mobile\mobile.cmd doctor
scripts\mobile\mobile.cmd start
```

Jeweils Smartphone, Tablet und Desktop prüfen:

- [ ] Login vollständig bedienbar.
- [ ] Produktwahl schnell und ohne Fehl-Taps.
- [ ] Warenkorb jederzeit erreichbar.
- [ ] Bottom Sheet lässt sich sicher bedienen.
- [ ] Bezahlen und Rückgeld sind ohne horizontales Scrollen sichtbar.
- [ ] Hoch- und Querformat funktionieren.
- [ ] Touch-Ziele sind ausreichend groß.
- [ ] Administration ist mobil navigierbar.
- [ ] Status-/Fehlermeldungen passen visuell in das neutrale POS-Design.

## 14. Release-Gate

V1-008 darf erst geschlossen werden, wenn:

- [ ] diese manuelle Checkliste vollständig durchgeführt wurde,
- [ ] `php artisan test` vollständig grün ist,
- [ ] `npm run build` grün ist,
- [ ] keine offenen P0/P1-Fehler für den Probebetrieb vorhanden sind,
- [ ] ein realer Smartphone-/Tablet-Test dokumentiert wurde.

Datum: ____________________

Tester: ____________________

Commit: ____________________

Ergebnis: [ ] PASS  [ ] FAIL

Notizen:

____________________________________________________________________

____________________________________________________________________
