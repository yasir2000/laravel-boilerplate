# Service Discovery & Configuration Management

## 🎯 Overview

Service Discovery and Configuration Management provides dynamic service registration, health checking, and centralized configuration for the HR microservices ecosystem using Consul and Vault.

### 🏗️ Architecture

```mermaid
graph TB
    subgraph "Service Discovery Layer"
        subgraph "Consul Cluster"
            ConsulServer1[Consul Server 1<br/>Leader]
            ConsulServer2[Consul Server 2<br/>Follower]
            ConsulServer3[Consul Server 3<br/>Follower]
        end
        
        subgraph "Consul Agents"
            ConsulAgent1[Consul Agent<br/>Node 1]
            ConsulAgent2[Consul Agent<br/>Node 2]
            ConsulAgent3[Consul Agent<br/>Node 3]
        end
        
        subgraph "Service Registry"
            ServiceCatalog[Service Catalog]
            HealthChecks[Health Checks]
            ServiceMesh[Service Mesh<br/>Connect]
        end
    end
    
    subgraph "Configuration Management"
        subgraph "Vault Cluster"
            VaultServer1[Vault Server 1<br/>Active]
            VaultServer2[Vault Server 2<br/>Standby]
            VaultServer3[Vault Server 3<br/>Standby]
        end
        
        subgraph "Secret Engines"
            DatabaseSecrets[Database<br/>Secrets Engine]
            PKISecrets[PKI<br/>Secrets Engine]
            KVSecrets[Key-Value<br/>Secrets Engine]
            AWSSecrets[AWS<br/>Secrets Engine]
        end
        
        subgraph "Authentication"
            JWTAuth[JWT Authentication]
            K8sAuth[Kubernetes Auth]
            AppRoleAuth[AppRole Auth]
        end
    end
    
    subgraph "Microservices"
        UserService[User Service]
        EmployeeService[Employee Service]
        AttendanceService[Attendance Service]
        APIGateway[API Gateway]
    end
    
    subgraph "Infrastructure"
        LoadBalancer[Load Balancer]
        Databases[(Databases)]
        MessageBroker[Message Broker]
        Cache[Cache Layer]
    end
    
    subgraph "Configuration Sources"
        GitOps[GitOps Repository]
        EnvironmentConfigs[Environment Configs]
        FeatureFlags[Feature Flags]
    end
    
    % Service Registration
    UserService --> ConsulAgent1
    EmployeeService --> ConsulAgent2
    AttendanceService --> ConsulAgent3
    APIGateway --> ConsulAgent1
    
    % Consul Cluster
    ConsulAgent1 --> ConsulServer1
    ConsulAgent2 --> ConsulServer1
    ConsulAgent3 --> ConsulServer1
    
    ConsulServer1 -.-> ConsulServer2
    ConsulServer2 -.-> ConsulServer3
    ConsulServer3 -.-> ConsulServer1
    
    % Service Discovery
    ConsulServer1 --> ServiceCatalog
    ConsulServer1 --> HealthChecks
    ConsulServer1 --> ServiceMesh
    
    % Configuration Management
    UserService --> VaultServer1
    EmployeeService --> VaultServer1
    AttendanceService --> VaultServer1
    APIGateway --> VaultServer1
    
    % Vault Cluster
    VaultServer1 -.-> VaultServer2
    VaultServer2 -.-> VaultServer3
    VaultServer3 -.-> VaultServer1
    
    % Secret Engines
    VaultServer1 --> DatabaseSecrets
    VaultServer1 --> PKISecrets
    VaultServer1 --> KVSecrets
    VaultServer1 --> AWSSecrets
    
    % Authentication
    VaultServer1 --> JWTAuth
    VaultServer1 --> K8sAuth
    VaultServer1 --> AppRoleAuth
    
    % Infrastructure Discovery
    APIGateway --> LoadBalancer
    ServiceCatalog --> Databases
    ServiceCatalog --> MessageBroker
    ServiceCatalog --> Cache
    
    % Configuration Sources
    VaultServer1 --> GitOps
    VaultServer1 --> EnvironmentConfigs
    ConsulServer1 --> FeatureFlags
    
    classDef consul fill:#e3f2fd
    classDef vault fill:#f3e5f5
    classDef service fill:#e8f5e8
    classDef infrastructure fill:#fff3e0
    classDef config fill:#fce4ec
    
    class ConsulServer1,ConsulServer2,ConsulServer3,ConsulAgent1,ConsulAgent2,ConsulAgent3,ServiceCatalog,HealthChecks,ServiceMesh consul
    class VaultServer1,VaultServer2,VaultServer3,DatabaseSecrets,PKISecrets,KVSecrets,AWSSecrets,JWTAuth,K8sAuth,AppRoleAuth vault
    class UserService,EmployeeService,AttendanceService,APIGateway service
    class LoadBalancer,Databases,MessageBroker,Cache infrastructure
    class GitOps,EnvironmentConfigs,FeatureFlags config
```

