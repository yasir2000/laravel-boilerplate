# System Architecture Overview

## 📐 High-Level System Architecture

```mermaid
graph TB
    subgraph "Client Layer"
        Web[Web Browser]
        Mobile[Mobile App]
        API_Client[API Clients]
    end
    
    subgraph "Load Balancer"
        LB[Load Balancer / Reverse Proxy]
    end
    
    subgraph "Application Layer"
        subgraph "Laravel Application Servers"
            App1[Laravel App Server 1<br/>Port 8000 - Standard]
            App2[Laravel App Server 2<br/>Port 8001 - Octane]
        end
        
        subgraph "Queue Workers"
            QW1[Queue Worker 1]
            QW2[Queue Worker 2]
            QW3[Queue Worker 3]
        end
    end
    
    subgraph "Caching Layer"
        Redis[(Redis Cache)]
        Session[(Session Store)]
    end
    
    subgraph "Search Engine"
        Meilisearch[(Meilisearch)]
    end
    
    subgraph "Database Layer"
        MySQL[(MySQL 8.0<br/>Primary Database)]
        MySQL_Read[(MySQL Read Replica)]
    end
    
    subgraph "Storage Layer"
        Local_Storage[Local File Storage]
        S3[AWS S3 / Cloud Storage]
    end
    
    subgraph "External Services"
        SMTP[SMTP Email Service]
        SMS[SMS Gateway]
        Payment[Payment Gateway]
    end
    
    subgraph "Monitoring & Logging"
        Logs[Application Logs]
        Metrics[Performance Metrics]
        Health[Health Checks]
    end
    
    %% Client connections
    Web --> LB
    Mobile --> LB
    API_Client --> LB
    
    %% Load balancer to app servers
    LB --> App1
    LB --> App2
    
    %% Application connections
    App1 --> Redis
    App1 --> MySQL
    App1 --> MySQL_Read
    App1 --> Meilisearch
    App1 --> Local_Storage
    App1 --> S3
    
    App2 --> Redis
    App2 --> MySQL
    App2 --> MySQL_Read
    App2 --> Meilisearch
    App2 --> Local_Storage
    App2 --> S3
    
    %% Queue workers
    QW1 --> Redis
    QW1 --> MySQL
    QW2 --> Redis
    QW2 --> MySQL
    QW3 --> Redis
    QW3 --> MySQL
    
    %% External service connections
    App1 --> SMTP
    App1 --> SMS
    App1 --> Payment
    App2 --> SMTP
    App2 --> SMS
    App2 --> Payment
    
    %% Queue workers to external services
    QW1 --> SMTP
    QW2 --> SMS
    QW3 --> Payment
    
    %% Session management
    App1 --> Session
    App2 --> Session
    
    %% Monitoring
    App1 --> Logs
    App1 --> Metrics
    App1 --> Health
    App2 --> Logs
    App2 --> Metrics
    App2 --> Health
    
    %% Database replication
    MySQL --> MySQL_Read
    
    classDef client fill:#e1f5fe
    classDef app fill:#f3e5f5
    classDef cache fill:#fff3e0
    classDef database fill:#e8f5e8
    classDef storage fill:#fce4ec
    classDef external fill:#f1f8e9
    classDef monitor fill:#fff8e1
    
    class Web,Mobile,API_Client client
    class App1,App2,QW1,QW2,QW3 app
    class Redis,Session cache
    class MySQL,MySQL_Read,Meilisearch database
    class Local_Storage,S3 storage
    class SMTP,SMS,Payment external
    class Logs,Metrics,Health monitor
```

## 🏗️ Laravel Application Architecture

