# 📚 ERP Integration System - Complete Documentation
## Comprehensive Setup Guide and Developer Reference

**Version**: 1.0  
**Date**: October 8, 2025  
**System**: Apache Camel ERP Integration with Laravel HR  
**Author**: AI Assistant  

---

## 📋 Table of Contents

1. [System Overview](#system-overview)
2. [Architecture](#architecture)
3. [Prerequisites](#prerequisites)
4. [Installation Guide](#installation-guide)
5. [Configuration Steps](#configuration-steps)
6. [Security Implementation](#security-implementation)
7. [Monitoring & Maintenance](#monitoring--maintenance)
8. [Developer Guide](#developer-guide)
9. [API Documentation](#api-documentation)
10. [Troubleshooting](#troubleshooting)
11. [Production Deployment](#production-deployment)
12. [Maintenance & Support](#maintenance--support)

---

## 1. System Overview

### 🎯 Purpose
The ERP Integration System is a comprehensive solution that bridges Laravel HR systems with multiple ERP platforms including Frappe/ERPNext, SAP ERP, Oracle ERP Cloud, and Microsoft Dynamics 365. It provides real-time synchronization, secure authentication, automated backups, and enterprise-grade monitoring.

### 🏗️ Key Components
- **Apache Camel 4.0.3** - Integration framework with 73+ operational routes
- **Spring Boot 3.x** - Core application framework
- **PostgreSQL** - Primary database for integration data
- **Redis** - Caching and session management
- **RabbitMQ** - Message queuing for async processing
- **OAuth2/JWT** - Authentication and authorization
- **Nginx** - Reverse proxy and load balancing
- **Prometheus + Grafana** - Monitoring and metrics
- **Docker** - Containerization platform

### 📊 System Capabilities
- ✅ **Multi-ERP Support**: Frappe, SAP, Oracle, Dynamics 365
- ✅ **Real-time Synchronization**: Employee, payroll, and financial data
- ✅ **Enterprise Security**: OAuth2, SSL/TLS, AES-256 encryption
- ✅ **Automated Backups**: Encrypted with retention policies
- ✅ **Health Monitoring**: 15-minute health checks with alerting
- ✅ **Load Balancing**: Nginx reverse proxy with failover
- ✅ **API Gateway**: RESTful APIs with rate limiting
- ✅ **Audit Logging**: Complete activity tracking

---

## 2. Architecture

### 🏛️ System Architecture Diagram

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Laravel HR    │    │  Integration    │    │   ERP Systems   │
│     System      │◄──►│    Gateway      │◄──►│ Frappe/SAP/etc  │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │              ┌─────────────────┐              │
         │              │     Nginx       │              │
         │              │  Reverse Proxy  │              │
         │              └─────────────────┘              │
         │                       │                       │
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   OAuth2 Auth   │    │   Monitoring    │    │    Backup       │
│    Server       │    │ Prometheus +    │    │    System       │
│                 │    │    Grafana      │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   PostgreSQL    │    │      Redis      │    │    RabbitMQ     │
│    Database     │    │     Cache       │    │   Message       │
│                 │    │                 │    │    Queue        │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### 🔄 Data Flow Architecture

```
Laravel HR ──► API Gateway ──► Authentication ──► Route Processing
     │              │               │                    │
     │              │               │                    ▼
     │              │               │           ┌─────────────────┐
     │              │               │           │  Apache Camel   │
     │              │               │           │   Integration   │
     │              │               │           │     Engine      │
     │              │               │           └─────────────────┘
     │              │               │                    │
     │              │               │                    ▼
     │              │               │           ┌─────────────────┐
     │              │               │           │   ERP System    │
     │              │               │           │   Connectors    │
     │              │               │           └─────────────────┘
     │              │               │                    │
     ▼              ▼               ▼                    ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Data Storage Layer                           │
│  PostgreSQL  │  Redis Cache  │  Message Queue  │  File Storage  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Prerequisites

### 💻 System Requirements

**Hardware Requirements:**
- **CPU**: 4+ cores (8+ recommended for production)
- **RAM**: 8GB minimum (16GB+ recommended)
- **Storage**: 100GB available space (SSD recommended)
- **Network**: Stable internet connection for ERP system access

**Software Requirements:**
- **Operating System**: Windows 10/11, macOS, or Linux
- **Docker**: Version 20.10+ with Docker Compose
- **Git**: Version 2.30+
- **OpenSSL**: For certificate generation
- **PowerShell/Bash**: For script execution

### 🔧 Development Tools (Optional)
- **Java 17+**: For Spring Boot development
- **Node.js 18+**: For frontend development
- **Visual Studio Code**: IDE with Docker extensions
- **Postman**: API testing
- **DBeaver**: Database management

---

## 4. Installation Guide

### 📦 Step 1: Repository Setup

```bash
# Clone the repository
git clone https://github.com/yasir2000/laravel-boilerplate.git
cd laravel-boilerplate

# Verify Docker installation
docker --version
docker-compose --version

# Check available services
ls -la docker-compose*.yml
```

### 🐳 Step 2: Docker Environment Setup

```bash
# Start the ERP integration services
docker-compose -f docker-compose.integration.yml up -d

# Verify services are running
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Check service logs
docker-compose -f docker-compose.integration.yml logs -f
```

**Expected Services:**
- `integration-service` - Main application (port 8083)
- `integration-db` - PostgreSQL database (port 5433)
- `redis` - Redis cache (port 6379)
- `rabbitmq` - Message queue (ports 5672, 15672)
- `prometheus` - Metrics collection (port 9090)
- `grafana` - Monitoring dashboard (port 3000)

### 🔍 Step 3: Verification

```bash
# Test integration API
curl http://localhost:8083/health

# Access monitoring dashboards
# Grafana: http://localhost:3000
# Prometheus: http://localhost:9090
# RabbitMQ: http://localhost:15672
```

---

## 5. Configuration Steps

### 🔐 Step 1: ERP System Credentials

Run the credential configuration script:

```bash
# Make script executable
chmod +x configure-credentials.sh

# Run interactive configuration
./configure-credentials.sh
```

**Configuration Options:**
1. **Frappe/ERPNext**:
   - Frappe Cloud URL
   - API Key and Secret
   - Company name

2. **SAP ERP**:
   - SAP Server URL
   - Client ID and credentials
   - System configuration

3. **Oracle ERP Cloud**:
   - Oracle Cloud URL
   - Authentication details
   - Service endpoints

4. **Microsoft Dynamics 365**:
   - Dynamics URL
   - OAuth2 credentials
   - Tenant configuration

**Generated Files:**
- `.env.production` - Production environment variables
- Secure credential backup files

### 🛡️ Step 2: SSL/TLS Certificate Setup

```bash
# Run SSL setup script
chmod +x setup-ssl.sh
./setup-ssl.sh
```

**Certificate Options:**
1. **Let's Encrypt** (Recommended for production)
2. **Custom certificates** (Enterprise CA)
3. **Self-signed certificates** (Development only)

**Generated Files:**
- `microservices/ssl/certs/` - SSL certificates
- `microservices/ssl/private/` - Private keys
- `microservices/ssl/csr/` - Certificate signing requests

### 🔑 Step 3: OAuth2 Authentication

```bash
# Configure OAuth2 authentication
chmod +x setup-oauth2.sh
./setup-oauth2.sh
```

**Provider Options:**
1. **Built-in OAuth2 Server** (Spring Security)
2. **Azure Active Directory**
3. **Google OAuth2**
4. **Custom OAuth2 Provider**

**Generated Files:**
- `microservices/oauth2/keys/` - JWT signing keys
- `microservices/oauth2/config/` - Authentication configuration
- OAuth2 client credentials

### 🌐 Step 4: Nginx Reverse Proxy

```bash
# Set up Nginx reverse proxy
chmod +x setup-nginx.sh
./setup-nginx.sh
```

**Configuration Features:**
- SSL termination and HTTPS redirect
- Load balancing with health checks
- Rate limiting for API protection
- Security headers (HSTS, CSP, XSS protection)
- GZIP compression
- WebSocket support

**Generated Files:**
- `nginx-docker-compose.yml` - Nginx container configuration
- `microservices/nginx/conf/` - Nginx configuration files
- `manage-nginx.sh` - Nginx management utility

### 💾 Step 5: Automated Backup System

```bash
# Configure backup system
chmod +x setup-backup.sh
./setup-backup.sh
```

**Backup Configuration:**
- **Database Types**: PostgreSQL, MySQL, MongoDB
- **Encryption**: AES-256-CBC with PBKDF2
- **Destinations**: Local, AWS S3, Google Cloud, Azure, SFTP
- **Retention**: Configurable daily/weekly/monthly policies
- **Notifications**: Email, Slack, Discord

**Generated Files:**
- `backups/scripts/backup.sh` - Main backup script
- `backups/scripts/restore.sh` - Restore utility
- `backups/config/backup.conf` - Backup configuration
- `manage-backups.sh` - Backup management interface

---

## 6. Security Implementation

### 🔒 Security Architecture

**Authentication & Authorization:**
- **OAuth2/JWT**: Industry-standard authentication
- **RSA-2048**: JWT token signing
- **Role-based Access Control**: Granular permissions
- **Session Management**: Secure token handling

**Data Protection:**
- **AES-256 Encryption**: Backup data protection
- **TLS 1.2/1.3**: Transport encryption
- **Password Hashing**: bcrypt with salt
- **API Rate Limiting**: DDoS protection

**Network Security:**
- **Reverse Proxy**: Nginx with security headers
- **CORS Policies**: Cross-origin protection
- **IP Whitelisting**: Access control
- **Security Headers**: XSS, clickjacking prevention

### 🔐 Key Management

**Generated Security Keys:**

1. **Backup Encryption Key**:
   ```
   Algorithm: AES-256-CBC with PBKDF2
   Key: rY9u02SKXiP94F0SPbKzCarAxAlcQVcFQ3M4wf2IFtY=
   ```

2. **OAuth2 Client Credentials**:
   ```
   Client ID: asoath
   Client Secret: 9f+ecPBt+JVJXiWv5jzTi0dw8EogrI817l//8wrTLr4=
   ```

3. **JWT Signing Keys**:
   ```
   Private Key: microservices/oauth2/keys/jwt-private-pkcs8.pem
   Public Key: microservices/oauth2/keys/jwt-public.pem
   ```

**Security Best Practices:**
- ✅ Store keys in encrypted password manager
- ✅ Rotate keys every 3-6 months
- ✅ Never commit secrets to version control
- ✅ Use environment variables for production
- ✅ Monitor for security incidents

---

## 7. Monitoring & Maintenance

### 📊 Health Monitoring

**Automated Health Checks:**
```bash
# Run health check
./monitor-system.sh check

# Generate health report
./monitor-system.sh report

# View monitoring status
./monitor-system.sh status
```

**Monitored Components:**
- ✅ Service availability (Docker containers)
- ✅ Database connectivity
- ✅ External API connectivity
- ✅ SSL certificate validity
- ✅ Disk space usage
- ✅ Backup freshness
- ✅ Integration API responsiveness

**Health Check Results:**
```
✅ Integration service is running
✅ Database service is running
✅ Redis service is running
✅ External connectivity is working
✅ SSL certificate valid for 364 days
✅ Disk usage: 69%
✅ Latest backup is 0 hours old
✅ Integration API is responding
```

### 📈 Monitoring Dashboards

**Prometheus Metrics** (http://localhost:9090):
- System resource usage
- API response times
- Error rates and counts
- Database performance
- Message queue statistics

**Grafana Dashboard** (http://localhost:3000):
- Real-time system metrics
- Custom alerting rules
- Historical trend analysis
- Performance optimization insights

### 🔄 Backup Management

**Backup Operations:**
```bash
# Run backup now
./manage-backups.sh backup

# List available backups
./manage-backups.sh list

# Check backup status
./manage-backups.sh status

# Restore from backup
./manage-backups.sh restore database backup_file.sql.gz.enc
```

**Backup Components:**
- **Database backups**: PostgreSQL dumps with compression
- **File backups**: Application files and configurations
- **Configuration backups**: Environment and service configs

**Retention Policies:**
- **Daily backups**: 7 days retention
- **Weekly backups**: 4 weeks retention
- **Monthly backups**: 6 months retention

---

## 8. Developer Guide

### 🛠️ Development Environment Setup

**Prerequisites:**
```bash
# Install development tools
npm install -g @angular/cli
pip install pre-commit

# Set up development environment
cp .env.example .env.development
```

**IDE Configuration (VS Code):**
```json
{
  "extensions": [
    "ms-vscode.vscode-docker",
    "ms-vscode.rest-client",
    "redhat.java",
    "vscjava.vscode-spring-boot-dashboard"
  ]
}
```

### 🔧 Code Structure

**Project Directory Structure:**
```
laravel-boilerplate/
├── microservices/
│   ├── integration-service/     # Main Spring Boot application
│   ├── oauth2/                  # Authentication configuration
│   ├── nginx/                   # Reverse proxy configuration
│   └── ssl/                     # SSL certificates
├── backups/
│   ├── scripts/                 # Backup automation scripts
│   ├── config/                  # Backup configuration
│   └── database/                # Database backup storage
├── monitoring/                  # Health monitoring files
├── config/                      # Application configuration
├── docker-compose.integration.yml
├── .env.production
└── setup scripts/               # Installation and configuration
```

**Core Components:**

1. **Integration Service** (`microservices/integration-service/`):
   ```java
   @SpringBootApplication
   @EnableCamel
   public class IntegrationApplication {
       public static void main(String[] args) {
           SpringApplication.run(IntegrationApplication.class, args);
       }
   }
   ```

2. **Camel Routes** (`src/main/java/routes/`):
   ```java
   @Component
   public class ERPIntegrationRoute extends RouteBuilder {
       @Override
       public void configure() throws Exception {
           from("direct:sync-employee")
               .routeId("employee-sync")
               .to("bean:employeeProcessor")
               .to("direct:erp-endpoint");
       }
   }
   ```

### 🧪 Testing Framework

**Unit Tests:**
```bash
# Run unit tests
./mvnw test

# Run integration tests
./mvnw verify -Pintegration-tests

# Generate test reports
./mvnw jacoco:report
```

**API Testing:**
```bash
# Test integration endpoints
curl -X GET http://localhost:8083/api/health
curl -X POST http://localhost:8083/api/employees -H "Content-Type: application/json" -d "{}"
```

**Performance Testing:**
```bash
# Load testing with Apache Bench
ab -n 1000 -c 10 http://localhost:8083/api/health

# Monitor during testing
./monitor-system.sh check
```

### 🔍 Debugging

**Application Logs:**
```bash
# View application logs
docker-compose -f docker-compose.integration.yml logs -f integration-service

# Debug specific component
docker exec -it integration-service bash
```

**Database Debugging:**
```sql
-- Connect to PostgreSQL
docker exec -it integration-db psql -U erp_user -d erp_integration

-- Check integration status
SELECT * FROM integration_status ORDER BY last_sync DESC;

-- View error logs
SELECT * FROM error_logs WHERE created_at > NOW() - INTERVAL '1 hour';
```

---

## 9. API Documentation

### 🌐 Integration API Endpoints

**Base URL**: `http://localhost:8083/api`

**Authentication**: OAuth2 Bearer Token
```bash
# Get access token
curl -X POST http://localhost:8083/oauth2/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials&client_id=asoath&client_secret=CLIENT_SECRET"
```

### 📋 Core Endpoints

**1. Health Check**
```http
GET /api/health
```
Response:
```json
{
  "status": "UP",
  "components": {
    "database": "UP",
    "redis": "UP",
    "rabbitmq": "UP"
  }
}
```

**2. Employee Synchronization**
```http
POST /api/employees/sync
Authorization: Bearer {token}
Content-Type: application/json

{
  "employeeId": "12345",
  "action": "create|update|delete"
}
```

**3. Payroll Integration**
```http
POST /api/payroll/process
Authorization: Bearer {token}
Content-Type: application/json

{
  "payrollPeriod": "2025-10",
  "employees": ["12345", "67890"]
}
```

**4. Financial Data Sync**
```http
POST /api/financial/journal-entries
Authorization: Bearer {token}
Content-Type: application/json

{
  "entries": [
    {
      "account": "1000",
      "debit": 1000.00,
      "credit": 0.00,
      "description": "Salary expense"
    }
  ]
}
```

### 🔍 Monitoring Endpoints

**1. System Metrics**
```http
GET /api/metrics
Authorization: Bearer {token}
```

**2. Integration Status**
```http
GET /api/integration/status
Authorization: Bearer {token}
```

**3. Error Logs**
```http
GET /api/logs/errors?from=2025-10-08&to=2025-10-09
Authorization: Bearer {token}
```

### 🛡️ Security Headers

All API responses include security headers:
```http
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'
```

---

## 10. Troubleshooting

### 🚨 Common Issues and Solutions

**1. Docker Services Not Starting**
```bash
# Check Docker daemon
docker info

# Restart Docker services
docker-compose -f docker-compose.integration.yml down
docker-compose -f docker-compose.integration.yml up -d

# Check resource usage
docker stats
```

**2. Database Connection Issues**
```bash
# Test database connectivity
docker exec -it integration-db pg_isready

# Check database logs
docker logs integration-db

# Reset database connection
docker-compose -f docker-compose.integration.yml restart integration-db
```

**3. Authentication Failures**
```bash
# Verify OAuth2 configuration
curl http://localhost:8083/.well-known/openid_configuration

# Check JWT token validity
./test-oauth2.sh

# Regenerate OAuth2 credentials
./setup-oauth2.sh
```

**4. SSL Certificate Issues**
```bash
# Verify certificate validity
openssl x509 -in microservices/ssl/certs/erp-integration.crt -text -noout

# Check certificate expiration
openssl x509 -in microservices/ssl/certs/erp-integration.crt -noout -dates

# Regenerate certificates
./setup-ssl.sh
```

**5. Backup Failures**
```bash
# Check backup logs
cat backups/logs/backup_*.log

# Test backup manually
./manage-backups.sh backup

# Verify encryption key
echo "test" | openssl enc -aes-256-cbc -salt -pbkdf2 -k "ENCRYPTION_KEY"
```

### 🔧 Performance Optimization

**1. Memory Optimization**
```bash
# Check memory usage
docker stats --no-stream

# Increase JVM heap size
export JAVA_OPTS="-Xmx2g -Xms1g"
```

**2. Database Performance**
```sql
-- Check slow queries
SELECT query, mean_time, calls 
FROM pg_stat_statements 
ORDER BY mean_time DESC LIMIT 10;

-- Analyze table statistics
ANALYZE;
```

**3. Network Optimization**
```bash
# Check network latency
ping erp-system-url

# Monitor network usage
netstat -i
```

### 📊 Log Analysis

**Application Logs Location:**
```bash
# Integration service logs
docker logs integration-service

# Database logs
docker logs integration-db

# Nginx access logs
tail -f microservices/nginx/logs/access.log

# Health check logs
tail -f monitoring/health-check.log
```

**Log Analysis Commands:**
```bash
# Search for errors
grep -i "error" monitoring/health-check.log

# Count API requests
awk '{print $7}' microservices/nginx/logs/access.log | sort | uniq -c

# Monitor real-time logs
tail -f monitoring/health-check.log | grep -i "error\|warning"
```

---

## 11. Production Deployment

### 🚀 Production Checklist

**Pre-deployment:**
- ✅ Environment variables configured
- ✅ SSL certificates installed
- ✅ OAuth2 authentication tested
- ✅ Database migrations completed
- ✅ Backup system verified
- ✅ Monitoring alerts configured
- ✅ Load balancer configured
- ✅ Security audit completed

**Deployment Steps:**

**1. Infrastructure Setup**
```bash
# Create production environment
cp .env.production .env

# Start all services
docker-compose -f docker-compose.integration.yml up -d

# Verify deployment
./monitor-system.sh check
```

**2. Load Balancer Configuration**
```bash
# Start Nginx reverse proxy
docker-compose -f nginx-docker-compose.yml up -d

# Test load balancing
for i in {1..10}; do curl http://localhost/api/health; done
```

**3. Monitoring Setup**
```bash
# Configure automated monitoring
./monitor-system.sh setup

# Set up alerting
# Configure Grafana alerts for critical metrics
```

**4. Backup Automation**
```bash
# Schedule automated backups
# Windows: Run schedule-backups.bat as administrator
# Linux: ./manage-backups.sh schedule
```

### 🌍 Multi-Environment Configuration

**Development Environment:**
```yaml
# docker-compose.dev.yml
services:
  integration-service:
    environment:
      - SPRING_PROFILES_ACTIVE=development
      - LOG_LEVEL=DEBUG
```

**Staging Environment:**
```yaml
# docker-compose.staging.yml
services:
  integration-service:
    environment:
      - SPRING_PROFILES_ACTIVE=staging
      - LOG_LEVEL=INFO
```

**Production Environment:**
```yaml
# docker-compose.prod.yml
services:
  integration-service:
    environment:
      - SPRING_PROFILES_ACTIVE=production
      - LOG_LEVEL=WARN
```

### 🔒 Security Hardening

**1. Network Security**
```bash
# Configure firewall rules
sudo ufw allow 80,443,8083/tcp
sudo ufw deny 5432,6379,5672/tcp

# Enable fail2ban for SSH protection
sudo apt install fail2ban
```

**2. Container Security**
```bash
# Run containers as non-root
docker run --user 1000:1000 integration-service

# Enable Docker security scanning
docker scan integration-service:latest
```

**3. Database Security**
```sql
-- Create read-only user for monitoring
CREATE USER monitoring WITH PASSWORD 'secure_password';
GRANT SELECT ON ALL TABLES IN SCHEMA public TO monitoring;
```

---

## 12. Maintenance & Support

### 🔄 Regular Maintenance Tasks

**Daily Tasks:**
- ✅ Monitor system health dashboard
- ✅ Review backup success/failure logs
- ✅ Check disk space and resource usage
- ✅ Verify SSL certificate status

**Weekly Tasks:**
- ✅ Review application error logs
- ✅ Test backup restore procedures
- ✅ Update security patches
- ✅ Performance metrics analysis

**Monthly Tasks:**
- ✅ Rotate authentication keys
- ✅ Security audit and penetration testing
- ✅ Capacity planning review
- ✅ Disaster recovery testing

### 📈 Performance Monitoring

**Key Performance Indicators (KPIs):**
- **API Response Time**: < 500ms for 95th percentile
- **System Uptime**: > 99.9%
- **Database Query Time**: < 100ms average
- **Error Rate**: < 0.1%
- **Backup Success Rate**: > 99%

**Monitoring Commands:**
```bash
# Check system performance
./monitor-system.sh status

# Generate performance report
./monitor-system.sh report

# View resource usage
docker stats --no-stream
```

### 🛠️ Support Procedures

**1. Incident Response**
```bash
# Emergency health check
./monitor-system.sh check

# Quick service restart
docker-compose -f docker-compose.integration.yml restart

# Emergency backup
./manage-backups.sh backup
```

**2. Data Recovery**
```bash
# List available backups
./manage-backups.sh list

# Restore from backup
./manage-backups.sh restore database latest_backup.sql.gz.enc

# Verify data integrity
docker exec -it integration-db psql -U erp_user -c "SELECT COUNT(*) FROM employees;"
```

**3. Scale Operations**
```bash
# Scale integration service
docker-compose -f docker-compose.integration.yml up -d --scale integration-service=3

# Update load balancer configuration
# Edit nginx configuration for additional upstream servers
```

### 📞 Support Contacts

**Technical Support:**
- **System Administrator**: [Contact Information]
- **Database Administrator**: [Contact Information]
- **Security Team**: [Contact Information]
- **DevOps Engineer**: [Contact Information]

**Escalation Matrix:**
1. **Level 1**: Application issues, configuration problems
2. **Level 2**: Infrastructure issues, performance problems
3. **Level 3**: Security incidents, data corruption
4. **Emergency**: System down, data loss

---

## 📚 Additional Resources

### 🔗 Documentation Links
- [Apache Camel Documentation](https://camel.apache.org/manual/)
- [Spring Boot Reference](https://docs.spring.io/spring-boot/docs/current/reference/html/)
- [Docker Documentation](https://docs.docker.com/)
- [PostgreSQL Manual](https://www.postgresql.org/docs/)
- [OAuth2 Specification](https://oauth.net/2/)

### 🛠️ Useful Commands Reference

**Docker Management:**
```bash
# View all containers
docker ps -a

# Clean up unused resources
docker system prune -f

# View container logs
docker logs -f container_name

# Execute command in container
docker exec -it container_name bash
```

**Database Operations:**
```bash
# Connect to PostgreSQL
docker exec -it integration-db psql -U erp_user -d erp_integration

# Backup database
docker exec integration-db pg_dump -U erp_user erp_integration > backup.sql

# Restore database
docker exec -i integration-db psql -U erp_user erp_integration < backup.sql
```

**SSL Certificate Management:**
```bash
# Generate self-signed certificate
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout private.key -out certificate.crt

# Check certificate details
openssl x509 -in certificate.crt -text -noout

# Verify certificate chain
openssl verify -CAfile ca.crt certificate.crt
```

---

**Document Version**: 1.0  
**Last Updated**: October 8, 2025  
**Next Review Date**: November 8, 2025  

---

*This documentation is a living document and should be updated as the system evolves. For questions or clarifications, please contact the development team.*