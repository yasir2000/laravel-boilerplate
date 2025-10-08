# Apache Camel ERP Integration System - Complete Documentation

## 🎯 Overview

This is a comprehensive Apache Camel-based ERP integration system that synchronizes data between Laravel HR system and various ERP systems (Frappe/ERPNext, generic ERP APIs). The system provides bidirectional synchronization for employees, payroll, accounting, and leave management.

## 🏗️ System Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Laravel HR    │◄──►│ Integration     │◄──►│   ERP System    │
│   Application   │    │   Service       │    │ (Frappe/Other)  │
│   (Port 8000)   │    │  (Port 8083)    │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         ▲                       ▲                       ▲
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│     MySQL       │    │   PostgreSQL    │    │    RabbitMQ     │
│   (Port 3306)   │    │   (Port 5433)   │    │  (Port 5672)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                ▲
                                │
                                ▼
                    ┌─────────────────┐    ┌─────────────────┐
                    │   Prometheus    │◄──►│    Grafana      │
                    │   (Port 9090)   │    │   (Port 3000)   │
                    └─────────────────┘    └─────────────────┘
```

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Laravel application running
- ERP system accessible (Frappe/ERPNext or other)

### 1. Start Integration Services
```bash
cd /path/to/laravel-boilerplate
docker compose -f docker-compose.integration.yml up -d
```

### 2. Verify Services
```bash
# Check all services are running
docker compose -f docker-compose.integration.yml ps

# Test API endpoints
./test-integration-api.sh
```

### 3. Configure ERP Credentials
```bash
# Copy and configure environment file
cp .env.integration.example .env.integration
# Edit with your ERP credentials
```

## 📋 Services Overview

| Service | Port | Purpose | Health Check |
|---------|------|---------|--------------|
| Integration Service | 8083 | Apache Camel ERP integration | `curl http://localhost:8083/integration/actuator/health` |
| PostgreSQL | 5433 | Integration data storage | Docker health check |
| RabbitMQ | 5672/15672 | Message broker | `http://localhost:15672` |
| Redis | 6379 | Caching layer | Docker health check |
| Prometheus | 9090 | Metrics collection | `http://localhost:9090` |
| Grafana | 3000 | Monitoring dashboards | `http://localhost:3000` |

## 🔧 API Endpoints

### Authentication
All endpoints require Basic Authentication:
- Username: `admin`
- Password: `admin123`

### Health & Status
```bash
# Service health
GET /integration/actuator/health

# Camel health check
GET /integration/camel/health

# Integration status
GET /integration/camel/integration/status
```

### Synchronization Endpoints
```bash
# Employee Sync
POST /integration/camel/employee/sync    # Trigger sync
GET  /integration/camel/employee/status  # Check status

# Payroll Sync  
POST /integration/camel/payroll/sync     # Trigger sync
GET  /integration/camel/payroll/status   # Check status

# Accounting Sync
POST /integration/camel/accounting/sync  # Trigger sync
GET  /integration/camel/accounting/status # Check status
```

### Example Usage
```bash
# Trigger employee synchronization
curl -X POST -H "Authorization: Basic YWRtaW46YWRtaW4xMjM=" \
  http://localhost:8083/integration/camel/employee/sync

# Check employee sync status
curl -H "Authorization: Basic YWRtaW46YWRtaW4xMjM=" \
  http://localhost:8083/integration/camel/employee/status
```

## ⚙️ Configuration

### Environment Variables (.env.integration)

#### Frappe/ERPNext Configuration
```bash
FRAPPE_ENABLED=true
FRAPPE_BASE_URL=https://your-frappe-instance.com
FRAPPE_API_KEY=your-api-key
FRAPPE_API_SECRET=your-api-secret
FRAPPE_TIMEOUT=60000
FRAPPE_RETRY_ATTEMPTS=3
```

#### Generic ERP Configuration
```bash
GENERIC_ERP_ENABLED=false
GENERIC_ERP_BASE_URL=https://your-erp-system.com/api
GENERIC_ERP_AUTH_TYPE=bearer
GENERIC_ERP_TOKEN=your-bearer-token
```

#### Sync Settings
```bash
EMPLOYEE_SYNC_ENABLED=true
EMPLOYEE_BATCH_SIZE=50
EMPLOYEE_SYNC_SCHEDULE="0 2 * * *"

PAYROLL_SYNC_ENABLED=true
PAYROLL_BATCH_SIZE=25
PAYROLL_SYNC_SCHEDULE="0 3 * * *"

ACCOUNTING_SYNC_ENABLED=true
ACCOUNTING_BATCH_SIZE=100
ACCOUNTING_SYNC_SCHEDULE="0 4 * * *"
```

## 📊 Monitoring Setup

### Grafana Dashboard
1. Access: `http://localhost:3000`
2. Login: `admin` / `admin` (change on first login)
3. Add Prometheus data source: `http://prometheus:9090`
4. Import dashboard: Upload `grafana-dashboard.json`

### Key Metrics
- Service health status
- Sync success/failure rates
- Response time distribution
- Error rates by type
- Database connection pool status
- Sync operations timeline

### Prometheus Metrics
```bash
# View raw metrics
curl -H "Authorization: Basic YWRtaW46YWRtaW4xMjM=" \
  http://localhost:8083/integration/actuator/prometheus
```

## 🔄 Data Synchronization

### Employee Sync Flow
1. **Laravel → ERP**: Fetch employees from Laravel HR API
2. **Transform**: Convert Laravel format to ERP format
3. **Send**: Push to ERP system (Frappe Employee doctype)
4. **Bidirectional**: Pull updates from ERP back to Laravel

