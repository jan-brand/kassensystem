# POS auf Smartphone und Tablet

Der POS verwendet für Desktop, Tablet und Smartphone dieselbe Livewire-Oberfläche.
Es gibt keine separate mobile Fachlogik.

## Responsive Verhalten

### Smartphone

- Produktbereich nutzt die gesamte Breite.
- Kategorien bleiben horizontal scrollbar.
- Suche bleibt direkt über den Produkten.
- Eine feste Leiste am unteren Bildschirmrand zeigt Artikelanzahl und Gesamtbetrag.
- Der Warenkorb öffnet als Bottom-Sheet.
- Bezahlen ist aus der festen Leiste und aus dem Warenkorb erreichbar.
- Kassenmenü, Zahlung und Abschlussdialoge öffnen mobil als Bottom-Sheet.
- Safe-Area-Abstände für Geräte mit Home-Indikator/Notch werden berücksichtigt.

### Tablet/Desktop

Ab `xl` bleibt der Warenkorb dauerhaft rechts neben dem Produktbereich sichtbar.
Damit bleibt die bisherige Tablet-/Desktop-Arbeitsweise erhalten.

## Touch-Regeln

- häufige Aktionen verwenden große Touch-Ziele;
- Produktkacheln reagieren auf Touch ohne Hover-Abhängigkeit;
- Mengensteuerung hat mindestens etwa 44 px große Bedienelemente;
- kritische Livewire-Aktionen deaktivieren ihre Buttons während eines Requests;
- der eigentliche Schutz vor Doppelbuchungen bleibt zusätzlich im Backend.

## Reale Abnahme über das lokale Netz

Vor dem Test:

```bat
npm run build
scripts\mobile\mobile.cmd doctor
scripts\mobile\mobile.cmd start
```

Auf dem Smartphone die vom Script ausgegebene `/pos`-Adresse öffnen.

## Mobile Testmatrix

Jeder Release Candidate wird mindestens mit folgenden Abläufen auf einem echten
Smartphone geprüft:

1. Anmelden.
2. Kasse öffnen.
3. Kategorie wechseln und Produkt suchen.
4. Mehrere Produkte schnell nacheinander hinzufügen.
5. Warenkorb öffnen.
6. Menge erhöhen und verringern.
7. Warenkorb schließen und weiterverkaufen.
8. Bezahlen, gegebenen Betrag erfassen und Rückgeld prüfen.
9. Nächsten Verkauf starten.
10. Einlage und Entnahme buchen.
11. Kassenabschluss starten, abbrechen und erneut starten.
12. Kasse mit und ohne Differenz schließen.
13. Benutzer wechseln.
14. Administration öffnen und mobile Navigation prüfen.

Zusätzlich Hoch- und Querformat testen. Bei iOS/Android sollte der Browser nicht
durch horizontales Overflow zoomen oder wichtige Aktionen unter der Browserleiste
verdecken.
