@echo off
setlocal
cd /d "%~dp0\..\.."

php "%~dp0final-release-check.php" %*
set EXITCODE=%ERRORLEVEL%

endlocal & exit /b %EXITCODE%
