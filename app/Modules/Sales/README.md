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

## Administration

Abgeschlossene Verkäufe können in der Administration ausschließlich lesend gesucht und als HTML-Beleg angezeigt werden.
Die Belegpositionen verwenden den gespeicherten Produktnamen und Einzelpreis aus `sale_items`; spätere Katalogänderungen verändern historische Belege nicht.

## V2-Rabatte

- Positionsrabatte speichern Ausgangspreis, Rabattart, Rabattwert, Quelle, Bezeichnung und Endpreis als Snapshot.
- Tagesangebote werden beim erstmaligen Anlegen einer Warenkorbposition ausgewertet; spätere Zeit- oder Katalogänderungen verändern den Snapshot nicht.
- ein manueller Rabatt ersetzt einen vorhandenen Angebotsrabatt und wird immer vom ursprünglichen Positionspreis berechnet.
- `SaleItem::discountCents()` stellt die Rabattwirkung für Reporting ohne Rekonstruktion aus aktuellen Katalogdaten bereit.
