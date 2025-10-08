# 👨‍💻 Developer Guide
## Technical Reference for ERP Integration System

**For developers and technical implementers**

---

## 🏗️ Architecture Deep Dive

### Core Components

**1. Spring Boot Application** (`microservices/integration-service/`)
```java
@SpringBootApplication
@EnableCamel
@EnableOAuth2ResourceServer
@EnableScheduling
public class IntegrationApplication {
    public static void main(String[] args) {
        SpringApplication.run(IntegrationApplication.class, args);
    }
}
```

**2. Apache Camel Routes** (`src/main/java/routes/`)
```java
@Component
public class ERPIntegrationRoute extends RouteBuilder {
    
    @Override
    public void configure() throws Exception {
        
        // Employee synchronization route
        from("direct:sync-employee")
            .routeId("employee-sync")
            .log("Syncing employee: ${body.employeeId}")
            .to("bean:employeeValidator")
            .choice()
                .when(header("ERP_SYSTEM").isEqualTo("FRAPPE"))
                    .to("direct:frappe-employee")
                .when(header("ERP_SYSTEM").isEqualTo("SAP"))
                    .to("direct:sap-employee")
                .when(header("ERP_SYSTEM").isEqualTo("ORACLE"))
                    .to("direct:oracle-employee")
                .when(header("ERP_SYSTEM").isEqualTo("DYNAMICS"))
                    .to("direct:dynamics-employee")
                .otherwise()
                    .throwException(new IllegalArgumentException("Unknown ERP system"))
            .end()
            .to("direct:audit-log");
            
        // Payroll processing route
        from("timer:payroll?period=3600000") // Every hour
            .routeId("payroll-processor")
            .to("bean:payrollService?method=processScheduledPayroll")
            .split(body())
            .to("direct:process-payroll-entry")
            .end();
            
        // Error handling route
        onException(Exception.class)
            .handled(true)
            .log(LoggingLevel.ERROR, "Error processing: ${exception.message}")
            .to("direct:error-handler");
    }
}
```

**3. Service Layer** (`src/main/java/services/`)
```java
@Service
@Transactional
public class EmployeeIntegrationService {
    
    @Autowired
    private EmployeeRepository employeeRepository;
    
    @Autowired
    private ERPClientFactory erpClientFactory;
    
    public IntegrationResult syncEmployee(Employee employee, String erpSystem) {
        try {
            ERPClient client = erpClientFactory.getClient(erpSystem);
            ERPEmployee erpEmployee = mapToERPEmployee(employee);
            
            String erpId = client.createOrUpdateEmployee(erpEmployee);
            
            employee.setErpId(erpId);
            employee.setLastSyncDate(LocalDateTime.now());
            employeeRepository.save(employee);
            
            return IntegrationResult.success(erpId);
            
        } catch (Exception e) {
            log.error("Failed to sync employee {}: {}", employee.getId(), e.getMessage());
            return IntegrationResult.failure(e.getMessage());
        }
    }
}
```

### Database Schema

**1. Core Tables**
```sql
-- Employee integration tracking
CREATE TABLE employees (
    id BIGSERIAL PRIMARY KEY,
    hr_system_id VARCHAR(50) NOT NULL,
    erp_id VARCHAR(50),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    employee_number VARCHAR(50),
    department VARCHAR(100),
    position VARCHAR(100),
    salary DECIMAL(15,2),
    hire_date DATE,
    status VARCHAR(20) DEFAULT 'ACTIVE',
    last_sync_date TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Integration status tracking
CREATE TABLE integration_status (
    id BIGSERIAL PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL, -- EMPLOYEE, PAYROLL, JOURNAL
    entity_id VARCHAR(50) NOT NULL,
    erp_system VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL, -- PENDING, SUCCESS, FAILED
    error_message TEXT,
    retry_count INTEGER DEFAULT 0,
    last_attempt TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Audit logging
CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id VARCHAR(50),
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id VARCHAR(50),
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Error tracking
CREATE TABLE error_logs (
    id BIGSERIAL PRIMARY KEY,
    error_code VARCHAR(50),
    error_message TEXT NOT NULL,
    stack_trace TEXT,
    request_data JSONB,
    response_data JSONB,
    severity VARCHAR(20) DEFAULT 'ERROR',
    resolved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**2. Indexes for Performance**
```sql
-- Performance indexes
CREATE INDEX idx_employees_erp_id ON employees(erp_id);
CREATE INDEX idx_employees_email ON employees(email);
CREATE INDEX idx_employees_last_sync ON employees(last_sync_date);
CREATE INDEX idx_integration_status_entity ON integration_status(entity_type, entity_id);
CREATE INDEX idx_integration_status_erp ON integration_status(erp_system, status);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at);
CREATE INDEX idx_error_logs_created ON error_logs(created_at);
CREATE INDEX idx_error_logs_resolved ON error_logs(resolved);
```

---

## 🔧 Development Environment

### Prerequisites Setup
```bash
# Java Development Kit 17+
java -version

