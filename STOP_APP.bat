@echo off
setlocal enabledelayedexpansion
title SACSI - Stop App

echo ==========================================
echo   Stopping SACSI Volunteer Management App
echo ==========================================

REM 1) Kill the process listening on port 8000 (usually php.exe)
echo Stopping Laravel server (port 8000)...
set "FOUND="
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000 " ^| findstr LISTENING') do (
  set "FOUND=1"
  echo Killing PID on port 8000: %%a
  taskkill /PID %%a /T /F >nul 2>&1
)

if not defined FOUND (
  echo No process found listening on port 8000.
)

REM 2) Kill the CMD window launched by START_APP.bat (/k keeps it open)
echo Closing Laravel terminal window(s) started with artisan serve...
powershell -NoProfile -Command ^
  "Get-CimInstance Win32_Process -Filter \"Name='cmd.exe'\" | " ^
  "Where-Object { $_.CommandLine -match 'artisan\s+serve' -and $_.CommandLine -match '--port=8000' } | " ^
  "ForEach-Object { Stop-Process -Id $_.ProcessId -Force }" >nul 2>&1

echo.
echo Done.
pause
exit /b 0
