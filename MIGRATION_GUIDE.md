# Migration Guide - Upgrading to Laravel HR Boilerplate v2.0.0

## 🔄 Overview

This guide provides step-by-step instructions for upgrading from Laravel HR Boilerplate v1.x to v2.0.0, which introduces the revolutionary AI Agents System. The upgrade process is designed to be safe and preserve all existing data while adding powerful new automation capabilities.

## ⚠️ Before You Begin

### Backup Requirements
**CRITICAL: Always backup your data before upgrading!**

```bash
# 1. Backup your database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Backup your files
tar -czf laravel_backup_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/your/laravel/app

# 3. Backup your .env file
cp .env .env.backup
```

### System Requirements Verification
Before upgrading, ensure your system meets the new requirements:

```bash
# Check PHP version (8.1+ required)
php --version

# Check Laravel version (10.x required)
php artisan --version

# Check available disk space (minimum 1GB free)
df -h

# Check memory (minimum 2GB RAM recommended)
free -m
```

### Pre-upgrade Checklist
- [ ] Current version is v1.x
- [ ] All customizations documented
- [ ] Database backup completed
- [ ] File system backup completed
- [ ] System requirements verified
- [ ] Maintenance window scheduled
- [ ] Team notified of upgrade

## 🚀 Migration Process

### Step 1: Update Codebase

#### For Git-based Installations
```bash
# 1. Stash any local changes
git stash

# 2. Fetch latest changes
git fetch origin

# 3. Switch to v2.0.0 tag
git checkout tags/v2.0.0 -b upgrade-v2.0.0

# 4. Resolve any conflicts with stashed changes
git stash pop
```

#### For Manual Installations
```bash
# 1. Download v2.0.0 release
wget https://github.com/yasir2000/laravel-boilerplate/archive/v2.0.0.tar.gz

# 2. Extract to temporary directory
tar -xzf v2.0.0.tar.gz

# 3. Copy new files (preserve customizations)
rsync -av --exclude='.env' --exclude='storage/app' laravel-boilerplate-2.0.0/ /path/to/your/app/
```

### Step 2: Update Dependencies

```bash
# 1. Update Composer dependencies
composer update

# 2. Update NPM dependencies
npm update

# 3. Install new AI Agents dependencies
composer require crewai/framework (if external service)

# 4. Install ExtJS dependencies (if not using CDN)
npm install @sencha/ext-modern@^7.0.0
```

### Step 3: Environment Configuration

#### Update .env File
Add the following new environment variables to your `.env` file:

```env
# AI Agents System Configuration
AI_AGENTS_ENABLED=true
AI_AGENTS_BASE_URL=http://localhost:8001
AI_AGENTS_TIMEOUT=30
AI_AGENTS_API_TOKEN=your-secure-api-token-here
AI_AGENTS_LOG_LEVEL=info
AI_AGENTS_DEBUG=false
AI_AGENTS_MOCK=false
AI_AGENTS_TEST_MODE=false

# CrewAI Configuration (if using external service)
CREWAI_SERVICE_URL=http://localhost:8002
CREWAI_API_KEY=your-crewai-api-key-here

# Dashboard Configuration
AI_DASHBOARD_EXTJS_CDN=https://cdn.sencha.com/ext/gpl/7.0.0
AI_DASHBOARD_THEME=triton
AI_DASHBOARD_REFRESH_INTERVAL=30

# Agent Storage Configuration
AI_AGENTS_DB_PATH=database/ai_agents.sqlite
AI_AGENTS_DB_CONNECTION=sqlite
```

#### Generate Secure API Tokens
```bash
# Generate a secure API token for AI Agents
php artisan tinker
>>> str()->random(64)
# Copy the generated token to AI_AGENTS_API_TOKEN
```

### Step 4: Database Migration

#### Run Migrations
```bash
# 1. Check migration status
php artisan migrate:status

# 2. Run new migrations
php artisan migrate

# 3. Verify migration success
php artisan migrate:status | grep "2025_"
```

#### Create AI Agents Database (SQLite)
```bash
# Create SQLite database for AI agents
touch database/ai_agents.sqlite

# Set proper permissions
chmod 664 database/ai_agents.sqlite
chown www-data:www-data database/ai_agents.sqlite
```

### Step 5: Cache and Configuration

```bash
# 1. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Optimize autoloader
composer dump-autoload --optimize
```

### Step 6: Asset Compilation

```bash
# 1. Clear previous builds
npm run clean (if available)

# 2. Install dependencies
npm install

# 3. Build assets for production
npm run build

# 4. Verify assets are built
ls -la public/build/
```

### Step 7: Permissions and Ownership

```bash
# Set correct ownership (adjust user/group as needed)
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/
sudo chown -R www-data:www-data database/

# Set correct permissions
sudo chmod -R 755 storage/
sudo chmod -R 755 bootstrap/cache/
sudo chmod 664 database/ai_agents.sqlite
```

### Step 8: Service Configuration

#### Queue Configuration
```bash
# 1. Update supervisor configuration for new AI agents jobs
sudo nano /etc/supervisor/conf.d/laravel-worker.conf

# Add AI agents queue
[program:laravel-ai-agents]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/artisan queue:work redis --queue=ai-agents --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/laravel-ai-agents.log

# 2. Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all
```

#### Web Server Configuration
Update your web server configuration to handle new routes:

**Nginx Configuration:**
```nginx
# Add location block for AI agents dashboard
location /ai-agents {
    try_files $uri $uri/ /index.php?$query_string;
}

# Add location block for AI agents API
location /api/ai-agents {
    try_files $uri $uri/ /index.php?$query_string;
}

# Add WebSocket support (if using real-time features)
location /ws {
    proxy_pass http://127.0.0.1:6001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
}
```

