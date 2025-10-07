# Observability Stack

## 🎯 Overview

The Observability Stack provides comprehensive monitoring, logging, and tracing capabilities for the HR microservices ecosystem using the three pillars of observability: metrics, logs, and traces.

### 🏗️ Architecture

```mermaid
graph TB
    subgraph "Microservices"
        UserService[User Service]
        EmployeeService[Employee Service]
        AttendanceService[Attendance Service]
        APIGateway[API Gateway]
        Kafka[Kafka Cluster]
        Redis[Redis Cluster]
        Databases[(Databases)]
    end
    
    subgraph "Metrics Collection"
        subgraph "Metrics Exporters"
            PrometheusExporter[Prometheus Exporters]
            NodeExporter[Node Exporter]
            CAdvisor[cAdvisor]
            KafkaExporter[Kafka Exporter]
            RedisExporter[Redis Exporter]
            PostgresExporter[Postgres Exporter]
        end
        
        subgraph "Metrics Storage"
            Prometheus[Prometheus<br/>Time Series DB]
            Thanos[Thanos<br/>Long-term Storage]
        end
    end
    
    subgraph "Logging Pipeline"
        subgraph "Log Collection"
            Filebeat[Filebeat<br/>Log Shipper]
            Fluentd[Fluentd<br/>Log Aggregator]
        end
        
        subgraph "Log Processing"
            Logstash[Logstash<br/>Log Processor]
            Kafka_Logs[Kafka<br/>Log Streaming]
        end
        
        subgraph "Log Storage"
            Elasticsearch[Elasticsearch<br/>Search & Analytics]
            S3[S3<br/>Long-term Archive]
        end
    end
    
    subgraph "Distributed Tracing"
        subgraph "Trace Collection"
            JaegerAgent[Jaeger Agent]
            OTELCollector[OpenTelemetry<br/>Collector]
        end
        
        subgraph "Trace Storage"
            JaegerCollector[Jaeger Collector]
            JaegerQuery[Jaeger Query]
            CassandraDB[(Cassandra<br/>Trace Storage)]
        end
    end
    
    subgraph "Visualization & Alerting"
        Grafana[Grafana<br/>Dashboards]
        Kibana[Kibana<br/>Log Analysis]
        JaegerUI[Jaeger UI<br/>Trace Analysis]
        AlertManager[Alert Manager<br/>Notifications]
        PagerDuty[PagerDuty<br/>Incident Management]
        Slack[Slack<br/>Team Notifications]
    end
    
    subgraph "Security & Access"
        OAuth[OAuth 2.0<br/>Authentication]
        RBAC[Role-Based<br/>Access Control]
        SSO[Single Sign-On]
    end
    
    % Metrics Flow
    UserService --> PrometheusExporter
    EmployeeService --> PrometheusExporter
    AttendanceService --> PrometheusExporter
    APIGateway --> PrometheusExporter
    
    NodeExporter --> Prometheus
    CAdvisor --> Prometheus
    KafkaExporter --> Prometheus
    RedisExporter --> Prometheus
    PostgresExporter --> Prometheus
    PrometheusExporter --> Prometheus
    
    Prometheus --> Thanos
    Prometheus --> Grafana
    Prometheus --> AlertManager
    
    % Logging Flow
    UserService --> Filebeat
    EmployeeService --> Filebeat
    AttendanceService --> Filebeat
    APIGateway --> Filebeat
    
    Filebeat --> Fluentd
    Fluentd --> Logstash
    Logstash --> Kafka_Logs
    Kafka_Logs --> Elasticsearch
    Elasticsearch --> S3
    Elasticsearch --> Kibana
    
    % Tracing Flow
    UserService --> JaegerAgent
    EmployeeService --> JaegerAgent
    AttendanceService --> JaegerAgent
    APIGateway --> JaegerAgent
    
    JaegerAgent --> OTELCollector
    OTELCollector --> JaegerCollector
    JaegerCollector --> CassandraDB
    CassandraDB --> JaegerQuery
    JaegerQuery --> JaegerUI
    
    % Alerting Flow
    AlertManager --> PagerDuty
    AlertManager --> Slack
    
    % Security
    Grafana --> OAuth
    Kibana --> OAuth
    JaegerUI --> OAuth
    OAuth --> SSO
    SSO --> RBAC
    
    classDef service fill:#e3f2fd
    classDef metrics fill:#f3e5f5
    classDef logging fill:#e8f5e8
    classDef tracing fill:#fff3e0
    classDef visualization fill:#fce4ec
    classDef security fill:#f1f8e9
    
    class UserService,EmployeeService,AttendanceService,APIGateway,Kafka,Redis,Databases service
    class PrometheusExporter,NodeExporter,CAdvisor,KafkaExporter,RedisExporter,PostgresExporter,Prometheus,Thanos metrics
    class Filebeat,Fluentd,Logstash,Kafka_Logs,Elasticsearch,S3 logging
    class JaegerAgent,OTELCollector,JaegerCollector,JaegerQuery,CassandraDB tracing
    class Grafana,Kibana,JaegerUI,AlertManager,PagerDuty,Slack visualization
    class OAuth,RBAC,SSO security
```

