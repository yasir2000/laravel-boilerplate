@echo off
echo 🚀 Setting up Laravel Boilerplate...

REM Check if composer is installed
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Composer is not installed. Please install Composer first.
    exit /b 1
)

REM Check if PHP is installed
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP is not installed. Please install PHP 8.2+ first.
    exit /b 1
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
