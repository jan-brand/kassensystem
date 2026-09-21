@echo off
setlocal EnableExtensions
cd /d "%~dp0\..\.."

php artisan app:production-check
if errorlevel 1 exit /b 1

php artisan app:check
if errorlevel 1 exit /b 1

php artisan module:check
if errorlevel 1 exit /b 1

php artisan architecture:check
if errorlevel 1 exit /b 1

php artisan page:check
if errorlevel 1 exit /b 1

echo Production preflight passed.
exit /b 0
