@echo off
:: ==========================================================
:: Run only as Administrator
:: ==========================================================
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
set "BASEDIR=%~dp0"
set "PHP=%BASEDIR%php\php.exe"
set "EPS=%BASEDIR%eps"
set "NSSM=%BASEDIR%nssm.exe"

if not exist "%PHP%" (
    echo php.exe not found in %BASEDIR%php\
    exit /b 1
)

if not exist "%EPS%" (
    echo eps script not found in %BASEDIR%
    exit /b 1
)

if not exist "%NSSM%" (
    echo nssm.exe not found in %BASEDIR%
    exit /b 1
)

:: EPS_AUTH_TOKEN is intentionally not stored in this bundle. When it is empty,
:: only loopback clients are accepted. Remote POS terminals need the same token
:: set in the service environment and in every WebSocket/config client.
if "%EPS_AUTH_TOKEN%"=="" (
    echo Warning: EPS_AUTH_TOKEN is empty; only loopback clients will be accepted.
    echo Remote WebSocket/config clients need the same token in the service environment and in every client.
)

echo Installing EPS as Windows services...
"%NSSM%" install EPS-WS "%PHP%" "%EPS%" --role ws
"%NSSM%" install EPS-HTTP "%PHP%" "%EPS%" --role http

"%NSSM%" start EPS-WS
"%NSSM%" start EPS-HTTP

echo EPS services installed and started successfully.
pause
