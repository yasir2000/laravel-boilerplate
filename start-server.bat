@echo off
echo Starting Laravel application with FrankenPHP...

REM Check if FrankenPHP is available
if exist "frankenphp.exe" (
    echo Using FrankenPHP server...
    frankenphp.exe php-server --listen 127.0.0.1:8000
) else (
    echo FrankenPHP not found, using PHP built-in server...
    echo You can download FrankenPHP from: https://github.com/dunglas/frankenphp/releases
    echo.
    echo Starting server at http://127.0.0.1:8000
    php -S 127.0.0.1:8000 -t public
)