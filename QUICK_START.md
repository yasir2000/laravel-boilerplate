# 🚀 Laravel Boilerplate - Quick Start with Laravel Sail

## 🐳 Recommended: Laravel Sail (Docker)

**Prerequisites:** Docker Desktop for Windows

### Step 1: Clone & Setup
```bash
git clone https://github.com/yasir2000/laravel-boilerplate.git
cd laravel-boilerplate
cp .env.example .env
```

### Step 2: Start Laravel Sail
```bash
# Start all containers
docker-compose up -d

# Install dependencies
docker-compose exec laravel.test composer install

# Generate app key
docker-compose exec laravel.test php artisan key:generate

# Run migrations
docker-compose exec laravel.test php artisan migrate

# Seed database
docker-compose exec laravel.test php artisan db:seed
```

### Step 3: Access Your Application
- **🌐 App:** http://localhost
- **📧 Mailpit:** http://localhost:8025
- **🔍 Meilisearch:** http://localhost:7700

### 🚀 Optional: Add Laravel Octane for Performance
```bash
# Install Octane
docker-compose exec laravel.test php artisan octane:install --server=frankenphp

# Start high-performance server
docker-compose exec laravel.test php artisan octane:frankenphp --host=0.0.0.0 --port=80
```

## 🛠 Useful Commands

```bash
# Artisan commands
docker-compose exec laravel.test php artisan [command]

# Run tests
docker-compose exec laravel.test php artisan test

# Access container shell
docker-compose exec laravel.test bash

# View logs
docker-compose logs -f laravel.test

# Stop containers
docker-compose down
```

### 💻 Option 2: Install PHP & Composer with Chocolatey

**Run PowerShell as Administrator:**

```powershell
# 1. Install Chocolatey
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# 2. Install PHP
choco install php

# 3. Install Composer  
choco install composer

# 4. Install PostgreSQL
choco install postgresql

# 5. Restart PowerShell and run:
cd C:\Users\yasir\Code\laravel-boilerplate
.\setup.bat
```

### 📁 Option 3: Manual Installation

1. **Download & Install PHP:**
   - Go to: https://windows.php.net/download/
   - Download "Non Thread Safe" x64 version
   - Extract to `C:\php`
   - Add `C:\php` to your Windows PATH

2. **Download & Install Composer:**
   - Go to: https://getcomposer.org/Composer-Setup.exe
   - Run the installer

3. **Install PostgreSQL:**
   - Download from: https://www.postgresql.org/download/windows/
   - Install with default settings

4. **Then run:**
   ```cmd
   cd C:\Users\yasir\Code\laravel-boilerplate
   setup.bat
   ```

## Quick Test

After installation, test with:

```cmd
php --version
composer --version
```

Both should return version information.

---

**🎯 I recommend Option 1 (Docker) if you have Docker Desktop, or Option 2 (Chocolatey) for a permanent local setup.**