## 📊 Prometheus Metrics Collection

### Prometheus Configuration
```yaml
# prometheus.yml
global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    cluster: 'hr-microservices'
    environment: 'production'
    region: 'us-east-1'

rule_files:
  - "/etc/prometheus/rules/*.yml"

alerting:
  alertmanagers:
    - static_configs:
        - targets:
          - alertmanager:9093

scrape_configs:
  # Prometheus itself
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']
    scrape_interval: 30s
    metrics_path: '/metrics'

  # Node Exporter (System Metrics)
  - job_name: 'node-exporter'
    static_configs:
      - targets: 
        - 'node-exporter:9100'
    scrape_interval: 30s
    relabel_configs:
      - source_labels: [__address__]
        target_label: instance
        regex: '([^:]+)(:[0-9]+)?'
        replacement: '${1}'

  # cAdvisor (Container Metrics)
  - job_name: 'cadvisor'
    static_configs:
      - targets:
        - 'cadvisor:8080'
    scrape_interval: 30s
    metrics_path: '/metrics'

  # API Gateway (Kong)
  - job_name: 'api-gateway'
    static_configs:
      - targets:
        - 'kong-dp-1:8001'
        - 'kong-dp-2:8001'
    scrape_interval: 15s
    metrics_path: '/metrics'
    relabel_configs:
      - source_labels: [__address__]
        target_label: gateway_instance
        regex: '([^:]+):.*'
        replacement: '${1}'

  # User Service
  - job_name: 'user-service'
    static_configs:
      - targets:
        - 'user-service:3001'
    scrape_interval: 15s
    metrics_path: '/metrics'
    scrape_timeout: 10s
    relabel_configs:
      - target_label: service_name
        replacement: 'user-service'

  # Employee Service
  - job_name: 'employee-service'
    static_configs:
      - targets:
        - 'employee-service:3002'
    scrape_interval: 15s
    metrics_path: '/metrics'
    scrape_timeout: 10s
    relabel_configs:
      - target_label: service_name
        replacement: 'employee-service'

  # Attendance Service
  - job_name: 'attendance-service'
    static_configs:
      - targets:
        - 'attendance-service:3003'
    scrape_interval: 15s
    metrics_path: '/metrics'
    scrape_timeout: 10s
    relabel_configs:
      - target_label: service_name
        replacement: 'attendance-service'

  # PostgreSQL Databases
  - job_name: 'postgres-exporter'
    static_configs:
      - targets:
        - 'postgres-exporter-user:9187'
        - 'postgres-exporter-employee:9187'
        - 'postgres-exporter-attendance:9187'
    scrape_interval: 30s
    relabel_configs:
      - source_labels: [__address__]
        target_label: database_instance
        regex: 'postgres-exporter-([^:]+):.*'
        replacement: '${1}'

  # Redis Cluster
  - job_name: 'redis-exporter'
    static_configs:
      - targets:
        - 'redis-exporter:9121'
    scrape_interval: 30s
    relabel_configs:
      - target_label: service_name
        replacement: 'redis-cluster'

  # Kafka Cluster
  - job_name: 'kafka-exporter'
    static_configs:
      - targets:
        - 'kafka-exporter:9308'
    scrape_interval: 30s
    relabel_configs:
      - target_label: service_name
        replacement: 'kafka-cluster'

  # Elasticsearch
  - job_name: 'elasticsearch-exporter'
    static_configs:
      - targets:
        - 'elasticsearch-exporter:9114'
    scrape_interval: 30s
    relabel_configs:
      - target_label: service_name
        replacement: 'elasticsearch'

  # Service Discovery (Consul)
  - job_name: 'consul'
    consul_sd_configs:
      - server: 'consul:8500'
        services: []
    relabel_configs:
      - source_labels: [__meta_consul_service]
        target_label: service_name
      - source_labels: [__meta_consul_service_id]
        target_label: service_instance
      - source_labels: [__meta_consul_node]
        target_label: node_name
      - source_labels: [__meta_consul_datacenter]
        target_label: datacenter

# Storage Configuration
storage:
  tsdb:
    path: /prometheus/data
    retention.time: 30d
    retention.size: 50GB
    wal-compression: true

# Remote Write (to Thanos)
remote_write:
  - url: "http://thanos-receive:19291/api/v1/receive"
    queue_config:
      max_samples_per_send: 10000
      max_shards: 200
      capacity: 2500
```

