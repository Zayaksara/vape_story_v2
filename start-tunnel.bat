@echo off
REM =====================================================
REM Start Vape Story: Laravel Server + Cloudflare Tunnel
REM =====================================================

setlocal

echo ========================================
echo   Starting Vape Story Dev Environment
echo ========================================

REM Check if PHP is available
where php >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo [ERROR] PHP not found in PATH.
    echo Make sure PHP is installed and added to PATH.
    pause
    exit /b 1
)

REM Start Laravel server in background
echo.
echo [1/2] Starting Laravel server on http://127.0.0.1:8000 ...
start "Laravel" cmd /c "cd /d D:\story_vape && php artisan serve --host=127.0.0.1 --port=8000"

REM Wait for server to start
timeout /t 3 /nobreak >nul

REM Check if server is running
curl -s http://127.0.0.1:8000/up >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo [WARN] Laravel server may not be responding yet.
) else (
    echo [OK]   Laravel server is running.
)

REM Start Cloudflare Tunnel
echo.
echo [2/2] Starting Cloudflare Tunnel ...
echo       Opening https://vapestory.serverfaqih.my.id
echo.

cd /d C:\Users\Lenovo\.cloudflared
cloudflared tunnel run vapestory-tunnel

echo.
echo Tunnel stopped. Closing...
pause