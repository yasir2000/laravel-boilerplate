@echo off
echo 🚀 Laravel Boilerplate Setup for Windows
echo.

REM Check if PHP is installed
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP is not installed or not in PATH.
    echo.
    echo 📖 Please follow the installation guide in WINDOWS_SETUP.md
    echo.
    echo Quick install with Chocolatey ^(run as Administrator^):
    echo   1. Install Chocolatey: https://chocolatey.org/install
    echo   2. choco install php
    echo   3. choco install composer
    echo   4. choco install postgresql
    echo.
    echo Manual install:
    echo   1. Download PHP from: https://windows.php.net/download/
    echo   2. Download Composer from: https://getcomposer.org/
    echo   3. Add both to your system PATH
    echo.
    pause
    exit /b 1
)

echo ✅ PHP is installed
php --version

REM Check if Composer is installed globally
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo ❌ Composer is not installed globally.
    echo 📦 Installing Composer locally...
    
    REM Try to download and install Composer locally
    if exist composer-setup.php del composer-setup.php
    
    echo Downloading Composer installer...
    powershell -Command "Invoke-WebRequest -Uri 'https://getcomposer.org/installer' -OutFile 'composer-setup.php'"
    
    if exist composer-setup.php (
        echo Installing Composer...
        php composer-setup.php --install-dir=. --filename=composer.phar
        del composer-setup.php
        
        if exist composer.phar (
            echo ✅ Composer installed locally as composer.phar
            set COMPOSER_CMD=php composer.phar
        ) else (
            echo ❌ Failed to install Composer locally
            echo Please install Composer manually: https://getcomposer.org/
            pause
            exit /b 1
        )
    ) else (
        echo ❌ Failed to download Composer installer
        echo Please check your internet connection and try again
        pause
        exit /b 1
    )
) else (
    echo ✅ Composer is installed globally
    composer --version
    set COMPOSER_CMD=composer
)

REM Install dependencies
echo 📦 Installing PHP dependencies...
composer install

REM Copy environment file if it doesn't exist
if not exist .env (
    echo 📝 Creating environment file...
    copy .env.example .env
    echo ✅ Please configure your .env file with your database credentials
)

REM Generate application key
echo 🔑 Generating application key...
php artisan key:generate

REM Check if database connection works
echo 🔍 Checking database connection...
php artisan migrate:status >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Database connection successful
    
    REM Ask if user wants to run migrations
    echo 🗃️  Do you want to run database migrations and seeders? (y/N)
    set /p response=
    if /i "%response%" == "y" (
        echo 🏗️  Running migrations...
        php artisan migrate
        
        echo 🌱 Running seeders...
        php artisan db:seed
        
        echo ✅ Database setup completed!
        echo.
        echo 🎉 Setup completed! Default credentials:
        echo    Super Admin: superadmin@laravel-boilerplate.com / password
        echo    Company Admin: admin@techsolutions.com / password
        echo.
    )
) else (
    echo ❌ Database connection failed. Please check your .env configuration.
    echo    Make sure PostgreSQL is running and credentials are correct.
)

REM Ask if user wants to start the server
echo 🚀 Do you want to start the development server? (y/N)
set /p response=
if /i "%response%" == "y" (
    echo 🌐 Starting Laravel development server...
    echo    Application will be available at: http://localhost:8000
    echo    API Health Check: http://localhost:8000/api/health
    echo.
    php artisan serve
)

echo ✨ Setup completed! Happy coding!
pause
