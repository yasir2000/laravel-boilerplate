# Windows Installation Guide

## Prerequisites Installation

Since PHP and Composer are not installed on your system, here's how to install them:

### 1. Install PHP 8.2+

**Option A: Using Chocolatey (Recommended)**
```powershell
# Install Chocolatey first (run as Administrator)
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Install PHP
choco install php

# Install Composer
choco install composer
```

**Option B: Manual Installation**

1. **Download PHP:**
   - Go to: https://windows.php.net/download/
   - Download "Non Thread Safe" version for x64
   - Extract to `C:\php`

2. **Configure PHP:**
   ```powershell
   # Add PHP to PATH
   $env:PATH += ";C:\php"
   
   # Copy configuration file
   copy C:\php\php.ini-development C:\php\php.ini
   ```

3. **Enable Required Extensions in php.ini:**
   ```ini
   extension=pdo_pgsql
   extension=pgsql
   extension=openssl
   extension=mbstring
   extension=tokenizer
   extension=xml
   extension=ctype
   extension=json
   extension=bcmath
   extension=fileinfo
   extension=gd
   extension=curl
   extension=zip
   ```

4. **Install Composer:**
   - Download from: https://getcomposer.org/Composer-Setup.exe
   - Run the installer

### 2. Install PostgreSQL

**Option A: Using Chocolatey**
```powershell
choco install postgresql
```

**Option B: Manual Installation**
- Download from: https://www.postgresql.org/download/windows/
- Install with default settings
- Remember the password for the `postgres` user

### 3. Install Redis (Optional)

**Using Chocolatey:**
```powershell
choco install redis-64
```

## Quick Setup Commands

After installing the prerequisites, run:

```powershell
# Clone and setup (if not already done)
git clone <your-repo-url>
cd laravel-boilerplate

# Run the Windows setup script
.\setup.bat

# Or run manually:
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

## Alternative: Use Docker

If you prefer to use Docker instead of installing everything locally:

```powershell
# Make sure Docker Desktop is installed
docker --version

# Start the application with Docker
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Run setup
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate:fresh --seed
```

## Troubleshooting

### PHP Issues
- Ensure PHP is in your PATH
- Check php.ini has required extensions enabled
- Restart terminal after PATH changes

### Composer Issues
- Make sure Composer is in your PATH
- Try running `composer --version` to verify

### Database Issues
- Ensure PostgreSQL is running on port 5432
- Create database: `createdb laravel_boilerplate`
- Check connection settings in .env file

### Permission Issues
- Run PowerShell as Administrator for installation
- Check file permissions in project directory