### Alert Rules Configuration
```yaml
# /etc/prometheus/rules/microservices-alerts.yml
groups:
  - name: microservices.alerts
    rules:
      # High Error Rate
      - alert: HighErrorRate
        expr: rate(http_requests_total{status=~"5.."}[5m]) / rate(http_requests_total[5m]) > 0.1
        for: 5m
        labels:
          severity: critical
          team: platform
        annotations:
          summary: "High error rate detected for {{ $labels.service_name }}"
          description: "Service {{ $labels.service_name }} has error rate of {{ $value | humanizePercentage }} for the last 5 minutes"
          runbook_url: "https://runbooks.company.com/high-error-rate"

      # High Response Time
      - alert: HighResponseTime
        expr: histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m])) > 1
        for: 10m
        labels:
          severity: warning
          team: platform
        annotations:
          summary: "High response time detected for {{ $labels.service_name }}"
          description: "95th percentile response time is {{ $value }}s for service {{ $labels.service_name }}"

      # Service Down
      - alert: ServiceDown
        expr: up == 0
        for: 2m
        labels:
          severity: critical
          team: platform
        annotations:
          summary: "Service {{ $labels.job }} is down"
          description: "Service {{ $labels.job }} has been down for more than 2 minutes"
          runbook_url: "https://runbooks.company.com/service-down"

      # High CPU Usage
      - alert: HighCPUUsage
        expr: 100 - (avg by(instance) (irate(node_cpu_seconds_total{mode="idle"}[5m])) * 100) > 80
        for: 10m
        labels:
          severity: warning
          team: infrastructure
        annotations:
          summary: "High CPU usage on {{ $labels.instance }}"
          description: "CPU usage is {{ $value }}% on instance {{ $labels.instance }}"

      # High Memory Usage
      - alert: HighMemoryUsage
        expr: (1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes)) * 100 > 85
        for: 10m
        labels:
          severity: warning
          team: infrastructure
        annotations:
          summary: "High memory usage on {{ $labels.instance }}"
          description: "Memory usage is {{ $value }}% on instance {{ $labels.instance }}"

      # Database Connection Pool Exhaustion
      - alert: DatabaseConnectionPoolExhaustion
        expr: db_connections_active / db_connections_max > 0.9
        for: 5m
        labels:
          severity: critical
          team: database
        annotations:
          summary: "Database connection pool nearly exhausted"
          description: "Database {{ $labels.database }} has {{ $value | humanizePercentage }} connection pool utilization"

      # Kafka Consumer Lag
      - alert: KafkaConsumerLag
        expr: kafka_consumer_lag_sum > 1000
        for: 10m
        labels:
          severity: warning
          team: platform
        annotations:
          summary: "High Kafka consumer lag"
          description: "Consumer group {{ $labels.consumer_group }} has lag of {{ $value }} messages"

      # Redis High Memory Usage
      - alert: RedisHighMemoryUsage
        expr: redis_memory_used_bytes / redis_memory_max_bytes > 0.9
        for: 5m
        labels:
          severity: warning
          team: platform
        annotations:
          summary: "Redis high memory usage"
          description: "Redis instance {{ $labels.instance }} memory usage is {{ $value | humanizePercentage }}"

  - name: business.alerts
    rules:
      # Attendance System Alerts
      - alert: HighAttendanceProcessingDelay
        expr: attendance_processing_delay_seconds > 300
        for: 5m
        labels:
          severity: warning
          team: hr-platform
        annotations:
          summary: "High attendance processing delay"
          description: "Attendance processing delay is {{ $value }}s, which may affect payroll calculations"

      # Authentication Failures
      - alert: HighAuthenticationFailures
        expr: rate(auth_failures_total[5m]) > 10
        for: 5m
        labels:
          severity: warning
          team: security
        annotations:
          summary: "High authentication failure rate"
          description: "Authentication failure rate is {{ $value }} failures/second"

      # Employee Service Sync Issues
      - alert: EmployeeDataSyncFailure
        expr: employee_sync_failures_total > 0
        for: 1m
        labels:
          severity: critical
          team: hr-platform
        annotations:
          summary: "Employee data synchronization failure"
          description: "Employee service failed to sync data with external systems"
```