## 🔍 Consul Service Discovery

### Consul Server Configuration
```hcl
# consul-server.hcl
datacenter = "hr-microservices"
data_dir = "/opt/consul/data"
log_level = "INFO"
node_name = "consul-server-1"
server = true

# Clustering
bootstrap_expect = 3
retry_join = ["consul-server-2", "consul-server-3"]

# Network
bind_addr = "0.0.0.0"
client_addr = "0.0.0.0"

# Ports
ports {
  grpc = 8502
  http = 8500
  https = 8501
  dns = 8600
}

# Security
encrypt = "qDOPBEr+/oUqODBNaElWzQ=="
ca_file = "/opt/consul/tls/ca.crt"
cert_file = "/opt/consul/tls/consul.crt"
key_file = "/opt/consul/tls/consul.key"
verify_incoming = true
verify_outgoing = true
verify_server_hostname = true

# ACLs
acl = {
  enabled = true
  default_policy = "deny"
  enable_token_persistence = true
  tokens = {
    master = "your-master-token"
    agent = "your-agent-token"
  }
}

# Connect (Service Mesh)
connect {
  enabled = true
  ca_provider = "consul"
  ca_config {
    private_key_type = "ec"
    private_key_bits = 256
    root_cert_ttl = "87600h"
    intermediate_cert_ttl = "8760h"
  }
}

# UI
ui_config {
  enabled = true
  dir = "/opt/consul/ui"
  content_path = "/consul/"
}

# Performance
performance {
  raft_multiplier = 1
  rpc_hold_timeout = "7s"
}

# Logging
enable_syslog = true
syslog_facility = "LOCAL0"

# Telemetry
telemetry {
  prometheus_retention_time = "60s"
  disable_hostname = false
  metrics_prefix = "consul"
}
```

