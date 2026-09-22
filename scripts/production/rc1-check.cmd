@echo off
setlocal EnableExtensions
cd /d "%~dp0\..\.."

echo.
echo [1/4] Vollstaendiges Release-Gate
call composer qa:release
if errorlevel 1 goto :fail

echo.
echo [2/4] Laravel Optimize / Route-Cache
set "FOUNDATION_DASHBOARD=false"
php artisan optimize --no-ansi
if errorlevel 1 goto :optimize_fail

php artisan route:list --name=health --no-ansi
if errorlevel 1 goto :optimize_fail

php artisan optimize:clear --no-ansi
if errorlevel 1 goto :fail
set "FOUNDATION_DASHBOARD="

echo.
echo [3/4] Produktionsnahe Regressionstests
php artisan test tests\Feature\Production
if errorlevel 1 goto :fail

echo.
echo [4/4] Optionaler MariaDB-Smoke-Test
if /I "%~1"=="--with-mariadb" (
    call scripts\production\mariadb-smoke.cmd
    if errorlevel 1 goto :fail
) else (
    echo Uebersprungen. Fuer den Datenbank-Smoke-Test erneut mit --with-mariadb starten.
)

echo.
echo RC1 technical preflight passed.
endlocal & exit /b 0

:optimize_fail
php artisan optimize:clear --no-ansi >nul 2>&1
set "FOUNDATION_DASHBOARD="
goto :fail

:fail
echo.
echo RC1 technical preflight FAILED.
endlocal & exit /b 1