## 📝 Centralized Logging with ELK Stack

### Elasticsearch Configuration
```yaml
# elasticsearch.yml
cluster.name: hr-microservices-logs
node.name: elasticsearch-node-1
node.roles: [ master, data, ingest ]

# Network configuration
network.host: 0.0.0.0
http.port: 9200
transport.port: 9300

# Discovery configuration
discovery.seed_hosts: ["elasticsearch-node-2", "elasticsearch-node-3"]
cluster.initial_master_nodes: ["elasticsearch-node-1", "elasticsearch-node-2", "elasticsearch-node-3"]

# Memory configuration
bootstrap.memory_lock: true

# Security configuration
xpack.security.enabled: true
xpack.security.transport.ssl.enabled: true
xpack.security.transport.ssl.verification_mode: certificate
xpack.security.transport.ssl.client_authentication: required
xpack.security.transport.ssl.keystore.path: elastic-certificates.p12
xpack.security.transport.ssl.truststore.path: elastic-certificates.p12

xpack.security.http.ssl.enabled: true
xpack.security.http.ssl.keystore.path: elastic-certificates.p12

# Monitoring
xpack.monitoring.collection.enabled: true

# Index lifecycle management
xpack.ilm.enabled: true

# Index templates for microservices logs
index_patterns:
  - "microservices-logs-*"
template:
  settings:
    number_of_shards: 3
    number_of_replicas: 1
    index.lifecycle.name: "microservices-logs-policy"
    index.lifecycle.rollover_alias: "microservices-logs"
  mappings:
    properties:
      "@timestamp":
        type: date
      level:
        type: keyword
      service:
        type: keyword
      message:
        type: text
        analyzer: standard
      request_id:
        type: keyword
      user_id:
        type: keyword
      method:
        type: keyword
      url:
        type: keyword
      status_code:
        type: integer
      response_time:
        type: float
      error:
        properties:
          message:
            type: text
          stack:
            type: text
          code:
            type: keyword
```