### Payroll Sync Flow
1. **Laravel → ERP**: Fetch payroll data from Laravel
2. **Transform**: Map to ERP salary slip format
3. **Send**: Create salary slips in ERP
4. **Status Sync**: Update payroll status in Laravel

### Accounting Sync Flow
1. **Chart of Accounts**: Sync account structure
2. **Journal Entries**: Sync financial transactions
3. **Expense Claims**: Employee expense submissions
4. **Purchase Orders**: Procurement data sync

### Leave Management
1. **Leave Applications**: Sync leave requests
2. **Approval Workflow**: Bidirectional status updates
3. **Leave Balance**: Sync remaining leave balances

## 🛠️ Data Transformation

### Laravel HR → ERP Mapping

#### Employee Data
```json
Laravel Format:
{
  "id": 123,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@company.com",
  "department_id": 5,
  "position": "Developer"
}

ERP Format:
{
  "employee_name": "John Doe",
  "user_id": "john@company.com",
  "department": "IT",
  "designation": "Developer",
  "status": "Active"
}
```

#### Payroll Data
```json
Laravel Format:
{
  "user_id": 123,
  "basic_salary": 5000,
  "allowances": 1000,
  "deductions": 500,
  "net_salary": 5500,
  "pay_period": "2024-01"
}

ERP Format:
{
  "employee": "EMP-001",
  "gross_pay": 6000,
  "total_deduction": 500,
  "net_pay": 5500,
  "posting_date": "2024-01-31"
}
```

## 🚨 Error Handling

### Error Types & Responses
- **Authentication Errors**: 401 responses with token refresh
- **Authorization Errors**: 403 responses with role verification
- **Rate Limiting**: 429 responses with backoff strategy
- **Server Errors**: 500 responses with retry logic
- **Connection Errors**: Automatic reconnection attempts

### Error Recovery
```java
// Automatic retry with exponential backoff
onException(ConnectException.class)
    .retryDelay(5000)
    .maximumRedeliveries(3)
    .redeliveryDelay(5000)
    .to("direct:handle-connection-error");
```

### Dead Letter Queue
Failed messages are routed to dead letter queue for manual inspection:
```bash
# Check failed messages in RabbitMQ Management
http://localhost:15672
```

## 🔍 Troubleshooting

### Common Issues

#### Service Won't Start
```bash
# Check Docker logs
docker logs integration-service

# Common fixes:
# 1. Database connection issues
# 2. Missing dependencies  
# 3. Configuration errors
```

#### Authentication Failures
```bash
# Verify credentials
curl -H "Authorization: Basic YWRtaW46YWRtaW4xMjM=" \
  http://localhost:8083/integration/camel/health

# Check if service is secured
curl http://localhost:8083/integration/actuator/health
```

#### ERP Connection Issues
1. Verify ERP URL accessibility
2. Check API credentials
3. Validate network connectivity
4. Review firewall settings

#### Sync Failures
1. Check error logs in Grafana
2. Verify data format compatibility
3. Test individual endpoints
4. Review transformation logic

### Debug Commands
```bash
# Service logs
docker logs integration-service --tail 50

# Check database connection
docker exec -it integration-db psql -U integration_user -d integration_db

# Test RabbitMQ
curl http://localhost:15672

# Prometheus metrics
curl -H "Authorization: Basic YWRtaW46YWRtaW4xMjM=" \
  http://localhost:8083/integration/actuator/prometheus | grep employee
```

## 🔐 Security Considerations

### Authentication
- Basic HTTP authentication for API endpoints
- Secure credential storage in environment variables
- Token-based authentication for ERP systems

### Network Security
- Docker network isolation
- Firewall configuration for external ERP access
- HTTPS for production deployments

### Data Protection
- Encrypted data transmission
- Secure database connections
- PII data handling compliance

## 📈 Performance Optimization

### Batch Processing
- Configurable batch sizes for large datasets
- Parallel processing for multiple sync operations
- Queue-based async processing

### Caching Strategy
- Redis for frequently accessed data
- HTTP response caching
- Database query optimization

### Resource Management
- Connection pooling for databases
- Thread pool optimization for Camel routes
- Memory management for large payloads

## 🚀 Deployment

### Production Checklist
- [ ] Update default passwords
- [ ] Configure HTTPS certificates
- [ ] Set up proper logging levels
- [ ] Configure backup strategies
- [ ] Set up monitoring alerts
- [ ] Test disaster recovery procedures

### Environment-Specific Configuration
```bash
# Development
INTEGRATION_LOG_LEVEL=debug
FRAPPE_ENABLED=false

# Production  
INTEGRATION_LOG_LEVEL=warn
FRAPPE_ENABLED=true
INTEGRATION_ENCRYPTION_ENABLED=true
```

## 📞 Support

### Logs Location
- Integration Service: `docker logs integration-service`
- Database: `docker logs integration-db`
- Message Queue: `docker logs rabbitmq`

### Monitoring Dashboards
- Grafana: `http://localhost:3000`
- Prometheus: `http://localhost:9090`
- RabbitMQ Management: `http://localhost:15672`

### Health Checks
```bash
# Automated health check script
./test-integration-api.sh

# Manual health verification
curl http://localhost:8083/integration/actuator/health
```

---

**Last Updated**: October 8, 2025  
**Version**: 1.0.0  
**Apache Camel Version**: 4.0.3  
**Spring Boot Version**: 3.1.5