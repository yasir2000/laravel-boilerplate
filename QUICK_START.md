# IMMEDIATE SOLUTION - Laravel Boilerplate Setup

## You have 3 options to get started:

### 🚀 Option 1: Use Docker (Easiest - No PHP/Composer needed)

If you have Docker Desktop installed:

```bash
# Start with Docker
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Setup application
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate:fresh --seed

# Access at: http://localhost:8000
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
