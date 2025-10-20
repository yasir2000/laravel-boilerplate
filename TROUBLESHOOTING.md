# Troubleshooting Guide - AI Agents System

## 🔧 Overview

This guide provides solutions to common issues you may encounter with the AI Agents system in Laravel HR Boilerplate v2.0.0. Issues are organized by category with step-by-step solutions and prevention tips.

## 🚨 Quick Diagnostic Commands

### System Health Check
```bash
# Overall system health
php artisan ai-agents:health-check

# Check agent status
curl -s http://localhost:8000/api/test/agents/status | jq '.success'

# Verify database connectivity
php artisan tinker
>>> \DB::connection()->getPdo()
>>> \DB::connection('sqlite')->getPdo()
```

### Log Analysis
```bash
# Check AI Agents logs
tail -f storage/logs/ai-agents.log

# Check Laravel application logs
tail -f storage/logs/laravel.log

# Check system logs
tail -f /var/log/nginx/error.log
tail -f /var/log/supervisor/supervisord.log
```

## 🤖 AI Agents Issues

### Issue: Agents Not Responding

**Symptoms:**
- Dashboard shows agents as "inactive" or "error"
- API endpoints return 500 errors
- Workflows fail to start

**Diagnosis:**
```bash
# Check AI agent service connectivity
curl -v http://localhost:8001/health

# Check configuration
php artisan config:show ai_agents

# Check agent logs
tail -f storage/logs/ai-agents.log
```

**Solutions:**

1. **Service Configuration:**
```bash
# Verify environment variables
grep AI_AGENTS .env

# Update configuration
php artisan config:clear
php artisan config:cache
```

2. **Restart Services:**
```bash
# Restart queue workers
sudo supervisorctl restart laravel-worker:*

# Restart web server
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
```

3. **Check Network Connectivity:**
```bash
# Test connectivity to AI service
telnet localhost 8001

# Check firewall rules
sudo ufw status
```

**Prevention:**
- Monitor AI agent service uptime
- Set up health check alerts
- Use service discovery for production

### Issue: Workflow Stuck in "In Progress"

**Symptoms:**
- Workflows remain in "in_progress" status
- No progress updates in dashboard
- Agents appear idle but workflow not completing

**Diagnosis:**
```bash
# Check workflow status
php artisan ai-agents:workflow-status {workflow_id}

# Check queue jobs
php artisan queue:work --once --verbose

# Check for failed jobs
php artisan queue:failed
```

**Solutions:**

1. **Restart Workflow:**
```bash
# Pause and resume workflow
curl -X POST http://localhost:8000/api/ai-agents/workflows/{id}/pause
curl -X POST http://localhost:8000/api/ai-agents/workflows/{id}/resume
```

2. **Clear Queue Issues:**
```bash
# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs (if safe to do so)
php artisan queue:flush
```

3. **Emergency Workflow Reset:**
```bash
# Use emergency shutdown if needed
curl -X POST http://localhost:8000/api/ai-agents/system/emergency-shutdown
```

**Prevention:**
- Set appropriate timeouts for workflows
- Monitor workflow progress regularly
- Implement workflow health checks

### Issue: High Agent Load/Resource Usage

**Symptoms:**
- Agents showing >90% load
- System performance degraded
- Timeout errors in workflows

**Diagnosis:**
```bash
# Check system resources
htop
iotop
df -h

# Check database performance
mysql -e "SHOW PROCESSLIST;"

# Check queue length
php artisan queue:work --once --verbose
```

**Solutions:**

1. **Scale Resources:**
```bash
# Add more queue workers
sudo supervisorctl add laravel-worker-2

# Increase worker memory
# Edit supervisor config: memory_limit=256M
```

2. **Optimize Configuration:**
```bash
# Reduce agent timeout
# In .env: AI_AGENTS_TIMEOUT=15

# Adjust queue settings
# QUEUE_CONNECTION=redis
# REDIS_CLIENT=phpredis
```

3. **Database Optimization:**
```bash
# Optimize tables
mysql -e "OPTIMIZE TABLE ai_agent_workflows, ai_agent_activities;"

# Add missing indexes
php artisan migrate:refresh --seed
```

