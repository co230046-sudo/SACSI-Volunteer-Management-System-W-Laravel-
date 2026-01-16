@echo off
setlocal enabledelayedexpansion
title SACSI - Run App
cd /d "%~dp0"

REM Auto-detect XAMPP
set "XAMPP="
for %%D in (C D E) do (
  if exist "%%D:\xampp\xampp-control.exe" set "XAMPP=%%D:\xampp"
)

if "%XAMPP%"=="" (
  echo [ERROR] XAMPP not found in C:\xampp, D:\xampp, or E:\xampp
  pause
  exit /b 1
)

set "PHP=%XAMPP%\php\php.exe"
if not exist "%PHP%" (
  echo [ERROR] PHP not found at %PHP%
  pause
  exit /b 1
)

echo Starting Apache/MySQL (quiet)...

REM Start Apache only if not already listening on port 80 or 443
netstat -aon | findstr ":80 " | findstr LISTENING >nul
if errorlevel 1 (
  if exist "%XAMPP%\apache\bin\httpd.exe" (
    start "" /min "%XAMPP%\apache\bin\httpd.exe"
  )
)

REM Start MySQL only if not already listening on 3306
netstat -aon | findstr ":3306 " | findstr LISTENING >nul
if errorlevel 1 (
  if exist "%XAMPP%\mysql\bin\mysqld.exe" (
    start "" /min "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini"
  )
)

timeout /t 2 >nul

echo Starting Laravel server...
start "" /min cmd /k ""%PHP%" artisan serve --host=127.0.0.1 --port=8000"

timeout /t 2 >nul
start "" "http://127.0.0.1:8000"
exit /b 0