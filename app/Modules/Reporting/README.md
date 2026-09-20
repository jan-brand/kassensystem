# Reporting

Liest abgeschlossene Verkäufe und Kassenschichten aus und stellt daraus Berichte zusammen.

Der Berichtstag folgt `config('kassensystem.timezone')`. Kassenschichten bleiben davon unabhängig und dürfen über Mitternacht laufen. Für die Zuordnung einer abgeschlossenen Kassenschicht ist ihr tatsächlicher `closed_at`-Zeitpunkt maßgeblich.

Die Administrationsoberfläche bietet eine Tagesauswahl, Vor-/Folgetag-Navigation, Produktmengen und -umsätze sowie Kassenschichten mit Startbestand, Barumsatz, Einlagen, Entnahmen, Soll-/Ist-Bestand und Differenz. Der CSV-Export verwendet UTF-8 mit BOM und Semikolon als Trennzeichen.
