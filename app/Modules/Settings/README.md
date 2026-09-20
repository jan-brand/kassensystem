# Settings

Verwaltet die veränderlichen Anwendungseinstellungen des Kassensystems.

EUR und die Zeitzone `Europe/Berlin` bleiben in v1 technische Konfiguration in `config/kassensystem.php` und werden nicht über die normale Administrationsoberfläche geändert.

## Administration

Die Surface `administration.settings` bündelt die editierbaren v1-Einstellungen. Cafeteria-Name, Logo und POS-Darstellung werden über `UpdateSystemSettingsAction` gespeichert. Der Kassenname bleibt fachlich im Modul `CashRegister` und wird über `RenameRegisterAction` geändert. Währung und Zeitzone bleiben technische Konfiguration.
