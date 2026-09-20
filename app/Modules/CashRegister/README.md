# CashRegister

Verwaltet physische Kassenplätze, Kassenschichten und Bargeldbewegungen.

## V1-Regeln

- pro Kassenplatz darf höchstens eine offene oder im Abschluss befindliche Schicht existieren.
- Startbestand, Einlagen, Entnahmen und Barumsatz ergeben den Sollbestand.
- Einlagen und Entnahmen benötigen immer einen Grund.
- eine Kassendifferenz ist erlaubt, benötigt aber einen Abschlusskommentar.
- geschlossene Kassenschichten und Bargeldbewegungen werden nicht verändert oder gelöscht.

## POS-Ablauf

Die POS-Surface koordiniert modulübergreifende Regeln. Beim Start des Kassenabschlusses wird der Kassenplatz gesperrt und geprüft, dass kein offener Warenkorb existiert. Erst danach wechselt die Kassenschicht in `closing`. In diesem Zustand sind neue Verkäufe sowie Einlagen und Entnahmen gesperrt. Der Abschluss kann vor dem endgültigen Schließen wieder abgebrochen werden.