# Maven 3.8+
mvn -version

# Docker & Docker Compose
docker --version
docker-compose --version

# Git
git --version
```

### IDE Configuration

**VS Code Extensions** (`.vscode/extensions.json`):
```json
{
    "recommendations": [
        "redhat.java",
        "vscjava.vscode-java-pack",
        "vmware.vscode-spring-boot",
        "ms-vscode.vscode-docker",
        "humao.rest-client",
        "ms-vscode.powershell"
    ]
}
```

**IntelliJ IDEA Configuration**:
```xml
<!-- .idea/runConfigurations/Integration_Service.xml -->
<component name="ProjectRunConfigurationManager">
  <configuration default="false" name="Integration Service" type="SpringBootApplicationConfigurationType">
    <option name="SPRING_BOOT_MAIN_CLASS" value="com.erp.integration.IntegrationApplication" />
    <option name="ALTERNATIVE_JRE_PATH" />
    <option name="SHORTEN_COMMAND_LINE" value="NONE" />
    <option name="ENABLE_DEBUG_MODE" value="false" />
    <envs>
      <env name="SPRING_PROFILES_ACTIVE" value="development" />
    </envs>
    <method v="2">
      <option name="Make" enabled="true" />
    </method>
  </configuration>
</component>
```

### Local Development Setup

**1. Environment Configuration** (`.env.development`):
```bash
# Database
DATABASE_URL=jdbc:postgresql://localhost:5433/erp_integration
DATABASE_USERNAME=erp_user
DATABASE_PASSWORD=dev_password

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379

# RabbitMQ
RABBITMQ_HOST=localhost
RABBITMQ_PORT=5672
RABBITMQ_USERNAME=guest
RABBITMQ_PASSWORD=guest

# OAuth2
OAUTH2_CLIENT_ID=asoath
OAUTH2_CLIENT_SECRET=dev_secret
JWT_PRIVATE_KEY_PATH=microservices/oauth2/keys/jwt-private-pkcs8.pem
JWT_PUBLIC_KEY_PATH=microservices/oauth2/keys/jwt-public.pem

# ERP Systems
FRAPPE_URL=http://localhost:8000
FRAPPE_API_KEY=dev_api_key
FRAPPE_API_SECRET=dev_api_secret

SAP_URL=http://localhost:8001
SAP_CLIENT_ID=dev_client
SAP_USERNAME=dev_user
SAP_PASSWORD=dev_password

# Logging
LOG_LEVEL=DEBUG
LOG_FILE=logs/integration-service.log
```

**2. Development Database Setup**:
```bash
# Start development databases
docker run -d --name dev-postgres \
  -e POSTGRES_DB=erp_integration \
  -e POSTGRES_USER=erp_user \
  -e POSTGRES_PASSWORD=dev_password \
  -p 5433:5432 \
  postgres:15

# Run database migrations
./mvnw flyway:migrate -Dflyway.profiles=development
```

**3. Development Server**:
```bash
# Start with hot reload
./mvnw spring-boot:run -Dspring.profiles.active=development

# Or with IDE
# Run IntegrationApplication.java with development profile
```

---

## 🧪 Testing Framework

### Unit Testing

**1. Service Layer Tests** (`src/test/java/services/`):
```java
@ExtendWith(MockitoExtension.class)
class EmployeeIntegrationServiceTest {
    
    @Mock
    private EmployeeRepository employeeRepository;
    
    @Mock
    private ERPClientFactory erpClientFactory;
    
    @Mock
    private ERPClient erpClient;
    
    @InjectMocks
    private EmployeeIntegrationService service;
    
    @Test
    @DisplayName("Should successfully sync employee to ERP system")
    void shouldSyncEmployeeSuccessfully() {
        // Given
        Employee employee = createTestEmployee();
        when(erpClientFactory.getClient("FRAPPE")).thenReturn(erpClient);
        when(erpClient.createOrUpdateEmployee(any())).thenReturn("ERP123");
        
        // When
        IntegrationResult result = service.syncEmployee(employee, "FRAPPE");
        
        // Then
        assertThat(result.isSuccess()).isTrue();
        assertThat(result.getErpId()).isEqualTo("ERP123");
        verify(employeeRepository).save(employee);
    }
    
