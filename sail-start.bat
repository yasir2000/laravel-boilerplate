@echo off
echo 🚀 Starting Laravel Sail...

REM Set environment variables
set WWWUSER=1000
set WWWGROUP=1000

REM Start Sail
.\vendor\bin\sail up -d

echo ✅ Laravel Sail running!
echo 🌐 App: http://localhost
echo 📧 Mailpit: http://localhost:8025
echo 🔧 Commands:
echo   .\vendor\bin\sail artisan octane:install --server=frankenphp
echo   .\vendor\bin\sail artisan octane:start

pause