### Consul Agent Configuration
```hcl
# consul-agent.hcl
datacenter = "hr-microservices"
data_dir = "/opt/consul/data"
log_level = "INFO"
node_name = "consul-agent-node-1"
server = false

# Join cluster
retry_join = ["consul-server-1", "consul-server-2", "consul-server-3"]

# Network
bind_addr = "0.0.0.0"
client_addr = "127.0.0.1"

# Security
encrypt = "qDOPBEr+/oUqODBNaElWzQ=="
ca_file = "/opt/consul/tls/ca.crt"
cert_file = "/opt/consul/tls/consul-agent.crt"
key_file = "/opt/consul/tls/consul-agent.key"
verify_incoming = false
verify_outgoing = true

# ACLs
acl = {
  enabled = true
  default_policy = "deny"
  enable_token_persistence = true
  tokens = {
    agent = "your-agent-token"
  }
}

# Services
services {
  name = "user-service"
  id = "user-service-1"
  port = 3001
  tags = ["microservice", "hr", "user-management"]
  
  meta = {
    version = "1.0.0"
    environment = "production"
    team = "hr-platform"
  }
  
  check {
    http = "http://localhost:3001/health"
    interval = "30s"
    timeout = "10s"
    deregister_critical_service_after = "5m"
  }
  
  check {
    tcp = "localhost:3001"
    interval = "10s"
    timeout = "5s"
  }
  
  connect {
    sidecar_service {
      port = 20000
      proxy {
        upstreams = [
          {
            destination_name = "employee-service"
            local_bind_port = 3002
          },
          {
            destination_name = "postgres-user-db"
            local_bind_port = 5432
          }
        ]
      }
    }
  }
}

services {
  name = "employee-service"
  id = "employee-service-1"
  port = 3002
  tags = ["microservice", "hr", "employee-management"]
  
  meta = {
    version = "1.0.0"
    environment = "production"
    team = "hr-platform"
  }
  
  check {
    http = "http://localhost:3002/health"
    interval = "30s"
    timeout = "10s"
    deregister_critical_service_after = "5m"
  }
  
  connect {
    sidecar_service {
      port = 20001
      proxy {
        upstreams = [
          {
            destination_name = "user-service"
            local_bind_port = 3001
          },
          {
            destination_name = "postgres-employee-db"
            local_bind_port = 5433
          },
          {
            destination_name = "elasticsearch"
            local_bind_port = 9200
          }
        ]
      }
    }
  }
}

services {
  name = "attendance-service"
  id = "attendance-service-1"
  port = 3003
  tags = ["microservice", "hr", "attendance-tracking"]
  
  meta = {
    version = "1.0.0"
    environment = "production"
    team = "hr-platform"
  }
  
  check {
    http = "http://localhost:3003/health"
    interval = "30s"
    timeout = "10s"
    deregister_critical_service_after = "5m"
  }
  
  connect {
    sidecar_service {
      port = 20002
      proxy {
        upstreams = [
          {
            destination_name = "employee-service"
            local_bind_port = 3002
          },
          {
            destination_name = "timescaledb"
            local_bind_port = 5434
          },
          {
            destination_name = "redis-cluster"
            local_bind_port = 6379
          }
        ]
      }
    }
  }
}

# Connect configuration
connect {
  enabled = true
}
```

### Service Registration Script
```bash
#!/bin/bash
# register-service.sh

set -e

CONSUL_HTTP_ADDR=${CONSUL_HTTP_ADDR:-"http://consul:8500"}
CONSUL_HTTP_TOKEN=${CONSUL_HTTP_TOKEN:-""}
SERVICE_NAME=${SERVICE_NAME:-"unknown-service"}
SERVICE_ID=${SERVICE_ID:-"${SERVICE_NAME}-$(hostname)"}
SERVICE_PORT=${SERVICE_PORT:-"3000"}
SERVICE_ADDRESS=${SERVICE_ADDRESS:-"$(hostname -i)"}
HEALTH_CHECK_URL=${HEALTH_CHECK_URL:-"http://${SERVICE_ADDRESS}:${SERVICE_PORT}/health"}

# Create service definition
cat > /tmp/service-definition.json << EOF
{
  "ID": "${SERVICE_ID}",
  "Name": "${SERVICE_NAME}",
  "Tags": [
    "microservice",
    "hr",
    "${SERVICE_NAME}",
    "version-${SERVICE_VERSION:-1.0.0}",
    "environment-${ENVIRONMENT:-production}"
  ],
  "Address": "${SERVICE_ADDRESS}",
  "Port": ${SERVICE_PORT},
  "Meta": {
    "version": "${SERVICE_VERSION:-1.0.0}",
    "environment": "${ENVIRONMENT:-production}",
    "team": "${TEAM:-hr-platform}",
    "started_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  },
  "EnableTagOverride": false,
  "Check": {
    "HTTP": "${HEALTH_CHECK_URL}",
    "Interval": "30s",
    "Timeout": "10s",
    "DeregisterCriticalServiceAfter": "5m"
  },
  "Checks": [
    {
      "HTTP": "${HEALTH_CHECK_URL}",
      "Interval": "30s",
      "Timeout": "10s",
      "Name": "HTTP Health Check"
    },
    {
      "TCP": "${SERVICE_ADDRESS}:${SERVICE_PORT}",
      "Interval": "10s",
      "Timeout": "5s",
      "Name": "TCP Port Check"
    }
  ]
}
EOF

# Register service
echo "Registering service ${SERVICE_NAME} with Consul..."
curl -X PUT \
  -H "X-Consul-Token: ${CONSUL_HTTP_TOKEN}" \
  -d @/tmp/service-definition.json \
  "${CONSUL_HTTP_ADDR}/v1/agent/service/register"

if [ $? -eq 0 ]; then
  echo "Service ${SERVICE_NAME} registered successfully"
else
  echo "Failed to register service ${SERVICE_NAME}"
  exit 1
fi

# Setup graceful shutdown
trap 'deregister_service' TERM INT

deregister_service() {
  echo "Deregistering service ${SERVICE_NAME}..."
  curl -X PUT \
    -H "X-Consul-Token: ${CONSUL_HTTP_TOKEN}" \
    "${CONSUL_HTTP_ADDR}/v1/agent/service/deregister/${SERVICE_ID}"
  echo "Service deregistered"
  exit 0
}

# Keep script running
while true; do
  sleep 30
  # Optional: Update service metadata or perform health self-checks
done
```

