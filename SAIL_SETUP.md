# 🚀 Laravel Sail Setup - Complete Guide

## ✅ **Laravel Sail Configuration Complete!**

### 🐳 **What's Running:**
- **Laravel App** - Sail-compatible container with PHP 8.3
- **MySQL 8.0** - Database server with full Laravel integration
- **Redis 7** - Caching, sessions & queue backend
- **Mailpit** - Email testing and debugging
- **Meilisearch** - Full-text search engine

### 🌐 **Access Points:**
- **Main App:** http://localhost
- **Mailpit Dashboard:** http://localhost:8025
- **Meilisearch:** http://localhost:7700

### 🔧 **Essential Commands:**

#### **Container Management:**
```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Rebuild containers
docker-compose up --build -d

# View container status
docker-compose ps

# View logs
docker-compose logs -f laravel.test
```

#### **Laravel Development:**
```bash
# Run Artisan commands
docker-compose exec laravel.test php artisan [command]

# Install/update dependencies
docker-compose exec laravel.test composer install
docker-compose exec laravel.test composer update

# Database operations
docker-compose exec laravel.test php artisan migrate
docker-compose exec laravel.test php artisan migrate:fresh --seed
docker-compose exec laravel.test php artisan db:seed

# Run tests
docker-compose exec laravel.test php artisan test
docker-compose exec laravel.test php artisan test --filter=ExampleTest

# Clear caches
docker-compose exec laravel.test php artisan cache:clear
docker-compose exec laravel.test php artisan config:clear
docker-compose exec laravel.test php artisan route:clear
```

#### **Performance & Debugging:**
```bash
# Access container shell
docker-compose exec laravel.test bash

# Monitor application logs
docker-compose exec laravel.test tail -f storage/logs/laravel.log

# Run queue workers
docker-compose exec laravel.test php artisan queue:work

# Run scheduled tasks
docker-compose exec laravel.test php artisan schedule:run
```

### 🚀 **Laravel Octane Integration:**

#### **Installation:**
```bash
# Install Octane with FrankenPHP
docker-compose exec laravel.test php artisan octane:install --server=frankenphp --no-interaction
```

#### **Running Octane:**
```bash
# Start Octane server
docker-compose exec laravel.test php artisan octane:frankenphp --host=0.0.0.0 --port=80

# Start Octane in background
docker-compose exec -d laravel.test php artisan octane:frankenphp --host=0.0.0.0 --port=80

# Check Octane status
docker-compose exec laravel.test php artisan octane:status

# Stop Octane
docker-compose exec laravel.test php artisan octane:stop
```

#### **Performance Benefits:**
- **2-3x faster response times**
- **Lower memory usage**
- **Better concurrent request handling**
- **HTTP/2 & HTTP/3 support**

### 🛠 **Development Workflow:**

#### **Daily Development:**
```bash
# 1. Start your development session
docker-compose up -d

# 2. Install new dependencies
docker-compose exec laravel.test composer require package/name

# 3. Run migrations for new changes
docker-compose exec laravel.test php artisan migrate

# 4. Clear caches when needed
docker-compose exec laravel.test php artisan optimize:clear

# 5. Run tests
docker-compose exec laravel.test php artisan test
```

#### **Troubleshooting:**
```bash
# Reset everything
docker-compose down -v
docker-compose up --build -d

# Check container health
docker-compose exec laravel.test php artisan about

# Database connection test
docker-compose exec laravel.test php artisan tinker
# Then run: DB::connection()->getPdo();

# Clear all Laravel caches
docker-compose exec laravel.test php artisan optimize:clear
```

### 🎯 **Environment Configuration:**

Your `.env` is configured for Laravel Sail:
- **Database:** MySQL on `mysql:3306`
- **Redis:** Redis on `redis:6379`
- **Mail:** Mailpit on `mailpit:1025`
- **Search:** Meilisearch on `meilisearch:7700`

### 📊 **Container Architecture:**
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   laravel.test  │    │      mysql      │    │      redis      │
│   (PHP 8.3)     │────│   (Database)    │    │    (Cache)      │
│   Laravel App   │    │                 │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │
         ├── mailpit (Email Testing)
         ├── meilisearch (Search)
         └── selenium (Testing)
```

### 🎉 **Benefits of This Setup:**
- ✅ **No Local PHP/MySQL Required** - Everything in containers
- ✅ **Consistent Environment** - Same setup across all machines
- ✅ **Laravel Sail Compatibility** - Standard Laravel development
- ✅ **Windows Native** - No WSL2 required
- ✅ **Production Ready** - Easy deployment to any Docker platform
- ✅ **Octane Ready** - High-performance server integration

**Your Laravel Sail environment is ready for development!** 🚀