@echo off
setlocal EnableExtensions

rem GitHub issue system setup for jan-brand/kassensystem.
rem This script uses cmd.exe and GitHub CLI only. No PowerShell is required.

set "REPO=jan-brand/kassensystem"
set "ROOT=%~dp0..\.."
set "GH=C:\Program Files\GitHub CLI\gh.exe"

cd /d "%ROOT%"

if not exist "%GH%" (
    echo ERROR: GitHub CLI was not found at:
    echo   %GH%
    echo Install it with: winget install --id GitHub.cli
    exit /b 1
)

echo Checking GitHub login ...
"%GH%" auth status
if errorlevel 1 (
    echo ERROR: GitHub CLI is not logged in.
    echo Run: "%GH%" auth login
    exit /b 1
)

echo.
echo Checking repository %REPO% ...
"%GH%" repo view "%REPO%" --json nameWithOwner >nul
if errorlevel 1 (
    echo ERROR: Repository %REPO% is not accessible with the current login.
    exit /b 1
)

echo.
echo Creating/updating labels ...
call :ensure_label "type:bug" "d73a4a" "Fehler oder Regression" || goto :failed
call :ensure_label "type:feature" "a2eeef" "Neue fachliche Funktion" || goto :failed
call :ensure_label "type:tech" "5319e7" "Technische Aufgabe, Refactoring oder Infrastruktur" || goto :failed
call :ensure_label "type:docs" "0075ca" "Dokumentation" || goto :failed
call :ensure_label "type:qa" "fbca04" "Test, Qualitaet oder Abnahme" || goto :failed
call :ensure_label "type:epic" "3e4b9e" "Uebergeordneter Arbeitsblock" || goto :failed
call :ensure_label "priority:P0" "b60205" "Blockiert Betrieb oder sicherheitskritisch" || goto :failed
call :ensure_label "priority:P1" "d93f0b" "Fuer die aktuelle Version erforderlich" || goto :failed
call :ensure_label "priority:P2" "fbca04" "Wichtig, aber nicht release-blockierend" || goto :failed
call :ensure_label "priority:P3" "0e8a16" "Optional oder spaeter" || goto :failed
call :ensure_label "status:triage" "ededed" "Noch nicht vollstaendig eingeordnet" || goto :failed
call :ensure_label "status:blocked" "000000" "Durch Abhaengigkeit blockiert" || goto :failed
call :ensure_label "area:pos" "c5def5" "POS-Oberflaeche" || goto :failed
call :ensure_label "area:administration" "c5def5" "Administrationsoberflaeche" || goto :failed
call :ensure_label "area:identity" "c5def5" "Benutzer, Rollen und Authentifizierung" || goto :failed
call :ensure_label "area:catalog" "c5def5" "Kategorien und Produkte" || goto :failed
call :ensure_label "area:cash-register" "c5def5" "Kasse, Kassenschicht und Bargeldbewegungen" || goto :failed
call :ensure_label "area:sales" "c5def5" "Verkaeufe, Zahlungen und Belege" || goto :failed
call :ensure_label "area:reporting" "c5def5" "Berichte und Exporte" || goto :failed
call :ensure_label "area:settings" "c5def5" "Systemeinstellungen" || goto :failed
call :ensure_label "area:audit" "c5def5" "Audit-Protokoll" || goto :failed
call :ensure_label "area:foundation" "c5def5" "Foundation, Architektur und Tooling" || goto :failed

echo.
echo Creating milestones ...
call :ensure_milestone "v1" "Erster vollstaendiger Probebetrieb des Kassensystems" || goto :failed
call :ensure_milestone "v1.1" "Kleinere Erweiterungen nach v1" || goto :failed
call :ensure_milestone "v2" "Groessere fachliche Erweiterungen" || goto :failed