    @Test
    @DisplayName("Should handle ERP client failure gracefully")
    void shouldHandleERPClientFailure() {
        // Given
        Employee employee = createTestEmployee();
        when(erpClientFactory.getClient("FRAPPE")).thenReturn(erpClient);
        when(erpClient.createOrUpdateEmployee(any()))
            .thenThrow(new ERPClientException("Connection failed"));
        
        // When
        IntegrationResult result = service.syncEmployee(employee, "FRAPPE");
        
        // Then
        assertThat(result.isSuccess()).isFalse();
        assertThat(result.getErrorMessage()).contains("Connection failed");
    }
    
    private Employee createTestEmployee() {
        return Employee.builder()
            .hrSystemId("HR123")
            .firstName("John")
            .lastName("Doe")
            .email("john.doe@company.com")
            .employeeNumber("EMP001")
            .department("Engineering")
            .position("Software Developer")
            .salary(BigDecimal.valueOf(75000))
            .hireDate(LocalDate.now())
            .status(EmployeeStatus.ACTIVE)
            .build();
    }
}
```

**2. Repository Tests** (`src/test/java/repositories/`):
```java
@DataJpaTest
@AutoConfigureTestDatabase(replace = AutoConfigureTestDatabase.Replace.NONE)
@Testcontainers
class EmployeeRepositoryTest {
    
    @Container
    static PostgreSQLContainer<?> postgres = new PostgreSQLContainer<>("postgres:15")
            .withDatabaseName("test_db")
            .withUsername("test_user")
            .withPassword("test_password");
    
    @Autowired
    private TestEntityManager entityManager;
    
    @Autowired
    private EmployeeRepository employeeRepository;
    
    @Test
    @DisplayName("Should find employees by ERP ID")
    void shouldFindEmployeesByErpId() {
        // Given
        Employee employee = createTestEmployee();
        employee.setErpId("ERP123");
        entityManager.persistAndFlush(employee);
        
        // When
        Optional<Employee> found = employeeRepository.findByErpId("ERP123");
        
        // Then
        assertThat(found).isPresent();
        assertThat(found.get().getErpId()).isEqualTo("ERP123");
    }
    
    @Test
    @DisplayName("Should find employees needing sync")
    void shouldFindEmployeesNeedingSync() {
        // Given
        Employee syncedEmployee = createTestEmployee();
        syncedEmployee.setLastSyncDate(LocalDateTime.now().minusHours(1));
        entityManager.persistAndFlush(syncedEmployee);
        
        Employee unsyncedEmployee = createTestEmployee();
        unsyncedEmployee.setEmail("different@email.com");
        // No sync date set
        entityManager.persistAndFlush(unsyncedEmployee);
        
        // When
        List<Employee> needingSync = employeeRepository.findEmployeesNeedingSync(
            LocalDateTime.now().minusHours(2)
        );
        
        // Then
        assertThat(needingSync).hasSize(1);
        assertThat(needingSync.get(0).getEmail()).isEqualTo("different@email.com");
    }
}
```

### Integration Testing

**1. API Integration Tests** (`src/test/java/integration/`):
```java
@SpringBootTest(webEnvironment = SpringBootTest.WebEnvironment.RANDOM_PORT)
@Testcontainers
@DirtiesContext
class EmployeeIntegrationControllerIT {
    
    @Container
    static PostgreSQLContainer<?> postgres = new PostgreSQLContainer<>("postgres:15");
    
    @Container
    static GenericContainer<?> redis = new GenericContainer<>("redis:7-alpine")
            .withExposedPorts(6379);
    
    @Autowired
    private TestRestTemplate restTemplate;
    
    @Autowired
    private JwtTokenProvider tokenProvider;
    