```mermaid
graph TB
    subgraph "Presentation Layer"
        Routes[Route Definitions]
        Controllers[Controllers]
        Middleware[Middleware Stack]
        Views[Inertia Views]
        API[API Resources]
    end
    
    subgraph "Business Logic Layer"
        Services[Service Classes]
        Actions[Action Classes]
        Jobs[Queue Jobs]
        Events[Event Classes]
        Listeners[Event Listeners]
    end
    
    subgraph "Data Access Layer"
        Models[Eloquent Models]
        Repositories[Repository Pattern]
        DTOs[Data Transfer Objects]
        Observers[Model Observers]
    end
    
    subgraph "Infrastructure Layer"
        Database[(Database)]
        Cache[(Cache Store)]
        Queue[(Queue System)]
        Storage[(File Storage)]
        External[External APIs]
    end
    
    %% Request flow
    Routes --> Middleware
    Middleware --> Controllers
    Controllers --> Services
    Controllers --> API
    
    %% Business logic flow
    Services --> Actions
    Services --> Jobs
    Services --> Events
    Events --> Listeners
    
    %% Data access flow
    Services --> Models
    Services --> Repositories
    Models --> Database
    Repositories --> Models
    
    %% Infrastructure connections
    Models --> Cache
    Jobs --> Queue
    Listeners --> Database
    Listeners --> External
    Services --> Storage
    
    %% Observer connections
    Models --> Observers
    Observers --> Events
    
    %% View rendering
    Controllers --> Views
    
    classDef presentation fill:#e3f2fd
    classDef business fill:#f3e5f5
    classDef data fill:#e8f5e8
    classDef infrastructure fill:#fff3e0
    
    class Routes,Controllers,Middleware,Views,API presentation
    class Services,Actions,Jobs,Events,Listeners business
    class Models,Repositories,DTOs,Observers data
    class Database,Cache,Queue,Storage,External infrastructure
```

## 🔄 Request Lifecycle Flow

```mermaid
sequenceDiagram
    participant Client
    participant LB as Load Balancer
    participant MW as Middleware Stack
    participant Router
    participant Controller
    participant Service
    participant Model
    participant DB as Database
    participant Cache
    participant View
    
    Client->>LB: HTTP Request
    LB->>MW: Forward Request
    
    Note over MW: Security, CORS, Auth, Rate Limiting
    MW->>Router: Process Request
    
    Router->>Controller: Route to Action
    Controller->>Service: Business Logic
    
    Service->>Cache: Check Cache
    alt Cache Hit
        Cache-->>Service: Return Cached Data
    else Cache Miss
        Service->>Model: Query Data
        Model->>DB: Execute Query
        DB-->>Model: Return Results
        Model-->>Service: Transform Data
        Service->>Cache: Store in Cache
    end
    
    Service-->>Controller: Return Data
    Controller->>View: Render Response
    View-->>Controller: Rendered View
    Controller-->>Client: HTTP Response
```

## 🏢 HR System Component Architecture

```mermaid
graph TB
    subgraph "Core HR Components"
        Employee[Employee Management]
        Department[Department Management]
        Attendance[Attendance System]
        Leave[Leave Management]
        Payroll[Payroll System]
        Performance[Performance Management]
        Document[Document Management]
        Workflow[Workflow Engine]
        Reporting[Reporting System]
        Settings[System Settings]
    end
    
    subgraph "Shared Services"
        Auth[Authentication Service]
        Permission[Permission Service]
        Notification[Notification Service]
        Email[Email Service]
        File[File Management Service]
        Audit[Audit Log Service]
        Cache_Service[Cache Service]
        Search[Search Service]
    end
    
    subgraph "External Integrations"
        LDAP[LDAP/Active Directory]
        Payment_Gateway[Payment Gateways]
        Email_Provider[Email Providers]
        SMS_Gateway[SMS Gateway]
        Calendar[Calendar Systems]
        Biometric[Biometric Devices]
    end
    
    %% Core component relationships
    Employee --> Department
    Employee --> Attendance
    Employee --> Leave
    Employee --> Payroll
    Employee --> Performance
    Employee --> Document
    
    Attendance --> Leave
    Leave --> Payroll
    Performance --> Payroll
    
    %% Workflow connections
    Workflow --> Leave
    Workflow --> Performance
    Workflow --> Document
    
    %% Shared service connections
    Employee --> Auth
    Employee --> Permission
    Department --> Permission
    
    Attendance --> Notification
    Leave --> Notification
    Payroll --> Notification
    Performance --> Notification
    
    Document --> File
    Employee --> File
    
    All_Components[All Components] --> Audit
    All_Components --> Cache_Service
    Reporting --> Search
    
    %% External integrations
    Auth --> LDAP
    Payroll --> Payment_Gateway
    Notification --> Email_Provider
    Notification --> SMS_Gateway
    Attendance --> Biometric
    Leave --> Calendar
    
    classDef core fill:#e3f2fd
    classDef shared fill:#f3e5f5
    classDef external fill:#e8f5e8
    
    class Employee,Department,Attendance,Leave,Payroll,Performance,Document,Workflow,Reporting,Settings core
    class Auth,Permission,Notification,Email,File,Audit,Cache_Service,Search shared
    class LDAP,Payment_Gateway,Email_Provider,SMS_Gateway,Calendar,Biometric external
```