## 🔐 HashiCorp Vault Configuration

### Vault Server Configuration
```hcl
# vault.hcl
ui = true
cluster_name = "hr-microservices-vault"

# Storage backend (Consul)
storage "consul" {
  address = "consul:8500"
  path = "vault/"
  token = "vault-consul-token"
  
  # High availability
  ha_enabled = true
  
  # Security
  tls_ca_file = "/vault/tls/ca.crt"
  tls_cert_file = "/vault/tls/consul.crt"
  tls_key_file = "/vault/tls/consul.key"
  tls_skip_verify = false
}

# Alternative: Integrated storage (Raft)
# storage "raft" {
#   path = "/vault/data"
#   node_id = "vault-node-1"
#   
#   retry_join {
#     leader_api_addr = "https://vault-node-2:8200"
#   }
#   retry_join {
#     leader_api_addr = "https://vault-node-3:8200"
#   }
# }

# HTTP listener
listener "tcp" {
  address = "0.0.0.0:8200"
  
  # TLS configuration
  tls_cert_file = "/vault/tls/vault.crt"
  tls_key_file = "/vault/tls/vault.key"
  tls_client_ca_file = "/vault/tls/ca.crt"
  tls_min_version = "tls12"
  tls_cipher_suites = "TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256,TLS_ECDHE_RSA_WITH_AES_256_GCM_SHA384"
  
  # Security headers
  tls_prefer_server_cipher_suites = true
  tls_require_and_verify_client_cert = false
}

# Cluster listener
listener "tcp" {
  address = "0.0.0.0:8201"
  cluster_address = "0.0.0.0:8201"
  
  tls_cert_file = "/vault/tls/vault.crt"
  tls_key_file = "/vault/tls/vault.key"
  tls_client_ca_file = "/vault/tls/ca.crt"
}

# API address
api_addr = "https://vault.hr-microservices.local:8200"
cluster_addr = "https://vault-node-1:8201"

# Seal configuration (Auto-unseal with AWS KMS)
seal "awskms" {
  region = "us-east-1"
  kms_key_id = "arn:aws:kms:us-east-1:123456789012:key/your-kms-key-id"
}

# Alternative: Transit auto-unseal
# seal "transit" {
#   address = "https://vault-cluster.company.com:8200"
#   token = "your-transit-token"
#   key_name = "hr-microservices-unseal-key"
#   mount_path = "transit/"
# }

# Telemetry
telemetry {
  prometheus_retention_time = "60s"
  disable_hostname = false
  usage_gauge_period = "10m"
  maximum_gauge_cardinality = 500
  
  statsd_address = "statsd:8125"
}

# Entropy configuration
entropy "seal" {
  mode = "augmentation"
}

# Performance and tuning
default_lease_ttl = "768h"
max_lease_ttl = "8760h"

# Logging
log_level = "INFO"
log_format = "json"

# Disable mlock (for containers)
disable_mlock = true

# Plugin directory
plugin_directory = "/vault/plugins"
```

