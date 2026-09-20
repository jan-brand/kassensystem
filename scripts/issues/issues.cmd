@echo off
setlocal EnableExtensions
set "ROOT=%~dp0..\.."
cd /d "%ROOT%"
php "%~dp0issues.php" %*
exit /b %ERRORLEVEL%