## 🧪 Post-Migration Testing

### System Health Verification

```bash
# 1. Check AI Agents system health
php artisan ai-agents:health-check

# 2. Verify all agents are operational
curl -s http://localhost:8000/api/test/agents/status | jq '.success'

# 3. Test dashboard accessibility
curl -s http://localhost:8000/api/test/dashboard | grep "AI Agents Dashboard"

# 4. Verify database connectivity
php artisan tinker
>>> \DB::connection()->getPdo()
>>> \DB::connection('sqlite')->getPdo() // for AI agents DB
```

### Functionality Testing

#### Test Core Features
```bash
# 1. Test user authentication
php artisan test --filter=AuthenticationTest

# 2. Test HR modules
php artisan test --filter=EmployeeTest
php artisan test --filter=LeaveTest
php artisan test --filter=PayrollTest

# 3. Test AI Agents functionality
php artisan test --filter=AIAgentsTest
```

#### Test New AI Features
1. **Dashboard Access Test:**
   - Navigate to `/ai-agents` (requires login)
   - Navigate to `/api/test/dashboard` (no login required)
   - Verify all 12 agents are displayed
   - Check system health indicators

2. **API Endpoints Test:**
   ```bash
   # Test agents status
   curl -s http://localhost:8000/api/test/agents/status
   
   # Test system health
   curl -s http://localhost:8000/api/test/system/health
   
   # Test active workflows
   curl -s http://localhost:8000/api/test/workflows/active
   ```

3. **Workflow Test:**
   - Start a test employee onboarding workflow
   - Monitor progress in the dashboard
   - Verify workflow completion

### Performance Testing

```bash
# 1. Check response times
ab -n 100 -c 10 http://localhost:8000/api/test/agents/status

# 2. Monitor memory usage
ps aux | grep php

# 3. Check database performance
mysql -u username -p -e "SHOW PROCESSLIST;"
```

## 🔧 Troubleshooting Common Issues

### Migration Failures

**Issue: Migration times out**
```bash
# Solution: Increase timeout and run migrations individually
php artisan migrate --path=database/migrations/2025_10_20_000001_create_ai_agents_tables.php --timeout=300
```

**Issue: Permission denied on SQLite database**
```bash
# Solution: Fix permissions
sudo chown www-data:www-data database/ai_agents.sqlite
sudo chmod 664 database/ai_agents.sqlite
```

### Configuration Issues

**Issue: AI Agents not responding**
```bash
# Solution: Check configuration and restart services
php artisan config:clear
php artisan config:cache
sudo supervisorctl restart all
```

**Issue: Dashboard not loading**
```bash
# Solution: Check ExtJS CDN and rebuild assets
curl -I https://cdn.sencha.com/ext/gpl/7.0.0/ext-all.js
npm run build
```

### Performance Issues

**Issue: Slow response times**
```bash
# Solution: Optimize caches and database
php artisan optimize
php artisan route:cache
php artisan config:cache
php artisan view:cache

# Optimize database
mysql -u username -p -e "OPTIMIZE TABLE employees, users, ai_agent_workflows;"
```

## 🔄 Rollback Procedure

If you need to rollback to v1.x:

### Emergency Rollback
```bash
# 1. Stop all services
sudo supervisorctl stop all

# 2. Restore database backup
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql

# 3. Restore files
tar -xzf laravel_backup_YYYYMMDD_HHMMSS.tar.gz -C /

# 4. Restore environment
cp .env.backup .env

# 5. Clear caches
php artisan cache:clear
php artisan config:clear

# 6. Restart services
sudo supervisorctl start all
```

### Graceful Rollback
```bash
# 1. Rollback migrations (if needed)
php artisan migrate:rollback --step=5

# 2. Switch to previous version
git checkout tags/v1.5.0 -b rollback-v1.5.0

# 3. Restore dependencies
composer install
npm install

# 4. Rebuild assets
npm run build
```

## 📊 Migration Checklist

### Pre-Migration
- [ ] System requirements verified
- [ ] Database backup completed
- [ ] File system backup completed  
- [ ] Current customizations documented
- [ ] Team notified and trained
- [ ] Maintenance window scheduled
- [ ] Rollback plan prepared

### During Migration
- [ ] Codebase updated to v2.0.0
- [ ] Dependencies updated (Composer & NPM)
- [ ] Environment variables configured
- [ ] Database migrations executed
- [ ] AI Agents database created
- [ ] Caches cleared and rebuilt
- [ ] Assets compiled
- [ ] Permissions set correctly
- [ ] Services configured

### Post-Migration
- [ ] System health check passed
- [ ] All agents operational
- [ ] Dashboard accessible
- [ ] API endpoints responding
- [ ] Core HR features working
- [ ] Performance benchmarks met
- [ ] User acceptance testing completed
- [ ] Documentation updated
- [ ] Team training completed
- [ ] Monitoring configured

## 📞 Support

### Getting Help During Migration
- **Email Support:** migration-support@laravel-hr-boilerplate.com
- **Documentation:** [AI Agents Setup Guide](./docs/development/ai-agents-setup.md)
- **Community Forum:** Join our Discord/Slack community
- **Emergency Support:** Available during business hours

### Migration Support Package
- **Pre-migration consultation** (30 minutes)
- **Live migration assistance** (up to 2 hours)
- **Post-migration verification** (30 minutes)
- **30-day post-migration support**

Contact sales@laravel-hr-boilerplate.com for migration support packages.

---

**Ready to upgrade? Follow this guide step-by-step and join the AI revolution in HR management!** 🚀

For additional help or questions, please don't hesitate to reach out to our support team.