### Logstash Configuration
```ruby
# logstash.conf
input {
  beats {
    port => 5044
  }
  
  kafka {
    bootstrap_servers => "kafka1:9092,kafka2:9092,kafka3:9092"
    topics => ["microservices-logs"]
    group_id => "logstash-consumer-group"
    consumer_threads => 3
    codec => json
  }
}

filter {
  # Parse timestamp
  date {
    match => [ "timestamp", "ISO8601" ]
    target => "@timestamp"
  }
  
  # Extract service name from source
  if [source] {
    grok {
      match => { "source" => "/var/log/(?<service>[^/]+)/" }
    }
  }
  
  # Parse JSON logs
  if [message] =~ /^\{.*\}$/ {
    json {
      source => "message"
    }
  }
  
  # Grok patterns for different log formats
  if [service] == "nginx" or [service] == "kong" {
    grok {
      match => { 
        "message" => "%{COMBINEDAPACHELOG} %{NUMBER:response_time:float}" 
      }
    }
  }
  
  # Laravel application logs
  if [service] =~ /(user|employee|attendance)-service/ {
    grok {
      match => { 
        "message" => "\[%{TIMESTAMP_ISO8601:timestamp}\] %{WORD:environment}\.%{WORD:level}: %{GREEDYDATA:log_message}" 
      }
    }
    
    # Parse JSON context if present
    if [log_message] =~ /\{.*\}/ {
      json {
        source => "log_message"
        target => "context"
      }
    }
  }
  
  # API Gateway logs
  if [service] == "api-gateway" {
    json {
      source => "message"
    }
    
    mutate {
      rename => { "request" => "http_request" }
      rename => { "response" => "http_response" }
      rename => { "latencies" => "timing" }
    }
  }
  
  # Add geographic information based on client IP
  if [client_ip] {
    geoip {
      source => "client_ip"
      target => "geoip"
    }
  }
  
  # Enrich with service metadata
  mutate {
    add_field => { 
      "environment" => "production"
      "cluster" => "hr-microservices"
      "region" => "us-east-1"
    }
  }
  
  # Security: Remove sensitive data
  mutate {
    remove_field => [ "password", "token", "secret", "authorization" ]
  }
}

output {
  # Send to Elasticsearch
  elasticsearch {
    hosts => ["elasticsearch-node-1:9200", "elasticsearch-node-2:9200", "elasticsearch-node-3:9200"]
    index => "microservices-logs-%{+YYYY.MM.dd}"
    
    # Authentication
    user => "logstash_writer"
    password => "${LOGSTASH_ES_PASSWORD}"
    
    # SSL
    ssl => true
    ssl_certificate_verification => true
    cacert => "/etc/logstash/certs/ca.crt"
    
    # Template management
    manage_template => true
    template_name => "microservices-logs"
    template_pattern => "microservices-logs-*"
    template_overwrite => true
  }
  
  # Send critical errors to separate index for alerting
  if [level] == "ERROR" or [level] == "FATAL" {
    elasticsearch {
      hosts => ["elasticsearch-node-1:9200", "elasticsearch-node-2:9200", "elasticsearch-node-3:9200"]
      index => "critical-errors-%{+YYYY.MM.dd}"
      user => "logstash_writer"
      password => "${LOGSTASH_ES_PASSWORD}"
      ssl => true
      ssl_certificate_verification => true
      cacert => "/etc/logstash/certs/ca.crt"
    }
  }
  
  # Debug output (only in development)
  if [environment] == "development" {
    stdout { 
      codec => rubydebug 
    }
  }
}
```

### Filebeat Configuration
```yaml
# filebeat.yml
filebeat.inputs:
  # Microservices application logs
  - type: log
    enabled: true
    paths:
      - /var/log/microservices/user-service/*.log
      - /var/log/microservices/employee-service/*.log
      - /var/log/microservices/attendance-service/*.log
    fields:
      service_type: microservice
      environment: production
    fields_under_root: true
    multiline.pattern: '^\['
    multiline.negate: true
    multiline.match: after
    
  # API Gateway logs
  - type: log
    enabled: true
    paths:
      - /var/log/kong/*.log
    fields:
      service: api-gateway
      service_type: gateway
    fields_under_root: true
    json.keys_under_root: true
    json.add_error_key: true
    
  # System logs
  - type: log
    enabled: true
    paths:
      - /var/log/syslog
      - /var/log/auth.log
    fields:
      service_type: system
    fields_under_root: true
    
  # Docker container logs
  - type: container
    enabled: true
    paths:
      - /var/lib/docker/containers/*/*.log
    processors:
      - add_docker_metadata:
          host: "unix:///var/run/docker.sock"

processors:
  # Add host metadata
  - add_host_metadata:
      when.not.contains.tags: forwarded
      
  # Add Docker metadata
  - add_docker_metadata: ~
  
  # Add Kubernetes metadata (if running on K8s)
  - add_kubernetes_metadata: ~
  
  # Drop debug logs in production
  - drop_event:
      when:
        and:
          - equals:
              environment: production
          - equals:
              level: DEBUG

output.kafka:
  hosts: ["kafka1:9092", "kafka2:9092", "kafka3:9092"]
  topic: "microservices-logs"
  partition.round_robin:
    reachable_only: false
  compression: gzip
  max_message_bytes: 1000000
  
  # Kafka authentication (if enabled)
  # sasl.mechanism: PLAIN
  # sasl.username: filebeat
  # sasl.password: filebeat_password

# Alternative direct output to Logstash
# output.logstash:
#   hosts: ["logstash:5044"]

logging.level: info
logging.to_files: true
logging.files:
  path: /var/log/filebeat
  name: filebeat
  keepfiles: 7
  permissions: 0644

# Monitoring
monitoring.enabled: true
monitoring.elasticsearch:
  hosts: ["elasticsearch-node-1:9200", "elasticsearch-node-2:9200"]
  username: "monitoring_user"
  password: "${MONITORING_ES_PASSWORD}"
```