### Vault Initialization and Configuration Script
```bash
#!/bin/bash
# vault-init.sh

set -e

VAULT_ADDR=${VAULT_ADDR:-"https://vault:8200"}
VAULT_SKIP_VERIFY=${VAULT_SKIP_VERIFY:-"false"}

echo "Initializing Vault cluster..."

# Check if Vault is already initialized
if vault status > /dev/null 2>&1; then
  echo "Vault is already initialized"
  exit 0
fi

# Initialize Vault
echo "Initializing Vault with 5 key shares and threshold of 3..."
vault operator init \
  -key-shares=5 \
  -key-threshold=3 \
  -recovery-shares=5 \
  -recovery-threshold=3 \
  -format=json > /vault/data/init-output.json

# Extract root token and unseal keys
ROOT_TOKEN=$(cat /vault/data/init-output.json | jq -r '.root_token')
UNSEAL_KEY_1=$(cat /vault/data/init-output.json | jq -r '.unseal_keys_b64[0]')
UNSEAL_KEY_2=$(cat /vault/data/init-output.json | jq -r '.unseal_keys_b64[1]')
UNSEAL_KEY_3=$(cat /vault/data/init-output.json | jq -r '.unseal_keys_b64[2]')

echo "Vault initialized successfully"
echo "Root token: $ROOT_TOKEN"
echo "Please save the unseal keys securely!"

# Unseal Vault
echo "Unsealing Vault..."
vault operator unseal $UNSEAL_KEY_1
vault operator unseal $UNSEAL_KEY_2
vault operator unseal $UNSEAL_KEY_3

# Authenticate with root token
vault auth $ROOT_TOKEN

echo "Configuring Vault policies and auth methods..."

# Enable audit logging
vault audit enable file file_path=/vault/logs/audit.log

# Enable secret engines
echo "Enabling secret engines..."

# Database secrets engine
vault secrets enable -path=database database

# PKI secrets engine for certificates
vault secrets enable -path=pki pki
vault secrets tune -max-lease-ttl=87600h pki

# PKI intermediate CA
vault secrets enable -path=pki_int pki
vault secrets tune -max-lease-ttl=43800h pki_int

# AWS secrets engine
vault secrets enable -path=aws aws

# Key-Value secrets engine v2
vault secrets enable -path=secret kv-v2

# Enable authentication methods
echo "Enabling authentication methods..."

# JWT/OIDC for service authentication
vault auth enable jwt

# AppRole for applications
vault auth enable approle

# Kubernetes authentication (if running on K8s)
vault auth enable kubernetes

# Configure policies
echo "Creating Vault policies..."

# Database access policy
vault policy write database-policy - << EOF
path "database/creds/user-service-db" {
  capabilities = ["read"]
}
path "database/creds/employee-service-db" {
  capabilities = ["read"]
}
path "database/creds/attendance-service-db" {
  capabilities = ["read"]
}
EOF

# Application secrets policy
vault policy write app-secrets-policy - << EOF
path "secret/data/user-service/*" {
  capabilities = ["read"]
}
path "secret/data/employee-service/*" {
  capabilities = ["read"]
}
path "secret/data/attendance-service/*" {
  capabilities = ["read"]
}
path "secret/data/shared/*" {
  capabilities = ["read"]
}
EOF

# PKI policy for certificate generation
vault policy write pki-policy - << EOF
path "pki_int/issue/hr-microservices" {
  capabilities = ["create", "update"]
}
path "pki_int/sign/hr-microservices" {
  capabilities = ["create", "update"]
}
EOF

echo "Vault configuration completed successfully!"
```

