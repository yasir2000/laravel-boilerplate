# 🔧 Quick Setup Guide
## Fast Track Installation for ERP Integration System

**For users who want to get started quickly**

---

## ⚡ Quick Start (5 minutes)

### 1. Clone and Navigate
```bash
git clone https://github.com/yasir2000/laravel-boilerplate.git
cd laravel-boilerplate
```

### 2. Start Services
```bash
# Start all ERP integration services
docker-compose -f docker-compose.integration.yml up -d

# Wait 2-3 minutes for services to start, then verify
docker ps
```

### 3. Verify Installation
```bash
# Check if all services are running
curl http://localhost:8083/health

# Should return: {"status":"UP"}
```

**Access Points:**
- **Integration API**: http://localhost:8083
- **Grafana Dashboard**: http://localhost:3000 (admin/admin)
- **Prometheus Metrics**: http://localhost:9090
- **RabbitMQ Management**: http://localhost:15672 (guest/guest)

---

## 🛠️ Complete Setup (30 minutes)

### Step 1: Configure ERP Credentials
```bash
chmod +x configure-credentials.sh
./configure-credentials.sh
# Follow prompts to configure Frappe, SAP, Oracle, or Dynamics 365
```

### Step 2: Set Up Security
```bash
# SSL Certificates
chmod +x setup-ssl.sh
./setup-ssl.sh
# Choose option 3 for self-signed certificates (development)

# OAuth2 Authentication
chmod +x setup-oauth2.sh
./setup-oauth2.sh
# Choose option 1 for built-in OAuth2 server
```

### Step 3: Enable Monitoring
```bash
# Health monitoring
chmod +x monitor-system.sh
./monitor-system.sh check

# Backup system
chmod +x setup-backup.sh
./setup-backup.sh
# Choose PostgreSQL and local storage for quick setup
```

---

## 🎯 Essential Commands

### Service Management
```bash
# Start services
docker-compose -f docker-compose.integration.yml up -d

# Stop services
docker-compose -f docker-compose.integration.yml down

# View logs
docker-compose -f docker-compose.integration.yml logs -f

# Restart specific service
docker-compose -f docker-compose.integration.yml restart integration-service
```

### Health Monitoring
```bash
# Quick health check
./monitor-system.sh check

# Full system status
./monitor-system.sh status

# Generate health report
./monitor-system.sh report
```

### Backup Operations
```bash
# Create backup now
./manage-backups.sh backup

# List backups
./manage-backups.sh list

# Check backup status
./manage-backups.sh status
```

---

## 🔑 Default Credentials

**System Access:**
- **Grafana**: admin / admin
- **RabbitMQ**: guest / guest
- **OAuth2 Client ID**: asoath

**Database:**
- **PostgreSQL**: erp_user / secure_password
- **Host**: localhost:5433
- **Database**: erp_integration

---

## 🚨 Troubleshooting

### Services Won't Start
```bash
# Check Docker is running
docker info

# Check port conflicts
netstat -tulpn | grep :8083

# Reset everything
docker-compose -f docker-compose.integration.yml down -v
docker-compose -f docker-compose.integration.yml up -d
```

### API Not Responding
```bash
# Check service status
docker ps | grep integration-service

# View application logs
docker logs integration-service

# Test connectivity
curl -v http://localhost:8083/health
```

### Can't Access Dashboards
```bash
# Check if Grafana is running
docker ps | grep grafana

# Reset Grafana
docker-compose -f docker-compose.integration.yml restart grafana

# Access: http://localhost:3000
```

---

## 📖 Next Steps

1. **Read Full Documentation**: See `COMPREHENSIVE_DOCUMENTATION.md`
2. **Configure Production**: Follow production deployment guide
3. **Set Up Monitoring**: Configure alerts in Grafana
4. **Schedule Backups**: Set up automated backup scheduling
5. **Security Hardening**: Implement additional security measures

---

**Need Help?** 
- Full documentation: `COMPREHENSIVE_DOCUMENTATION.md`
- Troubleshooting: See section 10 in main documentation
- System health: Run `./monitor-system.sh check`