**Prevention:**
- Monitor resource usage proactively
- Set up auto-scaling for cloud deployments
- Regular performance optimization

## 🖥️ Dashboard Issues

### Issue: Dashboard Not Loading

**Symptoms:**
- Blank page when accessing `/ai-agents`
- JavaScript errors in browser console
- Loading spinner appears indefinitely

**Diagnosis:**
```bash
# Check browser console for errors
# Look for 404 errors on JS/CSS files

# Test dashboard endpoint
curl -v http://localhost:8000/api/test/dashboard

# Check ExtJS CDN availability
curl -I https://cdn.sencha.com/ext/gpl/7.0.0/ext-all.js
```

**Solutions:**

1. **Fix Asset Issues:**
```bash
# Rebuild assets
npm run build

# Clear browser cache
# In browser: Ctrl+Shift+R (hard refresh)

# Check file permissions
ls -la public/build/
```

2. **ExtJS CDN Issues:**
```bash
# Update CDN URL in config
# Edit config/ai_agents.php or .env
AI_DASHBOARD_EXTJS_CDN=https://cdn.sencha.com/ext/gpl/7.0.0

# Use local ExtJS (if available)
npm install @sencha/ext-modern
```

3. **Server Configuration:**
```bash
# Check nginx configuration
sudo nginx -t

# Verify route accessibility
php artisan route:list --name=ai-agents
```

**Prevention:**
- Use reliable CDN or local assets
- Regular asset building in CI/CD
- Monitor CDN availability

### Issue: Real-time Updates Not Working

**Symptoms:**
- Dashboard data doesn't update automatically
- Manual refresh required to see changes
- WebSocket connection failures

**Diagnosis:**
```bash
# Check WebSocket connectivity
# In browser developer tools: Network tab

# Check broadcasting configuration
php artisan config:show broadcasting

# Test pusher/websocket service
curl -v ws://localhost:6001/socket.io/
```

**Solutions:**

1. **Broadcasting Setup:**
```bash
# Install broadcasting dependencies
composer require pusher/pusher-php-server

# Configure broadcasting
php artisan config:cache
```

2. **WebSocket Service:**
```bash
# Start Laravel WebSocket server
php artisan websockets:serve

# Or use Pusher service
# Update .env with Pusher credentials
```

3. **Fallback to Polling:**
```javascript
// In dashboard.js, implement polling fallback
setInterval(function() {
    refreshDashboard();
}, 30000); // 30 seconds
```

**Prevention:**
- Use reliable WebSocket service
- Implement polling fallback
- Monitor connection health

## 🗄️ Database Issues

### Issue: SQLite Database Permissions

**Symptoms:**
- "Database is locked" errors
- Permission denied when accessing AI agents data
- SQLite file not found errors

**Diagnosis:**
```bash
# Check file permissions
ls -la database/ai_agents.sqlite

# Check directory permissions
ls -la database/

# Test database access
sqlite3 database/ai_agents.sqlite ".tables"
```

**Solutions:**

1. **Fix Permissions:**
```bash
# Set correct ownership
sudo chown www-data:www-data database/ai_agents.sqlite

# Set correct permissions
sudo chmod 664 database/ai_agents.sqlite

# Fix directory permissions
sudo chmod 755 database/
```

2. **Create Missing Database:**
```bash
# Create SQLite file
touch database/ai_agents.sqlite

# Run AI agents migrations
php artisan migrate --database=sqlite
```

3. **Switch to MySQL (if needed):**
```env
# In .env
AI_AGENTS_DB_CONNECTION=mysql
# Remove: AI_AGENTS_DB_PATH
```

**Prevention:**
- Set proper permissions during deployment
- Use MySQL for production environments
- Regular backup of SQLite files

### Issue: Database Connection Failures

**Symptoms:**
- "Connection refused" errors
- Timeout errors when accessing database
- Migration failures

**Diagnosis:**
```bash
# Test database connectivity
mysql -u username -p -e "SELECT 1;"

# Check database server status
sudo systemctl status mysql

# Check connection limits
mysql -e "SHOW VARIABLES LIKE 'max_connections';"
```