### Database Secrets Engine Configuration
```bash
#!/bin/bash
# configure-database-secrets.sh

set -e

# Configure PostgreSQL connection for User Service
vault write database/config/user-service-db \
  plugin_name=postgresql-database-plugin \
  connection_url="postgresql://{{username}}:{{password}}@user-db:5432/user_service?sslmode=require" \
  allowed_roles="user-service-role" \
  username="vault_admin" \
  password="vault_admin_password"

# Create role for User Service
vault write database/roles/user-service-role \
  db_name=user-service-db \
  creation_statements="CREATE ROLE \"{{name}}\" WITH LOGIN PASSWORD '{{password}}' VALID UNTIL '{{expiration}}'; \
    GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO \"{{name}}\"; \
    GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO \"{{name}}\";" \
  default_ttl="1h" \
  max_ttl="24h"

# Configure PostgreSQL connection for Employee Service
vault write database/config/employee-service-db \
  plugin_name=postgresql-database-plugin \
  connection_url="postgresql://{{username}}:{{password}}@employee-db:5432/employee_service?sslmode=require" \
  allowed_roles="employee-service-role" \
  username="vault_admin" \
  password="vault_admin_password"

# Create role for Employee Service
vault write database/roles/employee-service-role \
  db_name=employee-service-db \
  creation_statements="CREATE ROLE \"{{name}}\" WITH LOGIN PASSWORD '{{password}}' VALID UNTIL '{{expiration}}'; \
    GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO \"{{name}}\"; \
    GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO \"{{name}}\";" \
  default_ttl="1h" \
  max_ttl="24h"

# Configure TimescaleDB connection for Attendance Service
vault write database/config/attendance-service-db \
  plugin_name=postgresql-database-plugin \
  connection_url="postgresql://{{username}}:{{password}}@timescaledb:5432/attendance_service?sslmode=require" \
  allowed_roles="attendance-service-role" \
  username="vault_admin" \
  password="vault_admin_password"

# Create role for Attendance Service
vault write database/roles/attendance-service-role \
  db_name=attendance-service-db \
  creation_statements="CREATE ROLE \"{{name}}\" WITH LOGIN PASSWORD '{{password}}' VALID UNTIL '{{expiration}}'; \
    GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO \"{{name}}\"; \
    GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO \"{{name}}\";" \
  default_ttl="1h" \
  max_ttl="24h"

echo "Database secrets engine configured successfully!"
```

### AppRole Authentication Configuration
```bash
#!/bin/bash
# configure-approle.sh

set -e

# Configure AppRole for User Service
vault write auth/approle/role/user-service \
  token_policies="database-policy,app-secrets-policy" \
  token_ttl=1h \
  token_max_ttl=4h \
  bind_secret_id=true \
  secret_id_num_uses=0 \
  secret_id_ttl=0

# Get Role ID for User Service
USER_SERVICE_ROLE_ID=$(vault read -field=role_id auth/approle/role/user-service/role-id)
echo "User Service Role ID: $USER_SERVICE_ROLE_ID"

# Generate Secret ID for User Service
USER_SERVICE_SECRET_ID=$(vault write -field=secret_id auth/approle/role/user-service/secret-id)
echo "User Service Secret ID: $USER_SERVICE_SECRET_ID"

# Configure AppRole for Employee Service
vault write auth/approle/role/employee-service \
  token_policies="database-policy,app-secrets-policy,pki-policy" \
  token_ttl=1h \
  token_max_ttl=4h \
  bind_secret_id=true \
  secret_id_num_uses=0 \
  secret_id_ttl=0

# Get Role ID for Employee Service
EMPLOYEE_SERVICE_ROLE_ID=$(vault read -field=role_id auth/approle/role/employee-service/role-id)
echo "Employee Service Role ID: $EMPLOYEE_SERVICE_ROLE_ID"

# Generate Secret ID for Employee Service
EMPLOYEE_SERVICE_SECRET_ID=$(vault write -field=secret_id auth/approle/role/employee-service/secret-id)
echo "Employee Service Secret ID: $EMPLOYEE_SERVICE_SECRET_ID"

# Configure AppRole for Attendance Service
vault write auth/approle/role/attendance-service \
  token_policies="database-policy,app-secrets-policy" \
  token_ttl=1h \
  token_max_ttl=4h \
  bind_secret_id=true \
  secret_id_num_uses=0 \
  secret_id_ttl=0

# Get Role ID for Attendance Service
ATTENDANCE_SERVICE_ROLE_ID=$(vault read -field=role_id auth/approle/role/attendance-service/role-id)
echo "Attendance Service Role ID: $ATTENDANCE_SERVICE_ROLE_ID"

# Generate Secret ID for Attendance Service
ATTENDANCE_SERVICE_SECRET_ID=$(vault write -field=secret_id auth/approle/role/attendance-service/secret-id)
echo "Attendance Service Secret ID: $ATTENDANCE_SERVICE_SECRET_ID"

echo "AppRole authentication configured successfully!"
```

