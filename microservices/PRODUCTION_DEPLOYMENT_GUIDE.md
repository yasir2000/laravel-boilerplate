# Production Deployment Guide
## Apache Camel ERP Integration System

### 📋 Table of Contents
1. [System Overview](#system-overview)
2. [Prerequisites](#prerequisites)
3. [Production Environment Setup](#production-environment-setup)
4. [Configuration Management](#configuration-management)
5. [Deployment Procedures](#deployment-procedures)
6. [Monitoring & Alerting](#monitoring--alerting)
7. [Security Configuration](#security-configuration)
8. [Performance Optimization](#performance-optimization)
9. [Backup & Recovery](#backup--recovery)
10. [Troubleshooting](#troubleshooting)
11. [Maintenance Procedures](#maintenance-procedures)

---

## 🎯 System Overview

### Architecture Components
- **Apache Camel 4.0.3** - Enterprise integration framework with 73+ operational routes
- **Spring Boot 3.1.5** - Microservice foundation with security and metrics
- **PostgreSQL 15** - Primary database for integration data storage
- **RabbitMQ 3.12** - Message broker for asynchronous processing
- **Redis 7** - Caching layer for performance optimization
- **Prometheus** - Metrics collection and monitoring
- **Grafana** - Visualization and dashboards
- **Alertmanager** - Alert routing and notifications
- **Docker** - Containerization platform

### Integration Capabilities
- **Employee Synchronization** - Bidirectional sync with ERP systems
- **Payroll Integration** - Automated payroll data processing
- **Accounting Sync** - Financial data integration and reconciliation
- **Real-time Monitoring** - Comprehensive system health monitoring
- **Error Handling** - Robust error recovery and retry mechanisms

---

## 🔧 Prerequisites

### Infrastructure Requirements
```bash
# Minimum System Requirements
CPU: 4 cores (8 recommended)
RAM: 8GB (16GB recommended)
Storage: 50GB SSD (100GB recommended)
Network: 1Gbps connection

# Operating System
- Linux (Ubuntu 20.04+ / CentOS 8+ / RHEL 8+)
- Docker Engine 20.10+
- Docker Compose 2.0+
```

### Software Dependencies
```bash
# Required Software
- Docker Engine 24.0+
- Docker Compose 2.21+
- Git 2.30+
- OpenSSL 1.1.1+
- curl/wget for health checks

# Optional but Recommended
- Nginx (reverse proxy)
- Certbot (SSL certificates)
- logrotate (log management)
```

### Network Configuration
```bash
# Required Ports
8083  - Integration Service (HTTP)
5432  - PostgreSQL Database
5672  - RabbitMQ AMQP
15672 - RabbitMQ Management
6379  - Redis
9090  - Prometheus
3000  - Grafana
9093  - Alertmanager

# Firewall Configuration
sudo ufw allow 8083/tcp
sudo ufw allow 3000/tcp
sudo ufw allow 9090/tcp
sudo ufw allow 9093/tcp
```

---

## 🏗️ Production Environment Setup

### 1. Server Preparation
```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installation
docker --version
docker-compose --version
```

### 2. Project Deployment
```bash
# Clone repository
git clone <your-repository-url>
cd laravel-boilerplate/microservices

# Set production permissions
chmod +x scripts/*.sh
chmod 600 config/production.env
```

### 3. Environment Configuration
```bash
# Create production environment file
cp .env.example .env.production

# Configure production settings
cat > .env.production << 'EOF'
# Database Configuration
POSTGRES_DB=integration_db
POSTGRES_USER=integration_user
POSTGRES_PASSWORD=YOUR_SECURE_PASSWORD_HERE
POSTGRES_HOST=integration-db
POSTGRES_PORT=5432

# RabbitMQ Configuration
RABBITMQ_DEFAULT_USER=admin
RABBITMQ_DEFAULT_PASS=YOUR_RABBITMQ_PASSWORD_HERE
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672

# Redis Configuration
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=YOUR_REDIS_PASSWORD_HERE

# Security Configuration
JWT_SECRET=YOUR_JWT_SECRET_HERE
ENCRYPTION_KEY=YOUR_ENCRYPTION_KEY_HERE

# Monitoring Configuration
PROMETHEUS_RETENTION=30d
GRAFANA_ADMIN_PASSWORD=YOUR_GRAFANA_PASSWORD_HERE

# Alert Configuration
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
ALERT_EMAIL=alerts@yourcompany.com

# ERP Configuration
ERP_API_URL=https://your-erp-system.com/api
ERP_API_KEY=YOUR_ERP_API_KEY_HERE
ERP_USERNAME=integration_user
ERP_PASSWORD=YOUR_ERP_PASSWORD_HERE

# Performance Settings
JAVA_OPTS="-Xmx2g -Xms1g -XX:+UseG1GC"
CAMEL_THREADS=10
DB_POOL_SIZE=20
EOF
```

---

## ⚙️ Configuration Management

### 1. Security Configuration
```bash
# Generate secure passwords
openssl rand -base64 32  # Database password
openssl rand -base64 32  # RabbitMQ password
openssl rand -base64 32  # Redis password
openssl rand -base64 64  # JWT secret

# Set secure file permissions
chmod 600 .env.production
chmod 600 config/secrets/*
```

### 2. Database Configuration
```sql
-- Production database settings
-- Execute in PostgreSQL:

-- Create additional indexes for performance
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_employee_sync_status 
ON employee_sync (status, created_at);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_payroll_sync_date 
ON payroll_sync (sync_date, status);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_accounting_sync_reference 
ON accounting_sync (reference_id, status);

-- Enable query statistics
ALTER SYSTEM SET track_activity_query_size = 2048;
ALTER SYSTEM SET log_statement = 'mod';
ALTER SYSTEM SET log_min_duration_statement = 1000;

-- Reload configuration
SELECT pg_reload_conf();
```

### 3. Performance Tuning
```yaml
# docker-compose.production.yml overrides
version: '3.8'
services:
  integration-service:
    environment:
      - JAVA_OPTS=-Xmx4g -Xms2g -XX:+UseG1GC -XX:MaxGCPauseMillis=200
      - CAMEL_THREADS=20
      - HTTP_POOL_SIZE=50
    deploy:
      resources:
        limits:
          memory: 6g
          cpus: '2.0'
        reservations:
          memory: 2g
          cpus: '1.0'

  integration-db:
    environment:
      - POSTGRES_SHARED_PRELOAD_LIBRARIES=pg_stat_statements
      - POSTGRES_MAX_CONNECTIONS=100
      - POSTGRES_SHARED_BUFFERS=256MB
      - POSTGRES_EFFECTIVE_CACHE_SIZE=1GB
    deploy:
      resources:
        limits:
          memory: 2g
          cpus: '1.0'

  rabbitmq:
    environment:
      - RABBITMQ_VM_MEMORY_HIGH_WATERMARK=0.6
      - RABBITMQ_DISK_FREE_LIMIT=2GB
    deploy:
      resources:
        limits:
          memory: 1g
          cpus: '0.5'
```

---

## 🚀 Deployment Procedures

### 1. Initial Deployment
```bash
#!/bin/bash
# production-deploy.sh

set -e

echo "🚀 Starting Production Deployment..."

# Pre-deployment checks
echo "🔍 Running pre-deployment checks..."
./scripts/pre-deploy-checks.sh

# Backup existing data (if upgrading)
if docker-compose ps | grep -q integration-db; then
    echo "💾 Creating database backup..."
    ./scripts/backup-database.sh
fi

# Deploy new version
echo "📦 Deploying new version..."
docker-compose -f docker-compose.yml -f docker-compose.production.yml up -d

# Wait for services to be healthy
echo "⏳ Waiting for services to be healthy..."
./scripts/wait-for-services.sh

# Run database migrations
echo "🗃️ Running database migrations..."
./scripts/run-migrations.sh

# Verify deployment
echo "✅ Verifying deployment..."
python testing/windows-test-suite.py

echo "🎉 Production deployment completed successfully!"
```

### 2. Rolling Update Procedure
```bash
#!/bin/bash
# rolling-update.sh

# Rolling update with zero downtime
docker-compose -f docker-compose.yml -f docker-compose.production.yml up -d --no-deps integration-service

# Wait for new instance to be healthy
sleep 30
curl -f http://localhost:8083/ || exit 1

# Update remaining services if needed
docker-compose -f docker-compose.yml -f docker-compose.production.yml up -d
```

### 3. Health Check Script
```bash
#!/bin/bash
# wait-for-services.sh

services=("integration-service:8083" "grafana:3000" "prometheus:9090" "alertmanager:9093")
max_attempts=60
attempt=1

for service in "${services[@]}"; do
    IFS=':' read -r name port <<< "$service"
    echo "Checking $name on port $port..."
    
    while [ $attempt -le $max_attempts ]; do
        if curl -f -s "http://localhost:$port/" > /dev/null 2>&1; then
            echo "✅ $name is healthy"
            break
        fi
        
        if [ $attempt -eq $max_attempts ]; then
            echo "❌ $name failed to start after $max_attempts attempts"
            exit 1
        fi
        
        echo "⏳ Attempt $attempt/$max_attempts - waiting for $name..."
        sleep 5
        ((attempt++))
    done
    attempt=1
done
```

---

## 📊 Monitoring & Alerting

### 1. Grafana Dashboard Setup
```bash
# Import pre-configured dashboard
curl -X POST \
  http://admin:${GRAFANA_ADMIN_PASSWORD}@localhost:3000/api/dashboards/import \
  -H 'Content-Type: application/json' \
  -d @monitoring/apache-camel-erp-integration.json
```

### 2. Alert Rules Verification
```bash
# Check Prometheus alert rules
curl -s http://localhost:9090/api/v1/rules | jq '.data.groups[].rules[].name'

# Verify Alertmanager configuration
curl -s http://localhost:9093/api/v1/status | jq '.data.configYAML'
```

### 3. Monitoring Endpoints
```bash
# Health check endpoints
curl http://localhost:8083/                    # Integration service
curl http://localhost:9090/                    # Prometheus
curl http://localhost:3000/                    # Grafana
curl http://localhost:9093/                    # Alertmanager

# Metrics endpoints
curl http://localhost:9090/metrics             # Prometheus metrics
curl http://localhost:9093/metrics             # Alertmanager metrics
```

---

## 🔒 Security Configuration

### 1. SSL/TLS Setup
```bash
# Install Certbot for Let's Encrypt
sudo apt install certbot python3-certbot-nginx

# Generate SSL certificates
sudo certbot --nginx -d your-integration-domain.com

# Configure Nginx reverse proxy
cat > /etc/nginx/sites-available/erp-integration << 'EOF'
server {
    listen 443 ssl http2;
    server_name your-integration-domain.com;
    
    ssl_certificate /etc/letsencrypt/live/your-integration-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-integration-domain.com/privkey.pem;
    
    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
    
    # Integration service
    location /api/ {
        proxy_pass http://localhost:8083/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    # Monitoring endpoints (restrict access)
    location /grafana/ {
        auth_basic "Monitoring Access";
        auth_basic_user_file /etc/nginx/.htpasswd;
        proxy_pass http://localhost:3000/;
    }
}
EOF

# Enable site
sudo ln -s /etc/nginx/sites-available/erp-integration /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 2. Firewall Configuration
```bash
# Configure UFW firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Internal service access only from localhost
sudo ufw deny 8083  # Integration service
sudo ufw deny 3000  # Grafana
sudo ufw deny 9090  # Prometheus
sudo ufw deny 9093  # Alertmanager
```

### 3. Secrets Management
```bash
# Create secrets directory
mkdir -p /opt/erp-integration/secrets
chmod 700 /opt/erp-integration/secrets

# Store sensitive configuration
echo "YOUR_ERP_API_KEY" > /opt/erp-integration/secrets/erp_api_key
echo "YOUR_DATABASE_PASSWORD" > /opt/erp-integration/secrets/db_password
chmod 600 /opt/erp-integration/secrets/*

# Update docker-compose to use secrets
cat >> docker-compose.production.yml << 'EOF'
secrets:
  db_password:
    file: /opt/erp-integration/secrets/db_password
  erp_api_key:
    file: /opt/erp-integration/secrets/erp_api_key

services:
  integration-service:
    secrets:
      - erp_api_key
    environment:
      - ERP_API_KEY_FILE=/run/secrets/erp_api_key
EOF
```

---

## ⚡ Performance Optimization

### 1. JVM Tuning
```bash
# Production JVM settings
export JAVA_OPTS="
  -Xmx4g
  -Xms2g
  -XX:+UseG1GC
  -XX:MaxGCPauseMillis=200
  -XX:+UseStringDeduplication
  -XX:+OptimizeStringConcat
  -Djava.security.egd=file:/dev/./urandom
  -Dfile.encoding=UTF-8
  -Duser.timezone=UTC
"
```

### 2. Database Optimization
```sql
-- PostgreSQL performance tuning
-- Add to postgresql.conf

# Memory settings
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
maintenance_work_mem = 64MB

# Checkpoint settings
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100

# Connection settings
max_connections = 100
shared_preload_libraries = 'pg_stat_statements'

# Logging
log_statement = 'mod'
log_min_duration_statement = 1000
log_checkpoints = on
log_connections = on
log_disconnections = on
```

### 3. Camel Route Optimization
```java
// High-performance Camel configuration
@Component
public class CamelConfiguration {
    
    @Bean
    public CamelContext camelContext() {
        DefaultCamelContext context = new DefaultCamelContext();
        
        // Optimize thread pools
        context.getExecutorServiceManager().setDefaultThreadPoolProfile(
            new ThreadPoolProfileBuilder("default")
                .poolSize(10)
                .maxPoolSize(50)
                .maxQueueSize(1000)
                .rejectedPolicy(ThreadPoolRejectedPolicy.CallerRuns)
                .build()
        );
        
        // Enable lazy loading
        context.setLazyLoadTypeConverters(true);
        
        return context;
    }
}
```

---

## 💾 Backup & Recovery

### 1. Database Backup
```bash
#!/bin/bash
# backup-database.sh

BACKUP_DIR="/opt/backups/database"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="erp_integration_backup_${DATE}.sql"

# Create backup directory
mkdir -p $BACKUP_DIR

# Create database backup
docker exec integration-db pg_dump -U integration_user integration_db > "$BACKUP_DIR/$BACKUP_FILE"

# Compress backup
gzip "$BACKUP_DIR/$BACKUP_FILE"

# Remove backups older than 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Database backup completed: $BACKUP_DIR/${BACKUP_FILE}.gz"
```

### 2. Configuration Backup
```bash
#!/bin/bash
# backup-config.sh

BACKUP_DIR="/opt/backups/config"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup configuration files
tar -czf "$BACKUP_DIR/config_backup_${DATE}.tar.gz" \
  .env.production \
  docker-compose.production.yml \
  monitoring/ \
  config/ \
  scripts/

echo "Configuration backup completed: $BACKUP_DIR/config_backup_${DATE}.tar.gz"
```

### 3. Recovery Procedures
```bash
#!/bin/bash
# restore-database.sh

BACKUP_FILE=$1

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file.sql.gz>"
    exit 1
fi

# Stop integration service
docker-compose stop integration-service

# Restore database
gunzip -c "$BACKUP_FILE" | docker exec -i integration-db psql -U integration_user integration_db

# Start integration service
docker-compose start integration-service

echo "Database restore completed"
```

---

## 🔧 Troubleshooting

### 1. Common Issues

#### Service Won't Start
```bash
# Check container logs
docker logs integration-service --tail 100

# Check resource usage
docker stats

# Verify configuration
docker-compose config

# Check port conflicts
netstat -tlnp | grep :8083
```

#### Database Connection Issues
```bash
# Test database connectivity
docker exec integration-db psql -U integration_user -d integration_db -c "SELECT 1;"

# Check database logs
docker logs integration-db --tail 50

# Verify network connectivity
docker network ls
docker network inspect microservices_default
```

#### Performance Issues
```bash
# Check JVM memory usage
docker exec integration-service jstat -gc 1

# Monitor CPU usage
top -p $(docker inspect --format '{{.State.Pid}}' integration-service)

# Check Camel route performance
curl http://localhost:8083/camel/routes | jq '.routes[] | {id: .id, exchangesTotal: .exchangesTotal, meanProcessingTime: .meanProcessingTime}'
```

### 2. Log Analysis
```bash
# Centralized logging
docker logs integration-service 2>&1 | grep ERROR
docker logs integration-service 2>&1 | grep "sync failed"

# Performance monitoring
docker logs integration-service 2>&1 | grep "Processing time"

# Database query analysis
docker exec integration-db psql -U integration_user -d integration_db -c "
SELECT query, calls, total_time, mean_time 
FROM pg_stat_statements 
ORDER BY total_time DESC 
LIMIT 10;"
```

### 3. Health Check Scripts
```bash
#!/bin/bash
# health-check.sh

# Check all services
services=("integration-service" "integration-db" "rabbitmq" "redis" "prometheus" "grafana")

for service in "${services[@]}"; do
    if docker-compose ps $service | grep -q "Up"; then
        echo "✅ $service is running"
    else
        echo "❌ $service is not running"
    fi
done

# Check endpoints
endpoints=(
    "http://localhost:8083/:Integration Service"
    "http://localhost:9090/:Prometheus"
    "http://localhost:3000/:Grafana"
    "http://localhost:9093/:Alertmanager"
)

for endpoint in "${endpoints[@]}"; do
    IFS=':' read -r url name <<< "$endpoint"
    if curl -f -s "$url" > /dev/null; then
        echo "✅ $name endpoint is accessible"
    else
        echo "❌ $name endpoint is not accessible"
    fi
done
```

---

## 🔄 Maintenance Procedures

### 1. Regular Maintenance Tasks
```bash
#!/bin/bash
# daily-maintenance.sh

# Clean Docker system
docker system prune -f

# Update container images (if needed)
docker-compose pull

# Backup database
./scripts/backup-database.sh

# Backup configuration
./scripts/backup-config.sh

# Check disk space
df -h /opt/

# Rotate logs
docker-compose logs --since 24h > /var/log/erp-integration-$(date +%Y%m%d).log
```

### 2. Weekly Maintenance
```bash
#!/bin/bash
# weekly-maintenance.sh

# Update system packages
sudo apt update && sudo apt upgrade -y

# Check SSL certificate expiry
certbot certificates

# Performance analysis
./scripts/performance-report.sh

# Security scan
./scripts/security-check.sh
```

### 3. Monthly Maintenance
```bash
#!/bin/bash
# monthly-maintenance.sh

# Full system backup
./scripts/full-backup.sh

# Database maintenance
docker exec integration-db psql -U integration_user -d integration_db -c "VACUUM ANALYZE;"

# Update monitoring retention
docker exec prometheus promtool tsdb snapshot /prometheus

# Review and clean old logs
find /var/log -name "*.log" -mtime +90 -delete
```

---

## 📞 Support & Contact

### Emergency Contacts
- **Primary Admin**: admin@yourcompany.com
- **Database Admin**: dba@yourcompany.com
- **DevOps Team**: devops@yourcompany.com
- **24/7 Hotline**: +1-555-SUPPORT

### Documentation Links
- [Apache Camel Documentation](https://camel.apache.org/manual/)
- [Spring Boot Reference](https://spring.io/projects/spring-boot)
- [Docker Documentation](https://docs.docker.com/)
- [Prometheus Documentation](https://prometheus.io/docs/)

### Version Information
- **System Version**: 1.0.0
- **Last Updated**: $(date +%Y-%m-%d)
- **Deployment Guide Version**: 1.0.0

---

*This deployment guide provides comprehensive instructions for production deployment of the Apache Camel ERP Integration System. Keep this document updated with any configuration changes or operational procedures.*