## 🗄️ Database Architecture

```mermaid
erDiagram
    COMPANIES ||--o{ USERS : "employs"
    COMPANIES ||--o{ DEPARTMENTS : "has"
    
    USERS ||--o{ ATTENDANCES : "records"
    USERS ||--o{ LEAVES : "requests"
    USERS ||--o{ PAYROLLS : "receives"
    USERS ||--o{ PERFORMANCES : "evaluated"
    USERS ||--o{ DOCUMENTS : "owns"
    
    DEPARTMENTS ||--o{ USERS : "contains"
    DEPARTMENTS ||--o{ POSITIONS : "has"
    
    USERS ||--o{ WORKFLOWS : "initiates"
    WORKFLOWS ||--o{ WORKFLOW_STEPS : "contains"
    
    COMPANIES {
        uuid id PK
        string name
        string email
        string phone
        text address
        json settings
        timestamp created_at
        timestamp updated_at
    }
    
    USERS {
        uuid id PK
        string first_name
        string last_name
        string email UK
        string phone
        uuid company_id FK
        uuid department_id FK
        uuid manager_id FK
        string job_title
        decimal salary
        date hire_date
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }
    
    DEPARTMENTS {
        uuid id PK
        string name
        string description
        uuid company_id FK
        uuid manager_id FK
        timestamp created_at
        timestamp updated_at
    }
    
    ATTENDANCES {
        uuid id PK
        uuid user_id FK
        date attendance_date
        time check_in
        time check_out
        decimal hours_worked
        string status
        text notes
        timestamp created_at
        timestamp updated_at
    }
    
    LEAVES {
        uuid id PK
        uuid user_id FK
        string leave_type
        date start_date
        date end_date
        integer days_requested
        text reason
        string status
        uuid approved_by FK
        timestamp approved_at
        timestamp created_at
        timestamp updated_at
    }
    
    PAYROLLS {
        uuid id PK
        uuid user_id FK
        string period
        decimal basic_salary
        decimal allowances
        decimal deductions
        decimal net_salary
        string status
        timestamp processed_at
        timestamp created_at
        timestamp updated_at
    }
```

## 🔐 Security Architecture

```mermaid
graph TB
    subgraph "Authentication Layer"
        Login[Login System]
        MFA[Multi-Factor Auth]
        SSO[Single Sign-On]
        Session[Session Management]
    end
    
    subgraph "Authorization Layer"
        RBAC[Role-Based Access Control]
        Permissions[Permission System]
        Guards[Route Guards]
        Policies[Model Policies]
    end
    
    subgraph "Data Protection"
        Encryption[Data Encryption]
        Hashing[Password Hashing]
        Tokenization[API Tokenization]
        Validation[Input Validation]
    end
    
    subgraph "Security Monitoring"
        Audit_Log[Audit Logging]
        Rate_Limiting[Rate Limiting]
        Intrusion[Intrusion Detection]
        Alerts[Security Alerts]
    end
    
    Login --> MFA
    Login --> Session
    SSO --> Session
    
    Session --> RBAC
    RBAC --> Permissions
    Permissions --> Guards
    Guards --> Policies
    
    All_Data[All Data] --> Encryption
    Passwords[User Passwords] --> Hashing
    API_Keys[API Access] --> Tokenization
    User_Input[User Input] --> Validation
    
    All_Actions[All Actions] --> Audit_Log
    API_Endpoints[API Endpoints] --> Rate_Limiting
    System_Activity[System Activity] --> Intrusion
    Security_Events[Security Events] --> Alerts
    
    classDef auth fill:#e3f2fd
    classDef authz fill:#f3e5f5
    classDef protection fill:#e8f5e8
    classDef monitoring fill:#fff3e0
    
    class Login,MFA,SSO,Session auth
    class RBAC,Permissions,Guards,Policies authz
    class Encryption,Hashing,Tokenization,Validation protection
    class Audit_Log,Rate_Limiting,Intrusion,Alerts monitoring
```