## 🔍 Distributed Tracing with Jaeger

### Jaeger Configuration
```yaml
# jaeger-production.yml
apiVersion: jaegertracing.io/v1
kind: Jaeger
metadata:
  name: hr-microservices-jaeger
  namespace: observability
spec:
  strategy: production
  
  collector:
    replicas: 3
    maxReplicas: 10
    resources:
      limits:
        cpu: 1000m
        memory: 1Gi
      requests:
        cpu: 500m
        memory: 512Mi
    options:
      kafka:
        producer:
          topic: jaeger-spans
          brokers: kafka1:9092,kafka2:9092,kafka3:9092
          batch-size: 1000000
          batch-timeout: 5s
        encoding: protobuf
      collector:
        zipkin:
          host-port: :9411
        otlp:
          grpc:
            host-port: :14250
          http:
            host-port: :14268
  
  ingester:
    replicas: 3
    options:
      kafka:
        consumer:
          topic: jaeger-spans
          brokers: kafka1:9092,kafka2:9092,kafka3:9092
          group-id: jaeger-ingester
        encoding: protobuf
      ingester:
        parallelism: 1000
        deadlock-interval: 5s
  
  storage:
    type: cassandra
    cassandra:
      servers: cassandra-node-1,cassandra-node-2,cassandra-node-3
      keyspace: jaeger_v1_production
      local_dc: datacenter1
      connection_timeout: 30s
      socket_keep_alive: true
      max_retry: 3
      proto_version: 4
      consistency: LOCAL_QUORUM
      disable_compression: false
      
  query:
    replicas: 2
    resources:
      limits:
        cpu: 500m
        memory: 512Mi
      requests:
        cpu: 250m
        memory: 256Mi
    options:
      query:
        base-path: /jaeger
        static-files: /go/jaeger-ui/
        ui-config: /etc/jaeger/ui-config.json
        
  agent:
    strategy: DaemonSet
    options:
      agent:
        zipkin.thrift.host-port: :5775
        jaeger.thrift.host-port: :6831
        jaeger.thrift.http-port: :14271
        config-rest.host-port: :5778
        processor:
          jaeger-binary:
            server-max-packet-size: 65000
            server-host-port: :6832
            server-queue-size: 1000
            workers: 10
```

### OpenTelemetry Collector Configuration
```yaml
# otel-collector.yml
receivers:
  otlp:
    protocols:
      grpc:
        endpoint: 0.0.0.0:4317
      http:
        endpoint: 0.0.0.0:4318
        
  jaeger:
    protocols:
      grpc:
        endpoint: 0.0.0.0:14250
      thrift_http:
        endpoint: 0.0.0.0:14268
      thrift_compact:
        endpoint: 0.0.0.0:6831
      thrift_binary:
        endpoint: 0.0.0.0:6832
        
  zipkin:
    endpoint: 0.0.0.0:9411
    
  prometheus:
    config:
      scrape_configs:
        - job_name: 'otel-collector'
          scrape_interval: 30s
          static_configs:
            - targets: ['localhost:8888']

processors:
  batch:
    send_batch_size: 1024
    timeout: 1s
    send_batch_max_size: 2048
    
  memory_limiter:
    check_interval: 1s
    limit_mib: 512
    
  resource:
    attributes:
      - key: environment
        value: production
        action: upsert
      - key: cluster
        value: hr-microservices
        action: upsert
        
  # Sampling processor
  probabilistic_sampler:
    sampling_percentage: 10.0
    
  # Span processor for filtering
  span:
    name:
      # Drop health check spans
      exclude:
        match_type: regexp
        regexp: ".*health.*"

exporters:
  jaeger:
    endpoint: jaeger-collector:14250
    tls:
      insecure: true
      
  kafka:
    brokers: [kafka1:9092, kafka2:9092, kafka3:9092]
    topic: jaeger-spans
    encoding: otlp_proto
    producer:
      max_message_bytes: 10000000
      compression: gzip
      
  prometheus:
    endpoint: "0.0.0.0:8889"
    
  logging:
    loglevel: debug

service:
  pipelines:
    traces:
      receivers: [otlp, jaeger, zipkin]
      processors: [memory_limiter, resource, batch, probabilistic_sampler, span]
      exporters: [jaeger, kafka]
      
    metrics:
      receivers: [otlp, prometheus]
      processors: [memory_limiter, resource, batch]
      exporters: [prometheus]
      
    logs:
      receivers: [otlp]
      processors: [memory_limiter, resource, batch]
      exporters: [logging]

  extensions: [health_check, pprof, zpages]
  
  telemetry:
    logs:
      level: info
    metrics:
      address: 0.0.0.0:8888
```

