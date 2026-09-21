@echo off
setlocal
cd /d "%~dp0\..\.."

php "%~dp0mobile.php" %*
set EXITCODE=%ERRORLEVEL%

endlocal & exit /b %EXITCODE%
