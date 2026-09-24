## Ziel
Abgeschlossene Verkaeufe sicher rueckabwickeln, ohne den Originalverkauf zu veraendern.

## Umfang
- vollstaendiges Storno
- eigene Gegenbuchung mit Referenz auf Originalverkauf
- Actor, Zeitpunkt und Pflichtgrund
- Umsatz- und Bargeldwirkung
- Schutz vor doppeltem Storno
- Rollen/Permission und Audit
- Darstellung in POS/Administration

## Akzeptanzkriterien
- [ ] Originalverkauf und Positionen bleiben unveraendert.
- [ ] Storno ist ein eigener unveraenderlicher Datensatz.
- [ ] Bereits stornierter Verkauf kann nicht erneut vollstaendig storniert werden.
- [ ] Bar-Storno korrigiert den erwarteten Kassenbestand nachvollziehbar.
- [ ] 0-Euro-Verkauf kann ohne erfundenes Payment storniert werden.
- [ ] Unberechtigte Rollen koennen keinen Storno ausloesen.
- [ ] Doppelklick/Doppel-Tap erzeugt keine doppelte Gegenbuchung.
- [ ] Tests decken Transaktion, Idempotenz und UI-Flow ab.