    @Test
    @DisplayName("Should sync employee successfully")
    void shouldSyncEmployeeSuccessfully() {
        // Given
        String token = tokenProvider.generateToken("test-client");
        HttpHeaders headers = new HttpHeaders();
        headers.setBearerAuth(token);
        
        EmployeeSyncRequest request = EmployeeSyncRequest.builder()
            .employeeId("HR123")
            .erpSystem("FRAPPE")
            .action("CREATE")
            .build();
        
        HttpEntity<EmployeeSyncRequest> entity = new HttpEntity<>(request, headers);
        
        // When
        ResponseEntity<IntegrationResponse> response = restTemplate.postForEntity(
            "/api/employees/sync", entity, IntegrationResponse.class
        );
        
        // Then
        assertThat(response.getStatusCode()).isEqualTo(HttpStatus.OK);
        assertThat(response.getBody().isSuccess()).isTrue();
    }
}
```

**2. Camel Route Tests** (`src/test/java/routes/`):
```java
@CamelSpringBootTest
@SpringBootTest
class ERPIntegrationRouteTest extends CamelTestSupport {
    
    @Autowired
    private CamelContext camelContext;
    
    @Override
    protected CamelContext createCamelContext() {
        return camelContext;
    }
    
    @Test
    @DisplayName("Should route employee to correct ERP system")
    void shouldRouteToCorrectERPSystem() throws Exception {
        // Given
        getMockEndpoint("mock:frappe-employee").expectedMessageCount(1);
        getMockEndpoint("mock:sap-employee").expectedMessageCount(0);
        
        Employee employee = createTestEmployee();
        
        // When
        template.sendBodyAndHeader("direct:sync-employee", employee, "ERP_SYSTEM", "FRAPPE");
        
        // Then
        assertMockEndpointsSatisfied();
    }
}
```

### Testing Commands

```bash
# Run all tests
./mvnw test

# Run specific test class
./mvnw test -Dtest=EmployeeIntegrationServiceTest