**Solutions:**

1. **Service Issues:**
```bash
# Restart database service
sudo systemctl restart mysql

# Check service logs
sudo journalctl -u mysql -f
```

2. **Connection Pool:**
```bash
# Increase connection limits
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
# Add: max_connections = 200

# Restart MySQL
sudo systemctl restart mysql
```

3. **Configuration Issues:**
```bash
# Verify database credentials
php artisan tinker
>>> config('database.connections.mysql')

# Test with different user
mysql -u root -p
```

**Prevention:**
- Monitor database connections
- Use connection pooling
- Regular database maintenance

## 🔒 Authentication & API Issues

### Issue: API Authentication Failures

**Symptoms:**
- 401 Unauthorized errors
- Invalid token responses
- Authentication required for test endpoints

**Diagnosis:**
```bash
# Check API token configuration
grep AI_AGENTS_API_TOKEN .env

# Test authentication
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/ai-agents/agents/status

# Check Sanctum configuration
php artisan config:show sanctum
```

**Solutions:**

1. **Token Issues:**
```bash
# Generate new API token
php artisan tinker
>>> $user = User::first()
>>> $token = $user->createToken('ai-agents')->plainTextToken
>>> echo $token

# Update environment
AI_AGENTS_API_TOKEN=your_new_token
```

2. **Sanctum Configuration:**
```bash
# Publish Sanctum config
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Check middleware
# Verify routes have 'auth:sanctum' middleware
```

3. **Test Endpoints:**
```bash
# Use test endpoints without auth
curl http://localhost:8000/api/test/agents/status
curl http://localhost:8000/api/test/dashboard
```

**Prevention:**
- Use secure token storage
- Regular token rotation
- Monitor authentication logs

### Issue: Rate Limiting Errors

**Symptoms:**
- 429 Too Many Requests errors
- API requests being blocked
- Intermittent access issues

**Diagnosis:**
```bash
# Check rate limiting logs
tail -f storage/logs/laravel.log | grep "rate_limit"

# Check Redis for rate limit keys
redis-cli keys "*rate_limit*"

# Test current limits
# Make multiple rapid requests
```

**Solutions:**

1. **Adjust Rate Limits:**
```php
// In app/Http/Kernel.php
'api' => [
    'throttle:api', // Increase from 60,1 to 120,1
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

2. **Clear Rate Limits:**
```bash
# Clear rate limiting cache
redis-cli flushdb

# Or specific keys
redis-cli del "throttle:api:127.0.0.1"
```

3. **Whitelist IPs:**
```php
// In app/Http/Middleware/ThrottleRequests.php
protected function resolveRequestSignature($request)
{
    $whitelist = ['127.0.0.1', '::1'];
    if (in_array($request->ip(), $whitelist)) {
        return null; // Skip rate limiting
    }
    return parent::resolveRequestSignature($request);
}
```

**Prevention:**
- Set appropriate rate limits
- Monitor API usage patterns
- Use IP whitelisting for trusted sources

## ⚡ Performance Issues

### Issue: Slow Dashboard Loading

**Symptoms:**
- Dashboard takes >5 seconds to load
- Browser freezes during loading
- Timeout errors

**Diagnosis:**
```bash
# Check network timing in browser DevTools
# Look for slow API calls

# Profile PHP performance
# Install Xdebug and use profiler

# Check database queries
tail -f storage/logs/laravel.log | grep "select"
```

**Solutions:**

1. **API Optimization:**
```php
// Cache agent status
Cache::remember('agents_status', 30, function () {
    return $this->agentService->getAgentsStatus();
});
```

2. **Database Optimization:**
```bash
# Add database indexes
php artisan make:migration add_indexes_to_ai_tables

# Optimize queries
EXPLAIN SELECT * FROM ai_agent_workflows WHERE status = 'active';
```

3. **Frontend Optimization:**
```javascript
// Lazy load dashboard components
Ext.define('AI.view.LazyDashboard', {
    extend: 'Ext.panel.Panel',
    requires: [],
    // Load components on demand
});
```

**Prevention:**
- Regular performance monitoring
- Database query optimization
- CDN for static assets

### Issue: High Memory Usage

**Symptoms:**
- PHP memory limit exceeded errors
- Server becomes unresponsive
- OOM (Out of Memory) errors

**Diagnosis:**
```bash
# Check memory usage
ps aux | grep php | awk '{sum+=$6} END {print sum/1024 " MB"}'