echo.
echo Creating initial v1 backlog ...
call :ensure_issue "[FEATURE] Systemeinstellungen in der Administration" ".github\backlog\01-settings.md" "type:feature" "priority:P1" "area:settings" "area:administration" || goto :failed
call :ensure_issue "[FEATURE] Audit-Protokoll-Browser" ".github\backlog\02-audit.md" "type:feature" "priority:P1" "area:audit" "area:administration" || goto :failed
call :ensure_issue "[FEATURE] Reporting und CSV-Oberflaeche fertigstellen" ".github\backlog\03-reporting.md" "type:feature" "priority:P1" "area:reporting" "area:administration" || goto :failed
call :ensure_issue "[FEATURE] Verkaufsdetails und Belegansicht" ".github\backlog\04-sales-receipts.md" "type:feature" "priority:P1" "area:sales" "area:administration" || goto :failed
call :ensure_issue "[TECH] Berechtigungen zentralisieren und vollstaendig durchsetzen" ".github\backlog\05-permissions.md" "type:tech" "priority:P1" "area:identity" "area:foundation" || goto :failed
call :ensure_issue "[TECH] Backup, Restore und Produktionsvorbereitung" ".github\backlog\06-production.md" "type:tech" "priority:P1" "area:foundation" "type:docs" || goto :failed
call :ensure_issue "[TECH] Pint und PHPStan bereinigen" ".github\backlog\07-quality.md" "type:tech" "priority:P2" "area:foundation" "type:qa" || goto :failed
call :ensure_issue "[QA] Demo-Daten und vollstaendige v1-Abnahme" ".github\backlog\08-acceptance.md" "type:qa" "priority:P1" "area:foundation" "area:pos" || goto :failed
call :ensure_issue "[FEATURE] POS-Finalisierung fuer Tabletbetrieb" ".github\backlog\09-pos-finalization.md" "type:feature" "priority:P2" "area:pos" "area:sales" || goto :failed
call :ensure_issue "[EPIC] Kassensystem v1 abschliessen" ".github\backlog\00-epic-v1.md" "type:epic" "priority:P0" "area:foundation" "status:triage" || goto :failed

echo.
echo ============================================================
echo GitHub issue system is ready.
echo ============================================================
"%GH%" issue list --repo "%REPO%" --milestone "v1" --state open --limit 50
exit /b 0

:ensure_label
"%GH%" label create "%~1" --repo "%REPO%" --color "%~2" --description "%~3" --force >nul
if errorlevel 1 exit /b 1
echo   label: %~1
exit /b 0

:ensure_milestone
set "MILESTONE_TITLE=%~1"
"%GH%" api "repos/%REPO%/milestones?state=all&per_page=100" --jq ".[].title" | findstr /x /l /c:"%MILESTONE_TITLE%" >nul
if not errorlevel 1 (
    echo   milestone exists: %MILESTONE_TITLE%
    exit /b 0
)
"%GH%" api --method POST "repos/%REPO%/milestones" -f "title=%~1" -f "description=%~2" >nul
if errorlevel 1 exit /b 1
echo   milestone created: %MILESTONE_TITLE%
exit /b 0

:ensure_issue
set "ISSUE_TITLE=%~1"
set "ISSUE_BODY=%~2"
if not exist "%ISSUE_BODY%" (
    echo ERROR: Body file not found: %ISSUE_BODY%
    exit /b 1
)
"%GH%" issue list --repo "%REPO%" --state all --limit 500 --json title --jq ".[].title" | findstr /x /l /c:"%ISSUE_TITLE%" >nul
if not errorlevel 1 (
    echo   issue exists: %ISSUE_TITLE%
    exit /b 0
)
"%GH%" issue create --repo "%REPO%" --title "%ISSUE_TITLE%" --body-file "%ISSUE_BODY%" --assignee "@me" --milestone "v1" --label "%~3" --label "%~4" --label "%~5" --label "%~6"
if errorlevel 1 exit /b 1
exit /b 0

:failed
echo.
echo ERROR: Setup stopped because a GitHub command failed.
exit /b 1
