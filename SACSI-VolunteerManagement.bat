@echo off
setlocal enabledelayedexpansion
title SACSI - App Control Panel
cd /d "%~dp0"

:: ================================
:: CONFIG
:: ================================
set APP_URL=http://127.0.0.1:8000

:: ================================
:: MAIN MENU
:: ================================
:menu
cls
echo ==========================================
echo   SACSI Volunteer Management System
echo           Control Panel
echo ==========================================
echo.
echo [1] Start Application
echo [2] Stop Application
echo [3] Open Website
echo [4] Open XAMPP Control Panel
echo [5] Clear Laravel Cache
echo [6] Exit
echo.
set /p choice=Select option (1-6): 

if "%choice%"=="1" goto start
if "%choice%"=="2" goto stop
if "%choice%"=="3" goto open
if "%choice%"=="4" goto xampp
if "%choice%"=="5" goto clearcache
if "%choice%"=="6" goto end

echo.
echo [!] Invalid choice.
timeout /t 2 >nul
goto menu


:: ================================
:: START APP
:: ================================
:start
cls
echo Starting SACSI Application...
echo.

if exist "START_APP.bat" (
    call "START_APP.bat"
    echo.
    echo [OK] Application started.
) else (
    echo [ERROR] START_APP.bat not found.
)

pause
goto menu


:: ================================
:: STOP APP
:: ================================
:stop
cls
echo Stopping SACSI Application...
echo.

if exist "STOP_APP.bat" (
    call "STOP_APP.bat"
    echo.
    echo [OK] Application stopped.
) else (
    echo [ERROR] STOP_APP.bat not found.
)

pause
goto menu


:: ================================
:: OPEN WEBSITE
:: ================================
:open
cls
echo Opening SACSI Website...
echo.
start "" "%APP_URL%"
echo [OK] Browser opened.
timeout /t 2 >nul
goto menu


:: ================================
:: OPEN XAMPP
:: ================================
:xampp
cls
echo Searching for XAMPP...
echo.

set "XAMPP_PATH="

for %%D in (C D E F) do (
    if exist "%%D:\xampp\xampp-control.exe" (
        set "XAMPP_PATH=%%D:\xampp"
    )
)

if "%XAMPP_PATH%"=="" (
    echo [ERROR] XAMPP not found.
    echo Checked: C:\ D:\ E:\ F:\
) else (
    echo [OK] Found XAMPP at %XAMPP_PATH%
    start "" "%XAMPP_PATH%\xampp-control.exe"
)

pause
goto menu


:: ================================
:: CLEAR CACHE (NEW)
:: ================================
:clearcache
cls
echo Clearing Laravel Cache...
echo.

if exist "artisan" (

    php artisan optimize:clear

    echo.
    echo [OK] Cache cleared.
) else (
    echo [ERROR] artisan file not found.
    echo Make sure this BAT is in Laravel root folder.
)

pause
goto menu


:: ================================
:: EXIT
:: ================================
:end
cls
echo Shutting down menu...
timeout /t 1 >nul
exit /b 0
