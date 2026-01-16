@echo off
setlocal enabledelayedexpansion
title SACSI - App Menu
cd /d "%~dp0"

:menu
cls
echo ==========================================
echo   SACSI Volunteer Management - MENU
echo ==========================================
echo.
echo [1] Start App
echo [2] Stop App
echo [3] Open Website (http://127.0.0.1:8000)
echo [4] Open XAMPP Control Panel (optional)
echo [5] Exit
echo.
set /p choice=Choose an option (1-5): 

if "%choice%"=="1" goto start
if "%choice%"=="2" goto stop
if "%choice%"=="3" goto open
if "%choice%"=="4" goto xampp
if "%choice%"=="5" goto end

echo Invalid choice.
timeout /t 2 >nul
goto menu

:start
if exist "START_APP.bat" (
  call "START_APP.bat"
) else (
  echo [ERROR] START_APP.bat not found in this folder.
  pause
)
goto menu

:stop
if exist "STOP_APP.bat" (
  call "STOP_APP.bat"
) else (
  echo [ERROR] STOP_APP.bat not found in this folder.
  pause
)
goto menu

:open
start "" "http://127.0.0.1:8000"
goto menu

:xampp
REM Auto-detect XAMPP and open the GUI
set "XAMPP="
for %%D in (C D E) do (
  if exist "%%D:\xampp\xampp-control.exe" set "XAMPP=%%D:\xampp"
)

if "%XAMPP%"=="" (
  echo [ERROR] XAMPP not found in C:\xampp, D:\xampp, or E:\xampp
  pause
) else (
  start "" "%XAMPP%\xampp-control.exe"
)
goto menu

:end
exit /b 0
