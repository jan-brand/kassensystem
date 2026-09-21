# Mobiles Testen im lokalen Netz

Dieses Projekt kann ohne Cloud-Tunnel direkt auf einem Smartphone getestet werden.
Der Entwicklungsrechner stellt Laravel im lokalen Netz auf `0.0.0.0:8000` bereit.

## Voraussetzungen

- Laptop und Smartphone befinden sich im selben lokalen Netz.
- Das Frontend wurde mit `npm run build` gebaut.
- Für Port 8000 ist eine lokale Windows-Firewall-Regel eingerichtet.
- Der mobile Testserver läuft während des Tests in einer CMD.

Die Lösung funktioniert vollständig offline. Es werden keine externen Tunnel- oder
QR-Dienste benötigt.

## Schnellstart unter Windows

### 1. Firewall einmalig freigeben

Eine **CMD als Administrator** öffnen:

```bat
cd C:\xampp\htdocs\kassensystem
scripts\mobile\mobile.cmd firewall-add
```

Die Regel erlaubt TCP-Port 8000 nur aus `LocalSubnet`. Sie kann später entfernt werden:

```bat
scripts\mobile\mobile.cmd firewall-remove
```

### 2. Diagnose

In einer normalen CMD:

```bat
cd C:\xampp\htdocs\kassensystem
scripts\mobile\mobile.cmd doctor
```

Das Script ermittelt die IPv4-Adresse der aktiven Standardroute und zeigt die
Adresse an, die im Browser des Smartphones geöffnet werden muss.

Beispiel:

```text
Handy-Adresse: http://192.168.43.123:8000/
```

### 3. Server starten

```bat
scripts\mobile\mobile.cmd start
```

Der Prozess bleibt in der CMD aktiv. Mit `Strg+C` wird er beendet.

Der normale mobile Teststart setzt für genau diesen Serverprozess:

```text
APP_DEBUG=false
FOUNDATION_DASHBOARD=false
APP_URL=http://<LAN-IP>:8000
```

Damit werden keine Laravel-Debugseiten über das WLAN verteilt. Für eine bewusst
unsichere lokale Fehlersuche ist möglich:

```bat
scripts\mobile\mobile.cmd start 8000 --debug
```

Debug nur in einem vertrauenswürdigen lokalen Netz verwenden.

## Nützliche URLs

Das Startscript zeigt die tatsächliche LAN-IP an. Danach sind typischerweise erreichbar:

```text
http://<LAN-IP>:8000/
http://<LAN-IP>:8000/pos
http://<LAN-IP>:8000/pos/login
http://<LAN-IP>:8000/administration
http://<LAN-IP>:8000/health
```

Livewire verwendet dieselbe HTTP-Verbindung. Es ist kein separater Vite-Devserver
auf dem Smartphone erforderlich, solange vorher `npm run build` ausgeführt wurde.

## Handy als Hotspot

Wenn der Laptop mit dem Hotspot des Smartphones verbunden ist, befindet sich der
Laptop normalerweise in einem privaten Hotspot-Subnetz, zum Beispiel:

```text
192.168.x.x
172.20.10.x
```

Das Script verwendet automatisch die IPv4-Adresse der Windows-Standardroute.

Einige Smartphone-/Provider-/Hotspot-Kombinationen erlauben dem Hotspot-Gerät
jedoch nicht, direkt auf verbundene Clients zuzugreifen. Wenn die ausgegebene URL
trotz laufendem Server und Firewall-Regel auf dem Smartphone nicht erreichbar ist,
liegt das nicht zwingend an Laravel.

Dann sind die zuverlässigsten lokalen Alternativen:

1. Laptop und Smartphone mit demselben WLAN-Router verbinden.
2. Smartphone mit einem vom Laptop bereitgestellten mobilen Hotspot verbinden.
3. Wenn Internet benötigt wird: Smartphone per USB-Tethering als Upstream nutzen
   und das Smartphone für den eigentlichen Browser-Test mit dem lokalen WLAN/Hotspot
   verbinden, sofern die vorhandene Hardware diese Kombination unterstützt.

## Fehlerdiagnose

### Browser meldet Zeitüberschreitung

```bat
scripts\mobile\mobile.cmd status
scripts\mobile\mobile.cmd doctor
```

Danach prüfen:

```bat
ipconfig
```

Die im Smartphone verwendete IP muss die IPv4-Adresse des aktiven WLAN-Adapters sein.

### Verbindung wird abgelehnt

Der Laravel-Server läuft nicht oder ein anderer Port wurde verwendet:

```bat
scripts\mobile\mobile.cmd start 8000
```

### Windows-Firewall

Die Regel neu setzen:

```bat
scripts\mobile\mobile.cmd firewall-remove 8000
scripts\mobile\mobile.cmd firewall-add 8000
```

Die Befehle benötigen eine Administrator-CMD.

### Frontend sieht alt aus

```bat
npm run build
```

Danach Smartphone-Seite neu laden. Bei hartnäckigem Browser-Cache einen privaten
Tab verwenden.

## Sicherheit

Der Server bindet an `0.0.0.0` und ist damit für Geräte erreichbar, die das lokale
Subnetz erreichen können. Deshalb:

- nur in einem vertrauenswürdigen lokalen Netz starten;
- Debug standardmäßig ausgeschaltet lassen;
- Test-PINs und Testdaten verwenden;
- nach dem Test `Strg+C` drücken;
- die Firewall-Regel entfernen, wenn mobile Tests dauerhaft nicht mehr benötigt werden.
