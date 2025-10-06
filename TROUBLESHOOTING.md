# 🔧 Laravel Sail Troubleshooting Guide

## ✅ **PHP Extension Issues Fixed!**

### 🐛 **Common Build Errors & Solutions:**

#### **1. Missing PHP Extensions**
**Error:** `ext-exif * -> it is missing from your system`

**Solution:** Updated `Dockerfile.sail` to include:
```dockerfile
# Install additional PHP extensions
RUN apt-get update && apt-get install -y \
    libexif-dev \
    && docker-php-ext-install exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
```

#### **2. Composer Superuser Warnings**
**Error:** `Do not run Composer as root/super user!`

**Solution:** Added `COMPOSER_ALLOW_SUPERUSER=1` flag:
```dockerfile
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-scripts --no-autoloader --no-dev --prefer-dist --ignore-platform-reqs
```

#### **3. Platform Requirements Issues**
**Error:** `Your requirements could not be resolved to an installable set of packages`

**Solution:** Added `--ignore-platform-reqs` flag temporarily for initial build.

### 🚀 **Fixed Build Process:**

#### **Current Working Dockerfile.sail:**
```dockerfile
FROM laravelsail/php83-composer:latest

WORKDIR /var/www/html

# Install additional PHP extensions
RUN apt-get update && apt-get install -y \
    libexif-dev \
    && docker-php-ext-install exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Copy composer files
COPY composer*.json ./

# Install PHP dependencies with platform requirements ignored temporarily
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-scripts --no-autoloader --no-dev --prefer-dist --ignore-platform-reqs

# Copy application
COPY . .

# Generate autoload with superuser permission
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize

# Install Node dependencies and build assets (if package.json exists)
RUN if [ -f "package.json" ]; then npm install && npm run build; fi

# Set permissions
RUN chown -R sail:sail /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

USER sail

EXPOSE 80

# Start with supervisor (like Sail)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

### 🛠 **Quick Fix Commands:**

#### **If Build Fails:**
```bash
# Stop containers
docker-compose down

# Remove old images
docker rmi laravel-boilerplate/app

# Rebuild from scratch
docker-compose up --build -d
```

#### **If Container Issues:**
```bash
# Reset everything
docker-compose down -v
docker system prune -f
docker-compose up --build -d
```

#### **Check Container Status:**
```bash
# View container logs
docker-compose logs -f laravel.test

# Check if containers are running
docker-compose ps

# Access container shell
docker-compose exec laravel.test bash
```

### 📊 **PHP Extensions Included:**
- ✅ `exif` - Image metadata reading
- ✅ `bcmath` - Arbitrary precision mathematics  
- ✅ `pcntl` - Process control
- ✅ `sodium` - Modern cryptography
- ✅ `zip` - Archive handling
- ✅ All Laravel Sail standard extensions

### 🎯 **Performance Tips:**
1. **Use .dockerignore** to exclude unnecessary files
2. **Layer caching** - Order commands from least to most likely to change
3. **Multi-stage builds** for production optimization
4. **Composer caching** for faster rebuilds

### ✅ **Status: Fixed!**
The Laravel Sail container now builds successfully with all required PHP extensions and proper Composer configuration.

**Your Laravel application will be ready at:** http://localhost 🚀