## 🐳 Docker Configuration

### Docker Compose for Service Discovery
```yaml
# docker-compose.service-discovery.yml
services:
  # Consul Cluster
  consul-server-1:
    image: consul:1.16.1
    container_name: hr_consul_server_1
    hostname: consul-server-1
    command: [
      "consul", "agent",
      "-config-file=/consul/config/consul-server.hcl",
      "-bootstrap-expect=3",
      "-ui"
    ]
    volumes:
      - ./microservices/infrastructure/service-discovery/consul-server.hcl:/consul/config/consul-server.hcl:ro
      - ./microservices/infrastructure/service-discovery/tls:/consul/tls:ro
      - consul_1_data:/opt/consul/data
      - consul_1_logs:/opt/consul/logs
    ports:
      - "8500:8500"  # HTTP API
      - "8501:8501"  # HTTPS API
      - "8600:8600"  # DNS
      - "8502:8502"  # gRPC
    environment:
      - CONSUL_BIND_INTERFACE=eth0
      - CONSUL_CLIENT_INTERFACE=eth0
    restart: unless-stopped
    networks:
      - microservices-network
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'
        reservations:
          memory: 256M
          cpus: '0.25'
    healthcheck:
      test: ["CMD", "consul", "members"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 60s

  consul-server-2:
    image: consul:1.16.1
    container_name: hr_consul_server_2
    hostname: consul-server-2
    command: [
      "consul", "agent",
      "-config-file=/consul/config/consul-server.hcl",
      "-retry-join=consul-server-1"
    ]
    volumes:
      - ./microservices/infrastructure/service-discovery/consul-server.hcl:/consul/config/consul-server.hcl:ro
      - ./microservices/infrastructure/service-discovery/tls:/consul/tls:ro
      - consul_2_data:/opt/consul/data
      - consul_2_logs:/opt/consul/logs
    environment:
      - CONSUL_BIND_INTERFACE=eth0
      - CONSUL_CLIENT_INTERFACE=eth0
    restart: unless-stopped
    networks:
      - microservices-network
    depends_on:
      - consul-server-1
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'

  consul-server-3:
    image: consul:1.16.1
    container_name: hr_consul_server_3
    hostname: consul-server-3
    command: [
      "consul", "agent",
      "-config-file=/consul/config/consul-server.hcl",
      "-retry-join=consul-server-1"
    ]
    volumes:
      - ./microservices/infrastructure/service-discovery/consul-server.hcl:/consul/config/consul-server.hcl:ro
      - ./microservices/infrastructure/service-discovery/tls:/consul/tls:ro
      - consul_3_data:/opt/consul/data
      - consul_3_logs:/opt/consul/logs
    environment:
      - CONSUL_BIND_INTERFACE=eth0
      - CONSUL_CLIENT_INTERFACE=eth0
    restart: unless-stopped
    networks:
      - microservices-network
    depends_on:
      - consul-server-1
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'

  # Vault Cluster
  vault-server-1:
    image: vault:1.14.2
    container_name: hr_vault_server_1
    hostname: vault-server-1
    command: ["vault", "server", "-config=/vault/config/vault.hcl"]
    volumes:
      - ./microservices/infrastructure/service-discovery/vault.hcl:/vault/config/vault.hcl:ro
      - ./microservices/infrastructure/service-discovery/tls:/vault/tls:ro
      - vault_1_data:/vault/data
      - vault_1_logs:/vault/logs
    ports:
      - "8200:8200"  # HTTP API
      - "8201:8201"  # Cluster
    environment:
      - VAULT_ADDR=https://vault-server-1:8200
      - VAULT_API_ADDR=https://vault-server-1:8200
      - VAULT_CLUSTER_ADDR=https://vault-server-1:8201
      - VAULT_SKIP_VERIFY=false
    cap_add:
      - IPC_LOCK
    restart: unless-stopped
    networks:
      - microservices-network
    depends_on:
      - consul-server-1
      - consul-server-2
      - consul-server-3
    deploy:
      resources:
        limits:
          memory: 1G
          cpus: '1.0'
        reservations:
          memory: 512M
          cpus: '0.5'
    healthcheck:
      test: ["CMD", "vault", "status"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 120s

  vault-server-2:
    image: vault:1.14.2
    container_name: hr_vault_server_2
    hostname: vault-server-2
    command: ["vault", "server", "-config=/vault/config/vault.hcl"]
    volumes:
      - ./microservices/infrastructure/service-discovery/vault.hcl:/vault/config/vault.hcl:ro
      - ./microservices/infrastructure/service-discovery/tls:/vault/tls:ro
      - vault_2_data:/vault/data
      - vault_2_logs:/vault/logs
    environment:
      - VAULT_ADDR=https://vault-server-2:8200
      - VAULT_API_ADDR=https://vault-server-2:8200
      - VAULT_CLUSTER_ADDR=https://vault-server-2:8201
      - VAULT_SKIP_VERIFY=false
    cap_add:
      - IPC_LOCK
    restart: unless-stopped
    networks:
      - microservices-network
    depends_on:
      - vault-server-1
    deploy:
      resources:
        limits:
          memory: 1G
          cpus: '1.0'

  vault-server-3:
    image: vault:1.14.2
    container_name: hr_vault_server_3
    hostname: vault-server-3
    command: ["vault", "server", "-config=/vault/config/vault.hcl"]
    volumes:
      - ./microservices/infrastructure/service-discovery/vault.hcl:/vault/config/vault.hcl:ro
      - ./microservices/infrastructure/service-discovery/tls:/vault/tls:ro
      - vault_3_data:/vault/data
      - vault_3_logs:/vault/logs
    environment:
      - VAULT_ADDR=https://vault-server-3:8200
      - VAULT_API_ADDR=https://vault-server-3:8200
      - VAULT_CLUSTER_ADDR=https://vault-server-3:8201
      - VAULT_SKIP_VERIFY=false
    cap_add:
      - IPC_LOCK
    restart: unless-stopped
    networks:
      - microservices-network
    depends_on:
      - vault-server-1
    deploy:
      resources:
        limits:
          memory: 1G
          cpus: '1.0'

  # Vault Initialization Job
  vault-init:
    image: vault:1.14.2
    container_name: hr_vault_init
    volumes:
      - ./microservices/infrastructure/service-discovery/vault-init.sh:/vault-init.sh:ro
      - vault_1_data:/vault/data
    environment:
      - VAULT_ADDR=https://vault-server-1:8200
      - VAULT_SKIP_VERIFY=false
    command: ["/vault-init.sh"]
    depends_on:
      - vault-server-1
      - vault-server-2
      - vault-server-3
    restart: "no"
    networks:
      - microservices-network

volumes:
  consul_1_data:
  consul_1_logs:
  consul_2_data:
  consul_2_logs:
  consul_3_data:
  consul_3_logs:
  vault_1_data:
  vault_1_logs:
  vault_2_data:
  vault_2_logs:
  vault_3_data:
  vault_3_logs:

networks:
  microservices-network:
    driver: bridge
```

---

**Next**: [Deployment Configurations](../../deployment/README.md) | [Migration Documentation](../../migration/README.md)