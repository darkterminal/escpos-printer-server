@echo off
:: ==========================================================
:: Uninstalls EPS Printer Server Windows Services
:: ==========================================================

:: --- Ensure script runs as Administrator ---
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo This script requires Administrator privileges.
    echo Requesting elevation...
    powershell -Command "Start-Process '%~f0' -Verb RunAs"
    exit /b
)
echo Running as Administrator...
echo.

setlocal
set "SVC_WS=EPS-WS"
set "SVC_HTTP=EPS-HTTP"
set "BASEDIR=%~dp0"
set "NSSM=%BASEDIR%nssm.exe"

if not exist "%NSSM%" (
    echo ❌ nssm.exe not found in %BASEDIR%
    echo Please ensure nssm.exe is in the same directory as this script.
    pause
    exit /b 1
)

echo Stopping EPS services...
"%NSSM%" stop "%SVC_WS%" >nul 2>&1
"%NSSM%" stop "%SVC_HTTP%" >nul 2>&1
timeout /t 2 >nul

echo Removing EPS services...
"%NSSM%" remove "%SVC_WS%" confirm >nul 2>&1
"%NSSM%" remove "%SVC_HTTP%" confirm >nul 2>&1

echo Uninstallation complete.
echo.
echo EPS services "%SVC_WS%" and "%SVC_HTTP%" have been removed successfully.

pause
endlocal
exit /b 0
