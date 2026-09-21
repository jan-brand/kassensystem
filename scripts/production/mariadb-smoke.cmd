@echo off
setlocal
cd /d "%~dp0\..\.."

php "%~dp0mariadb-smoke.php" %*
set EXITCODE=%ERRORLEVEL%

endlocal & exit /b %EXITCODE%
