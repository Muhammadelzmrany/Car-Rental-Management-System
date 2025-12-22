@echo off
echo ========================================
echo   Car Rental System - Starting...
echo ========================================
echo.

REM Check if XAMPP is running
echo Checking XAMPP services...
echo.

REM Start Apache
echo Starting Apache...
start "" "C:\xampp\apache_start.bat"
timeout /t 2 /nobreak >nul

REM Start MySQL
echo Starting MySQL...
start "" "C:\xampp\mysql_start.bat"
timeout /t 2 /nobreak >nul

echo.
echo ========================================
echo   Services started!
echo ========================================
echo.
echo Opening application in browser...
timeout /t 3 /nobreak >nul

REM Open browser
start http://localhost/lamonaa/

echo.
echo Application should open in your browser.
echo If not, go to: http://localhost/lamonaa/
echo.
pause