## ⚡ Performance Architecture

```mermaid
graph TB
    subgraph "Frontend Performance"
        CDN[Content Delivery Network]
        Asset_Optimization[Asset Optimization]
        Lazy_Loading[Lazy Loading]
        Code_Splitting[Code Splitting]
    end
    
    subgraph "Application Performance"
        Octane[Laravel Octane]
        OPcache[OPcache]
        JIT[JIT Compilation]
        Memory_Optimization[Memory Optimization]
    end
    
    subgraph "Caching Strategies"
        Redis_Cache[Redis Cache]
        Query_Cache[Query Cache]
        Route_Cache[Route Cache]
        Config_Cache[Config Cache]
        View_Cache[View Cache]
    end
    
    subgraph "Database Performance"
        Query_Optimization[Query Optimization]
        Indexing[Database Indexing]
        Read_Replicas[Read Replicas]
        Connection_Pooling[Connection Pooling]
    end
    
    subgraph "Monitoring & Optimization"
        APM[Application Performance Monitoring]
        Query_Profiling[Query Profiling]
        Memory_Profiling[Memory Profiling]
        Performance_Testing[Performance Testing]
    end
    
    CDN --> Asset_Optimization
    Asset_Optimization --> Lazy_Loading
    Lazy_Loading --> Code_Splitting
    
    Octane --> OPcache
    Octane --> JIT
    JIT --> Memory_Optimization
    
    Redis_Cache --> Query_Cache
    Query_Cache --> Route_Cache
    Route_Cache --> Config_Cache
    Config_Cache --> View_Cache
    
    Query_Optimization --> Indexing
    Indexing --> Read_Replicas
    Read_Replicas --> Connection_Pooling
    
    APM --> Query_Profiling
    Query_Profiling --> Memory_Profiling
    Memory_Profiling --> Performance_Testing
    
    classDef frontend fill:#e3f2fd
    classDef app fill:#f3e5f5
    classDef cache fill:#e8f5e8
    classDef database fill:#fff3e0
    classDef monitoring fill:#fce4ec
    
    class CDN,Asset_Optimization,Lazy_Loading,Code_Splitting frontend
    class Octane,OPcache,JIT,Memory_Optimization app
    class Redis_Cache,Query_Cache,Route_Cache,Config_Cache,View_Cache cache
    class Query_Optimization,Indexing,Read_Replicas,Connection_Pooling database
    class APM,Query_Profiling,Memory_Profiling,Performance_Testing monitoring
```

---

## Technology Stack Details

| Layer | Technology | Purpose | Version |
|-------|------------|---------|---------|
| **Backend Framework** | Laravel | Web application framework | 10.x |
| **Runtime** | PHP | Server-side language | 8.3+ |
| **Performance** | Laravel Octane + FrankenPHP | High-performance application server | Latest |
| **Frontend Framework** | Vue.js | Progressive JavaScript framework | 3.x |
| **Frontend Bridge** | Inertia.js | Modern monolith approach | 1.x |
| **Styling** | Tailwind CSS | Utility-first CSS framework | 3.x |
| **Database** | MySQL | Relational database | 8.0 |
| **Cache** | Redis | In-memory data store | 7.x |
| **Search** | Meilisearch | Fast, typo-tolerant search engine | Latest |
| **Queue** | Redis | Background job processing | - |
| **File Storage** | Local/S3 | File and media storage | - |
| **Container** | Docker | Containerization platform | Latest |
| **Development** | Laravel Sail | Docker development environment | Latest |

## Key Architectural Decisions

### 1. **Monolithic Architecture with API-First Design**
- Single deployable unit for simplicity
- API endpoints for all functionality
- Future-ready for microservices migration

### 2. **Event-Driven Architecture**
- Loose coupling between components
- Extensible workflow system
- Audit trail and notifications

### 3. **Multi-Tenant Ready**
- UUID primary keys
- Company-based data isolation
- Scalable tenant management

### 4. **Performance-First Approach**
- Laravel Octane for high performance
- Multiple caching layers
- Optimized database queries

### 5. **Security by Design**
- Role-based access control
- Comprehensive audit logging
- Data encryption and validation

---

**Next**: [Application Structure](./application-structure.md) | [Data Flow](./data-flow.md)