# Run integration tests only
./mvnw test -Dtest=**/*IT

# Run with coverage
./mvnw test jacoco:report

# Run performance tests
./mvnw test -Dtest=**/*PerformanceTest
```

---

## 🔧 Configuration Management

### Application Properties

**1. Base Configuration** (`application.yml`):
```yaml
spring:
  application:
    name: erp-integration-service
  
  datasource:
    url: ${DATABASE_URL:jdbc:postgresql://localhost:5433/erp_integration}
    username: ${DATABASE_USERNAME:erp_user}
    password: ${DATABASE_PASSWORD:secure_password}
    driver-class-name: org.postgresql.Driver
    hikari:
      maximum-pool-size: 20
      minimum-idle: 5
      connection-timeout: 30000
      idle-timeout: 600000
      max-lifetime: 1800000
  
  jpa:
    hibernate:
      ddl-auto: validate
    show-sql: false
    properties:
      hibernate:
        dialect: org.hibernate.dialect.PostgreSQLDialect
        format_sql: true
        jdbc:
          batch_size: 20
        order_inserts: true
        order_updates: true
  
  redis:
    host: ${REDIS_HOST:localhost}
    port: ${REDIS_PORT:6379}
    timeout: 2000ms
    jedis:
      pool:
        max-active: 8
        max-idle: 8
        min-idle: 0
  
  rabbitmq:
    host: ${RABBITMQ_HOST:localhost}
    port: ${RABBITMQ_PORT:5672}
    username: ${RABBITMQ_USERNAME:guest}
    password: ${RABBITMQ_PASSWORD:guest}
    virtual-host: /
    connection-timeout: 30000

server:
  port: ${SERVER_PORT:8083}
  servlet:
    context-path: /
  compression:
    enabled: true
    mime-types: application/json,application/xml,text/html,text/xml,text/plain

management:
  endpoints:
    web:
      exposure:
        include: health,info,metrics,prometheus
  endpoint:
    health:
      show-details: always
  metrics:
    export:
      prometheus:
        enabled: true

logging:
  level:
    com.erp.integration: ${LOG_LEVEL:INFO}
    org.springframework.security: WARN
    org.apache.camel: INFO
  pattern:
    console: "%d{yyyy-MM-dd HH:mm:ss} - %msg%n"
    file: "%d{yyyy-MM-dd HH:mm:ss} [%thread] %-5level %logger{36} - %msg%n"
  file:
    name: ${LOG_FILE:logs/integration-service.log}

camel:
  springboot:
    name: erp-integration-camel
  component:
    servlet:
      mapping:
        context-path: /camel/*
```

**2. Security Configuration** (`SecurityConfig.java`):
```java
@Configuration
@EnableWebSecurity
@EnableMethodSecurity
public class SecurityConfig {
    
    @Bean
    public SecurityFilterChain filterChain(HttpSecurity http) throws Exception {
        http
            .csrf(csrf -> csrf.disable())
            .sessionManagement(session -> 
                session.sessionCreationPolicy(SessionCreationPolicy.STATELESS))
            .authorizeHttpRequests(authz -> authz
                .requestMatchers("/health", "/actuator/health").permitAll()
                .requestMatchers("/oauth2/**").permitAll()
                .requestMatchers("/api/public/**").permitAll()
                .requestMatchers(HttpMethod.GET, "/api/docs/**").permitAll()
                .requestMatchers("/api/**").authenticated()
                .anyRequest().authenticated()
            )
            .oauth2ResourceServer(oauth2 -> oauth2
                .jwt(jwt -> jwt
                    .decoder(jwtDecoder())
                    .jwtAuthenticationConverter(jwtAuthenticationConverter())
                )
            )
            .exceptionHandling(exceptions -> exceptions
                .authenticationEntryPoint(authenticationEntryPoint())
                .accessDeniedHandler(accessDeniedHandler())
            );
            
        return http.build();
    }
    
    @Bean
    public JwtDecoder jwtDecoder() {
        return NimbusJwtDecoder.withPublicKey(publicKey()).build();
    }
    
    @Bean
    public RSAPublicKey publicKey() {
        try {
            String publicKeyPath = environment.getProperty("JWT_PUBLIC_KEY_PATH");
            byte[] keyBytes = Files.readAllBytes(Paths.get(publicKeyPath));
            String publicKeyPEM = new String(keyBytes)
                .replace("-----BEGIN PUBLIC KEY-----", "")
                .replace("-----END PUBLIC KEY-----", "")
                .replaceAll("\\s", "");
            
            byte[] decoded = Base64.getDecoder().decode(publicKeyPEM);
            X509EncodedKeySpec spec = new X509EncodedKeySpec(decoded);
            KeyFactory keyFactory = KeyFactory.getInstance("RSA");
            return (RSAPublicKey) keyFactory.generatePublic(spec);
        } catch (Exception e) {
            throw new RuntimeException("Failed to load public key", e);
        }
    }
}
```

---

## 🔍 Monitoring & Observability

### Custom Metrics

**1. Business Metrics** (`MetricsService.java`):
```java
@Service
public class MetricsService {
    
    private final MeterRegistry meterRegistry;
    private final Counter syncSuccessCounter;
    private final Counter syncFailureCounter;
    private final Timer syncDurationTimer;
    private final Gauge activeIntegrationsGauge;
    
    public MetricsService(MeterRegistry meterRegistry) {
        this.meterRegistry = meterRegistry;
        this.syncSuccessCounter = Counter.builder("erp.sync.success")
            .description("Number of successful ERP synchronizations")
            .tag("type", "employee")
            .register(meterRegistry);
            
        this.syncFailureCounter = Counter.builder("erp.sync.failure")
            .description("Number of failed ERP synchronizations")
            .tag("type", "employee")
            .register(meterRegistry);
            
        this.syncDurationTimer = Timer.builder("erp.sync.duration")
            .description("Time taken for ERP synchronization")
            .register(meterRegistry);
            
        this.activeIntegrationsGauge = Gauge.builder("erp.integrations.active")
            .description("Number of active integrations")
            .register(meterRegistry, this, MetricsService::getActiveIntegrationsCount);
    }
    
    public void recordSyncSuccess(String erpSystem) {
        syncSuccessCounter.increment(Tags.of("erp_system", erpSystem));
    }
    
    public void recordSyncFailure(String erpSystem, String errorType) {
        syncFailureCounter.increment(Tags.of(
            "erp_system", erpSystem,
            "error_type", errorType
        ));
    }
    
    public Timer.Sample startSyncTimer() {
        return Timer.start(meterRegistry);
    }
    
    public void stopSyncTimer(Timer.Sample sample, String erpSystem) {
        sample.stop(Timer.builder("erp.sync.duration")
            .tag("erp_system", erpSystem)
            .register(meterRegistry));
    }
    
    private double getActiveIntegrationsCount() {
        // Implementation to count active integrations
        return integrationStatusRepository.countActiveIntegrations();
    }
}
```

**2. Health Indicators** (`ERPHealthIndicator.java`):
```java
@Component
public class ERPHealthIndicator implements HealthIndicator {
    
    private final ERPClientFactory erpClientFactory;
    
    public ERPHealthIndicator(ERPClientFactory erpClientFactory) {
        this.erpClientFactory = erpClientFactory;
    }
    
    @Override
    public Health health() {
        Health.Builder builder = new Health.Builder();
        
        try {
            Map<String, String> details = new HashMap<>();
            boolean allHealthy = true;
            
            for (String erpSystem : Arrays.asList("FRAPPE", "SAP", "ORACLE", "DYNAMICS")) {
                try {
                    ERPClient client = erpClientFactory.getClient(erpSystem);
                    boolean isHealthy = client.healthCheck();
                    details.put(erpSystem.toLowerCase(), isHealthy ? "UP" : "DOWN");
                    
                    if (!isHealthy) {
                        allHealthy = false;
                    }
                } catch (Exception e) {
                    details.put(erpSystem.toLowerCase(), "ERROR: " + e.getMessage());
                    allHealthy = false;
                }
            }
            
            builder.withDetails(details);
            
            if (allHealthy) {
                builder.up();
            } else {
                builder.down();
            }
            
        } catch (Exception e) {
            builder.down(e);
        }
        
        return builder.build();
    }
}
```

### Logging Configuration

**1. Structured Logging** (`LoggingAspect.java`):
```java
@Aspect
@Component
public class LoggingAspect {
    
    private final Logger logger = LoggerFactory.getLogger(LoggingAspect.class);
    private final ObjectMapper objectMapper;
    
    @Around("@annotation(Loggable)")
    public Object logExecutionTime(ProceedingJoinPoint joinPoint) throws Throwable {
        String methodName = joinPoint.getSignature().getName();
        String className = joinPoint.getTarget().getClass().getSimpleName();
        
        Map<String, Object> logData = new HashMap<>();
        logData.put("method", methodName);
        logData.put("class", className);
        logData.put("startTime", System.currentTimeMillis());
        
        try {
            Object result = joinPoint.proceed();
            logData.put("status", "SUCCESS");
            logData.put("duration", System.currentTimeMillis() - (Long) logData.get("startTime"));
            
            logger.info("Method execution: {}", objectMapper.writeValueAsString(logData));
            return result;
            
        } catch (Exception e) {
            logData.put("status", "ERROR");
            logData.put("error", e.getMessage());
            logData.put("duration", System.currentTimeMillis() - (Long) logData.get("startTime"));
            
            logger.error("Method execution failed: {}", objectMapper.writeValueAsString(logData));
            throw e;
        }
    }
    
    @AfterThrowing(pointcut = "@annotation(AuditLog)", throwing = "exception")
    public void logException(JoinPoint joinPoint, Exception exception) {
        Map<String, Object> auditData = new HashMap<>();
        auditData.put("method", joinPoint.getSignature().getName());
        auditData.put("exception", exception.getClass().getSimpleName());
        auditData.put("message", exception.getMessage());
        auditData.put("timestamp", LocalDateTime.now());
        
        logger.error("Audit log - Exception: {}", auditData);
    }
}
```

---

## 🚀 Deployment Strategies

### Docker Optimization

**1. Multi-stage Dockerfile** (`Dockerfile`):
```dockerfile
# Build stage
FROM openjdk:17-jdk-alpine AS builder

WORKDIR /app

# Copy maven files
COPY pom.xml .
COPY .mvn .mvn
COPY mvnw .

# Download dependencies
RUN ./mvnw dependency:go-offline -B

# Copy source code
COPY src src

# Build application
RUN ./mvnw package -DskipTests

# Runtime stage
FROM openjdk:17-jre-alpine AS runtime

# Add non-root user
RUN addgroup -g 1001 -S appgroup && \
    adduser -u 1001 -S appuser -G appgroup

# Install security updates
RUN apk update && apk upgrade && \
    apk add --no-cache curl && \
    rm -rf /var/cache/apk/*

WORKDIR /app

# Copy built application
COPY --from=builder /app/target/integration-service-*.jar app.jar

# Change ownership
RUN chown -R appuser:appgroup /app

# Switch to non-root user
USER appuser

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8083/health || exit 1

# Expose port
EXPOSE 8083

# JVM optimization
ENV JAVA_OPTS="-XX:+UseContainerSupport -XX:MaxRAMPercentage=75.0 -XX:+UseG1GC"

# Run application
ENTRYPOINT ["sh", "-c", "java $JAVA_OPTS -jar app.jar"]
```

**2. Production Docker Compose** (`docker-compose.prod.yml`):
```yaml
version: '3.8'

services:
  integration-service:
    image: erp-integration:latest
    restart: unless-stopped
    environment:
      - SPRING_PROFILES_ACTIVE=production
      - DATABASE_URL=jdbc:postgresql://postgres:5432/erp_integration
      - REDIS_HOST=redis
      - RABBITMQ_HOST=rabbitmq
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
      rabbitmq:
        condition: service_healthy
    networks:
      - erp-network
    deploy:
      replicas: 3
      resources:
        limits:
          cpus: '2.0'
          memory: 2G
        reservations:
          cpus: '1.0'
          memory: 1G
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 3
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8083/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 60s

  postgres:
    image: postgres:15-alpine
    restart: unless-stopped
    environment:
      POSTGRES_DB: erp_integration
      POSTGRES_USER: erp_user
      POSTGRES_PASSWORD_FILE: /run/secrets/postgres_password
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./database/init:/docker-entrypoint-initdb.d
    secrets:
      - postgres_password
    networks:
      - erp-network
    deploy:
      resources:
        limits:
          cpus: '1.0'
          memory: 1G
        reservations:
          cpus: '0.5'
          memory: 512M
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U erp_user -d erp_integration"]
      interval: 10s
      timeout: 5s
      retries: 5

networks:
  erp-network:
    driver: overlay
    encrypted: true

volumes:
  postgres_data:
    driver: local

secrets:
  postgres_password:
    external: true
```

### Kubernetes Deployment

**1. Deployment Manifest** (`k8s/deployment.yaml`):
```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: erp-integration
  namespace: erp-system
  labels:
    app: erp-integration
    version: v1.0.0
spec:
  replicas: 3
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxSurge: 1
      maxUnavailable: 0
  selector:
    matchLabels:
      app: erp-integration
  template:
    metadata:
      labels:
        app: erp-integration
        version: v1.0.0
    spec:
      serviceAccountName: erp-integration-sa
      securityContext:
        runAsNonRoot: true
        runAsUser: 1001
        fsGroup: 1001
      containers:
      - name: integration-service
        image: erp-integration:1.0.0
        imagePullPolicy: Always
        ports:
        - containerPort: 8083
          name: http
          protocol: TCP
        env:
        - name: SPRING_PROFILES_ACTIVE
          value: "kubernetes"
        - name: DATABASE_URL
          valueFrom:
            secretKeyRef:
              name: database-secret
              key: url
        - name: DATABASE_USERNAME
          valueFrom:
            secretKeyRef:
              name: database-secret
              key: username
        - name: DATABASE_PASSWORD
          valueFrom:
            secretKeyRef:
              name: database-secret
              key: password
        resources:
          requests:
            memory: "1Gi"
            cpu: "500m"
          limits:
            memory: "2Gi"
            cpu: "1000m"
        livenessProbe:
          httpGet:
            path: /health
            port: 8083
          initialDelaySeconds: 60
          periodSeconds: 30
          timeoutSeconds: 10
          failureThreshold: 3
        readinessProbe:
          httpGet:
            path: /health
            port: 8083
          initialDelaySeconds: 30
          periodSeconds: 10
          timeoutSeconds: 5
          failureThreshold: 3
        volumeMounts:
        - name: config-volume
          mountPath: /app/config
          readOnly: true
        - name: logs-volume
          mountPath: /app/logs
      volumes:
      - name: config-volume
        configMap:
          name: erp-integration-config
      - name: logs-volume
        emptyDir: {}
```

---

## 📊 Performance Optimization

### Database Optimization

**1. Connection Pooling** (`HikariConfig.java`):
```java
@Configuration
public class DatabaseConfig {
    
    @Bean
    @Primary
    @ConfigurationProperties("spring.datasource.hikari")
    public HikariConfig hikariConfig() {
        HikariConfig config = new HikariConfig();
        
        // Pool sizing
        config.setMaximumPoolSize(20);
        config.setMinimumIdle(5);
        config.setIdleTimeout(TimeUnit.MINUTES.toMillis(10));
        config.setMaxLifetime(TimeUnit.MINUTES.toMillis(30));
        config.setConnectionTimeout(TimeUnit.SECONDS.toMillis(30));
        
        // Performance optimizations
        config.setLeakDetectionThreshold(TimeUnit.MINUTES.toMillis(2));
        config.addDataSourceProperty("cachePrepStmts", "true");
        config.addDataSourceProperty("prepStmtCacheSize", "250");
        config.addDataSourceProperty("prepStmtCacheSqlLimit", "2048");
        config.addDataSourceProperty("useServerPrepStmts", "true");
        config.addDataSourceProperty("rewriteBatchedStatements", "true");
        config.addDataSourceProperty("cacheResultSetMetadata", "true");
        config.addDataSourceProperty("cacheServerConfiguration", "true");
        config.addDataSourceProperty("elideSetAutoCommits", "true");
        config.addDataSourceProperty("maintainTimeStats", "false");
        
        return config;
    }
}
```

**2. Query Optimization** (`EmployeeRepository.java`):
```java
@Repository
public interface EmployeeRepository extends JpaRepository<Employee, Long> {
    
    @Query(value = """
        SELECT e FROM Employee e 
        WHERE e.lastSyncDate IS NULL 
           OR e.lastSyncDate < :syncThreshold
        ORDER BY e.lastSyncDate ASC NULLS FIRST
        """)
    List<Employee> findEmployeesNeedingSync(@Param("syncThreshold") LocalDateTime syncThreshold);
    
    @Query(value = """
        SELECT e FROM Employee e 
        LEFT JOIN FETCH e.department d
        LEFT JOIN FETCH e.position p
        WHERE e.status = :status
        """)
    List<Employee> findActiveEmployeesWithDetails(@Param("status") EmployeeStatus status);
    
    @Query(value = """
        SELECT COUNT(e) FROM Employee e 
        WHERE e.lastSyncDate > :since
        """)
    long countRecentlySyncedEmployees(@Param("since") LocalDateTime since);
    
    @Modifying
    @Query(value = """
        UPDATE Employee e 
        SET e.lastSyncDate = :syncDate 
        WHERE e.id IN :employeeIds
        """)
    void updateSyncDates(@Param("employeeIds") List<Long> employeeIds, 
                        @Param("syncDate") LocalDateTime syncDate);
}
```

### Caching Strategy

**1. Redis Configuration** (`CacheConfig.java`):
```java
@Configuration
@EnableCaching
public class CacheConfig {
    
    @Bean
    public CacheManager cacheManager(RedisConnectionFactory connectionFactory) {
        RedisCacheConfiguration config = RedisCacheConfiguration.defaultCacheConfig()
            .entryTtl(Duration.ofMinutes(10))
            .serializeKeysWith(RedisSerializationContext.SerializationPair
                .fromSerializer(new StringRedisSerializer()))
            .serializeValuesWith(RedisSerializationContext.SerializationPair
                .fromSerializer(new GenericJackson2JsonRedisSerializer()));
        
        return RedisCacheManager.builder(connectionFactory)
            .cacheDefaults(config)
            .transactionAware()
            .build();
    }
    
    @Bean
    public RedisTemplate<String, Object> redisTemplate(RedisConnectionFactory connectionFactory) {
        RedisTemplate<String, Object> template = new RedisTemplate<>();
        template.setConnectionFactory(connectionFactory);
        template.setKeySerializer(new StringRedisSerializer());
        template.setValueSerializer(new GenericJackson2JsonRedisSerializer());
        template.setHashKeySerializer(new StringRedisSerializer());
        template.setHashValueSerializer(new GenericJackson2JsonRedisSerializer());
        template.setDefaultSerializer(new GenericJackson2JsonRedisSerializer());
        template.afterPropertiesSet();
        return template;
    }
}
```

**2. Service Layer Caching** (`EmployeeService.java`):
```java
@Service
@CacheConfig(cacheNames = "employees")
public class EmployeeService {
    
    @Cacheable(key = "#employeeId")
    public Employee getEmployee(String employeeId) {
        return employeeRepository.findByHrSystemId(employeeId)
            .orElseThrow(() -> new EmployeeNotFoundException(employeeId));
    }
    
    @CachePut(key = "#employee.hrSystemId")
    public Employee updateEmployee(Employee employee) {
        return employeeRepository.save(employee);
    }
    
    @CacheEvict(key = "#employeeId")
    public void deleteEmployee(String employeeId) {
        employeeRepository.deleteByHrSystemId(employeeId);
    }
    
    @Cacheable(key = "'department:' + #department")
    public List<Employee> getEmployeesByDepartment(String department) {
        return employeeRepository.findByDepartment(department);
    }
    
    @CacheEvict(allEntries = true)
    @Scheduled(fixedRate = 3600000) // Every hour
    public void clearCache() {
        // Cache will be automatically cleared
    }
}
```

---

This developer guide provides comprehensive technical documentation for building, testing, deploying, and maintaining the ERP integration system. It covers architecture patterns, development workflows, testing strategies, configuration management, monitoring implementation, and performance optimization techniques essential for developers working on this system.