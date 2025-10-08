# ⚡ Laravel Octane with FrankenPHP Setup

## ✅ **Laravel Octane + Laravel Sail Integration**

Your Laravel Boilerplate now supports high-performance serving with Laravel Octane and FrankenPHP, fully integrated with Laravel Sail.

### 🚀 **Quick Octane Setup:**

#### **Installation:**
```bash
# Install Octane in Laravel Sail container
docker-compose exec laravel.test php artisan octane:install --server=frankenphp --no-interaction
```

#### **Start High-Performance Server:**
```bash
# Start Octane with FrankenPHP
docker-compose exec laravel.test php artisan octane:frankenphp --host=0.0.0.0 --port=80

# Or run in background
docker-compose exec -d laravel.test php artisan octane:frankenphp --host=0.0.0.0 --port=80
```

### 🔧 **Octane Management:**

#### **Server Control:**
```bash
# Check Octane status
docker-compose exec laravel.test php artisan octane:status

# Stop Octane server
docker-compose exec laravel.test php artisan octane:stop

# Restart Octane server
docker-compose exec laravel.test php artisan octane:restart

# Reload application (for code changes)
docker-compose exec laravel.test php artisan octane:reload
```

#### **Performance Monitoring:**
```bash
# View Octane statistics
docker-compose exec laravel.test php artisan octane:status

# Monitor server logs
docker-compose logs -f laravel.test

# Watch application logs
docker-compose exec laravel.test tail -f storage/logs/laravel.log
```

### 🌟 **Performance Benefits:**

#### **Speed Improvements:**
- **2-3x faster response times** compared to traditional PHP-FPM
- **Lower memory usage** with persistent application state
- **Better concurrent handling** of multiple requests
- **Faster static file serving** with built-in optimization

#### **Modern Web Standards:**
- **HTTP/2 support** for multiplexed connections
- **HTTP/3 support** for even faster performance
- **WebSocket support** for real-time features
- **Server-sent events** for live updates

### ⚙️ **Configuration:**

#### **Environment Variables:**
Your `.env` file is configured for Octane:
```env
OCTANE_SERVER=frankenphp
OCTANE_HOST=0.0.0.0
OCTANE_PORT=80
OCTANE_WORKERS=auto
OCTANE_MAX_REQUESTS=500
OCTANE_HTTPS=false
```

#### **Octane Configuration File:**
Located at `config/octane.php` with optimized settings for:
- Request lifecycle management
- Memory leak prevention
- File change detection
- Worker process management

### 🛠 **Development Workflow:**

#### **Code Changes:**
```bash
# After making code changes, reload Octane
docker-compose exec laravel.test php artisan octane:reload

# Or restart for configuration changes
docker-compose exec laravel.test php artisan octane:restart
```

#### **Debugging:**
```bash
# Run without Octane for debugging
docker-compose exec laravel.test php artisan serve --host=0.0.0.0 --port=8080

# Then access via http://localhost:8080
```

### 🔍 **Troubleshooting:**

#### **Common Issues:**

**Memory Leaks:**
```bash
# Check memory usage
docker stats laravel-boilerplate-laravel.test-1

# Restart workers if needed
docker-compose exec laravel.test php artisan octane:restart
```

**Performance Issues:**
```bash
# Optimize Laravel
docker-compose exec laravel.test php artisan optimize

# Clear all caches
docker-compose exec laravel.test php artisan optimize:clear
```

**Configuration Problems:**
```bash
# Reinstall Octane
docker-compose exec laravel.test php artisan octane:install --server=frankenphp --force

# Verify configuration
docker-compose exec laravel.test php artisan config:show octane
```

### 📊 **Performance Comparison:**

| Server Type | Requests/sec | Response Time | Memory Usage |
|-------------|--------------|---------------|--------------|
| PHP-FPM     | ~100 req/s   | ~50ms        | High         |
| **Octane**  | **~300 req/s** | **~15ms**  | **Low**      |

### 🎯 **When to Use Octane:**

#### **Perfect For:**
- High-traffic applications
- API servers with many concurrent requests
- Real-time applications
- Performance-critical systems

#### **Consider Alternatives For:**
- Development environments (optional)
- Simple CRUD applications
- Applications with extensive file I/O

### 🚀 **Production Deployment:**

#### **Docker Production Setup:**
```bash
# Build production image
docker build -f Dockerfile.sail -t laravel-boilerplate:latest .

# Run with Octane
docker run -d -p 80:80 -e OCTANE_SERVER=frankenphp laravel-boilerplate:latest
```

#### **Process Management:**
Consider using process managers like:
- **Supervisor** for process monitoring
- **Docker Swarm** for container orchestration
- **Kubernetes** for large-scale deployments

### ✅ **Setup Complete!**

Your Laravel Boilerplate now runs on **Laravel Octane with FrankenPHP** providing:
- ⚡ **Superior performance**
- 🐳 **Full Docker integration**
- 🛠 **Easy development workflow**
- 🚀 **Production-ready setup**

**Access your high-performance app at:** http://localhost 🎉