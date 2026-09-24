# Tickets

V2-004 bildet tagesbezogene QR-Tickets als eigene Domain ab.

- Der QR-Inhalt ist ein kryptografisch zufälliger 256-Bit-Token. Persistiert wird nur sein SHA-256-Hash.
- Tickets sind entweder `free` oder `paid`. Kostenlose Tickets referenzieren keinen Sale. Bezahlte Tickets referenzieren einen bereits abgeschlossenen Sale mit vorhandenem Payment.
- Ein Ticket kann genau einmal einem offenen Hospitality-Vorgang und damit dessen Tisch zugeordnet werden. Eine Umhängung ist nicht vorgesehen.
- Mehrere Tickets dürfen demselben Vorgang zugeordnet sein.
- Menüberechtigungen werden mengenbasiert je Menü gespeichert. Einlösungen sind transaktionssicher gegen Überverbrauch geschützt.
- Die Einlösung fügt die gewählte Menüposition mit 0 Cent zum Hospitality-Vorgang hinzu und protokolliert den gedeckten Katalogwert separat. Dadurch entsteht kein zweiter Umsatz.
- Nach Tischschluss bleibt die historische Zuordnung bestehen, das Ticket kann an diesem Vorgang aber nicht weiter eingelöst werden.
- Neue Payment-/PayPal-Erzeugung gehört nicht zu diesem Modul und bleibt V2-006.