## 📈 Grafana Dashboards

### Main Microservices Dashboard
```json
{
  "dashboard": {
    "id": null,
    "title": "HR Microservices - Overview",
    "tags": ["microservices", "hr", "overview"],
    "style": "dark",
    "timezone": "browser",
    "refresh": "30s",
    "time": {
      "from": "now-1h",
      "to": "now"
    },
    "panels": [
      {
        "id": 1,
        "title": "Service Health Status",
        "type": "stat",
        "targets": [
          {
            "expr": "up{job=~\".*-service\"}"
          }
        ],
        "fieldConfig": {
          "defaults": {
            "color": {
              "mode": "thresholds"
            },
            "thresholds": {
              "steps": [
                {"color": "red", "value": 0},
                {"color": "green", "value": 1}
              ]
            },
            "mappings": [
              {"options": {"0": {"text": "DOWN"}}, "type": "value"},
              {"options": {"1": {"text": "UP"}}, "type": "value"}
            ]
          }
        },
        "gridPos": {"h": 8, "w": 12, "x": 0, "y": 0}
      },
      {
        "id": 2,
        "title": "Request Rate (RPS)",
        "type": "graph",
        "targets": [
          {
            "expr": "sum(rate(http_requests_total[5m])) by (service_name)",
            "legendFormat": "{{service_name}}"
          }
        ],
        "yAxes": [
          {"label": "Requests/sec", "min": 0}
        ],
        "gridPos": {"h": 8, "w": 12, "x": 12, "y": 0}
      },
      {
        "id": 3,
        "title": "Response Time (95th percentile)",
        "type": "graph",
        "targets": [
          {
            "expr": "histogram_quantile(0.95, sum(rate(http_request_duration_seconds_bucket[5m])) by (service_name, le))",
            "legendFormat": "{{service_name}}"
          }
        ],
        "yAxes": [
          {"label": "Seconds", "min": 0}
        ],
        "gridPos": {"h": 8, "w": 12, "x": 0, "y": 8}
      },
      {
        "id": 4,
        "title": "Error Rate",
        "type": "graph",
        "targets": [
          {
            "expr": "sum(rate(http_requests_total{status=~\"5..\"}[5m])) by (service_name) / sum(rate(http_requests_total[5m])) by (service_name)",
            "legendFormat": "{{service_name}}"
          }
        ],
        "yAxes": [
          {"label": "Error Rate", "min": 0, "max": 1}
        ],
        "gridPos": {"h": 8, "w": 12, "x": 12, "y": 8}
      },
      {
        "id": 5,
        "title": "Database Connections",
        "type": "graph",
        "targets": [
          {
            "expr": "db_connections_active",
            "legendFormat": "Active - {{database}}"
          },
          {
            "expr": "db_connections_idle",
            "legendFormat": "Idle - {{database}}"
          }
        ],
        "gridPos": {"h": 8, "w": 12, "x": 0, "y": 16}
      },
      {
        "id": 6,
        "title": "Kafka Consumer Lag",
        "type": "graph",
        "targets": [
          {
            "expr": "kafka_consumer_lag_sum",
            "legendFormat": "{{consumer_group}} - {{topic}}"
          }
        ],
        "gridPos": {"h": 8, "w": 12, "x": 12, "y": 16}
      }
    ]
  }
}
```

---

**Next**: [Service Discovery & Configuration](../service-discovery/README.md) | [Deployment Configurations](../../deployment/README.md)