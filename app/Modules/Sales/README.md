# Sales

Verwaltet offene Warenkörbe, Verkaufspositionen und abgeschlossene Bargeldverkäufe.

## V1-Regeln

- pro Kassenplatz darf höchstens ein offener Verkauf existieren.
- abgeschlossene Verkäufe sind unveränderlich.
- Produktname und Einzelpreis werden als Snapshot in der Verkaufsposition gespeichert.
- Verkaufsnummern werden erst beim erfolgreichen Abschluss vergeben.
- Bargeldzahlungen unterhalb des Gesamtbetrags sind nicht erlaubt.
- ein 0-Euro-Verkauf wird ohne Payment abgeschlossen.
- ein doppelter Abschluss erzeugt weder einen zweiten Verkauf noch eine zweite Zahlung.