# Check PHP memory limit
php -i | grep memory_limit

# Monitor memory usage
watch -n 1 'free -m'
```

**Solutions:**

1. **Increase Memory Limits:**
```bash
# In php.ini
memory_limit = 512M

# Or in .env for specific requests
PHP_MEMORY_LIMIT=512M
```

2. **Code Optimization:**
```php
// Use generators for large datasets
function getAgentActivities() {
    foreach (DB::table('ai_agent_activities')->cursor() as $activity) {
        yield $activity;
    }
}

// Clear variables
unset($largeArray);
gc_collect_cycles();
```

3. **Database Optimization:**
```bash
# Use pagination for large results
# Implement database connection pooling
# Use read replicas for analytics
```

**Prevention:**
- Monitor memory usage trends
- Implement proper pagination
- Use efficient data structures

## 🛠️ Development & Deployment Issues

### Issue: Asset Compilation Failures

**Symptoms:**
- `npm run build` fails
- Missing CSS/JS files
- Version mismatch errors

**Diagnosis:**
```bash
# Check Node.js version
node --version
npm --version

# Check for conflicts
npm ls

# Review build logs
npm run build 2>&1 | tee build.log
```

**Solutions:**

1. **Dependency Issues:**
```bash
# Clear npm cache
npm cache clean --force

# Remove node_modules
rm -rf node_modules package-lock.json

# Reinstall dependencies
npm install
```

2. **Version Conflicts:**
```bash
# Update package.json
npm update

# Fix vulnerabilities
npm audit fix
```

3. **Build Configuration:**
```bash
# Check vite.config.js
# Verify build paths and settings

# Use development build for debugging
npm run dev
```

**Prevention:**
- Lock dependency versions
- Regular dependency updates
- CI/CD build verification

## 📞 Getting Help

### Log Collection for Support

When contacting support, please collect these logs:

```bash
# System information
uname -a > system_info.txt
php --version >> system_info.txt
mysql --version >> system_info.txt

# Application logs
cp storage/logs/laravel.log laravel_$(date +%Y%m%d).log
cp storage/logs/ai-agents.log ai_agents_$(date +%Y%m%d).log

# Configuration (remove sensitive data)
php artisan config:show > config_dump.txt

# Database status
mysql -e "SHOW STATUS;" > mysql_status.txt
```

### Support Channels

- **Email:** support@laravel-hr-boilerplate.com
- **Documentation:** [AI Agents User Guide](./docs/ai-agents-user-guide.md)
- **Community:** Join our Discord/Slack community
- **GitHub Issues:** Report bugs on GitHub repository

### Emergency Support

For critical production issues:
- **Email:** emergency@laravel-hr-boilerplate.com
- **Phone:** Available for enterprise customers
- **Response Time:** 4 hours for critical issues

## 🔍 Prevention Best Practices

### Monitoring Setup
```bash
# Set up log monitoring
tail -f storage/logs/*.log | grep ERROR

# Database monitoring
mysql -e "SHOW PROCESSLIST;" | wc -l

# System resource monitoring
htop
iotop
nethogs
```

### Regular Maintenance
```bash
# Weekly tasks
php artisan optimize
php artisan queue:restart
mysql -e "OPTIMIZE TABLE ai_agent_workflows, ai_agent_activities;"

# Monthly tasks
composer update
npm update
php artisan cache:clear
```

### Health Checks
```bash
# Automated health checks
*/5 * * * * /usr/bin/php /path/to/laravel/artisan ai-agents:health-check

# Log rotation
/var/log/ai-agents*.log {
    daily
    rotate 30
    compress
    missingok
    notifempty
}
```

---

**Need more help?** Check our comprehensive documentation or contact our support team. We're here to help you succeed with the AI Agents system! 🚀