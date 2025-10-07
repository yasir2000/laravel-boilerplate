# Cloud-Native Messaging & Event Patterns

## 🌊 Event-Driven Architecture Overview

The microservices architecture uses a combination of synchronous and asynchronous communication patterns to ensure loose coupling, scalability, and resilience.

```mermaid
graph TB
    subgraph "Communication Patterns"
        Sync[Synchronous Communication<br/>REST APIs, gRPC]
        Async[Asynchronous Communication<br/>Events, Messages]
        Hybrid[Hybrid Patterns<br/>CQRS, Event Sourcing]
    end
    
    subgraph "Synchronous Patterns"
        RequestResponse[Request-Response<br/>HTTP/REST]
        RPC[Remote Procedure Call<br/>gRPC]
        GraphQLPattern[GraphQL<br/>Federated Queries]
    end
    
    subgraph "Asynchronous Patterns"
        EventDriven[Event-Driven<br/>Domain Events]
        MessageQueues[Message Queues<br/>Task Processing]
        EventSourcing[Event Sourcing<br/>Audit Trail]
        CQRS[CQRS<br/>Read/Write Separation]
    end
    
    subgraph "Message Brokers"
        Kafka[Apache Kafka<br/>Event Streaming]
        RabbitMQ[RabbitMQ<br/>Message Queues]
        Redis[Redis Streams<br/>Real-time Events]
        NATS[NATS<br/>Cloud-native Messaging]
    end
    
    Sync --> RequestResponse
    Sync --> RPC
    Sync --> GraphQLPattern
    
    Async --> EventDriven
    Async --> MessageQueues
    Async --> EventSourcing
    Async --> CQRS
    
    EventDriven --> Kafka
    MessageQueues --> RabbitMQ
    EventSourcing --> Kafka
    CQRS --> Redis
    
    classDef pattern fill:#e3f2fd
    classDef sync fill:#f3e5f5
    classDef async fill:#e8f5e8
    classDef broker fill:#fff3e0
    
    class Sync,Async,Hybrid pattern
    class RequestResponse,RPC,GraphQLPattern sync
    class EventDriven,MessageQueues,EventSourcing,CQRS async
    class Kafka,RabbitMQ,Redis,NATS broker
```

## 🚀 Apache Kafka Event Streaming

### Kafka Cluster Architecture
```mermaid
graph TB
    subgraph "Kafka Cluster"
        subgraph "Broker 1"
            Broker1[Kafka Broker 1<br/>Leader for Partition 0]
            Log1[Commit Log 1]
        end
        
        subgraph "Broker 2"
            Broker2[Kafka Broker 2<br/>Leader for Partition 1]
            Log2[Commit Log 2]
        end
        
        subgraph "Broker 3"
            Broker3[Kafka Broker 3<br/>Leader for Partition 2]
            Log3[Commit Log 3]
        end
    end
    
    subgraph "Zookeeper Ensemble"
        ZK1[Zookeeper 1]
        ZK2[Zookeeper 2]
        ZK3[Zookeeper 3]
    end
    
    subgraph "Kafka Components"
        SchemaRegistry[Schema Registry<br/>Avro Schemas]
        KafkaConnect[Kafka Connect<br/>Data Integration]
        KafkaStreams[Kafka Streams<br/>Stream Processing]
        KSQL[KSQL<br/>Stream Analytics]
    end
    
    subgraph "Topics & Partitions"
        EmployeeTopic[employee-events<br/>3 partitions]
        AttendanceTopic[attendance-events<br/>6 partitions]
        PayrollTopic[payroll-events<br/>3 partitions]
        AuditTopic[audit-events<br/>12 partitions]
    end
    
    Broker1 --> Log1
    Broker2 --> Log2
    Broker3 --> Log3
    
    Broker1 -.-> ZK1
    Broker2 -.-> ZK2
    Broker3 -.-> ZK3
    
    ZK1 -.-> ZK2
    ZK2 -.-> ZK3
    ZK3 -.-> ZK1
    
    Broker1 --> EmployeeTopic
    Broker2 --> AttendanceTopic
    Broker3 --> PayrollTopic
    
    SchemaRegistry --> EmployeeTopic
    KafkaConnect --> AttendanceTopic
    KafkaStreams --> PayrollTopic
    KSQL --> AuditTopic
    
    classDef broker fill:#e3f2fd
    classDef zk fill:#f3e5f5
    classDef component fill:#e8f5e8
    classDef topic fill:#fff3e0
    
    class Broker1,Broker2,Broker3,Log1,Log2,Log3 broker
    class ZK1,ZK2,ZK3 zk
    class SchemaRegistry,KafkaConnect,KafkaStreams,KSQL component
    class EmployeeTopic,AttendanceTopic,PayrollTopic,AuditTopic topic
```

### Event Schema Design
```mermaid
graph TB
    subgraph "Event Schema Strategy"
        BaseEvent[Base Event Schema]
        DomainEvents[Domain-Specific Events]
        SchemaEvolution[Schema Evolution Strategy]
    end
    
    subgraph "Base Event Structure"
        EventMetadata[Event Metadata<br/>eventId, timestamp, version]
        EventType[Event Type<br/>domain.entity.action]
        EventData[Event Data<br/>Business payload]
        EventContext[Event Context<br/>correlation, causation]
    end
    
    subgraph "Employee Domain Events"
        EmployeeCreated[EmployeeCreated]
        EmployeeUpdated[EmployeeUpdated]
        EmployeeDeactivated[EmployeeDeactivated]
        EmployeeDepartmentChanged[DepartmentChanged]
    end
    
    subgraph "Attendance Domain Events"
        CheckInRecorded[CheckInRecorded]
        CheckOutRecorded[CheckOutRecorded]
        BreakStarted[BreakStarted]
        BreakEnded[BreakEnded]
        OvertimeCalculated[OvertimeCalculated]
    end
    
    subgraph "Payroll Domain Events"
        SalaryCalculated[SalaryCalculated]
        PayslipGenerated[PayslipGenerated]
        PaymentProcessed[PaymentProcessed]
        TaxDeducted[TaxDeducted]
    end
    
    BaseEvent --> EventMetadata
    BaseEvent --> EventType
    BaseEvent --> EventData
    BaseEvent --> EventContext
    
    DomainEvents --> EmployeeCreated
    DomainEvents --> EmployeeUpdated
    DomainEvents --> EmployeeDeactivated
    DomainEvents --> EmployeeDepartmentChanged
    
    DomainEvents --> CheckInRecorded
    DomainEvents --> CheckOutRecorded
    DomainEvents --> BreakStarted
    DomainEvents --> BreakEnded
    DomainEvents --> OvertimeCalculated
    
    DomainEvents --> SalaryCalculated
    DomainEvents --> PayslipGenerated
    DomainEvents --> PaymentProcessed
    DomainEvents --> TaxDeducted
    
    classDef schema fill:#e3f2fd
    classDef base fill:#f3e5f5
    classDef employee fill:#e8f5e8
    classDef attendance fill:#fff3e0
    classDef payroll fill:#fce4ec
    
    class BaseEvent,DomainEvents,SchemaEvolution schema
    class EventMetadata,EventType,EventData,EventContext base
    class EmployeeCreated,EmployeeUpdated,EmployeeDeactivated,EmployeeDepartmentChanged employee
    class CheckInRecorded,CheckOutRecorded,BreakStarted,BreakEnded,OvertimeCalculated attendance
    class SalaryCalculated,PayslipGenerated,PaymentProcessed,TaxDeducted payroll
```

### Event Flow Patterns

#### 1. Employee Lifecycle Events
```mermaid
sequenceDiagram
    participant HR as HR Service
    participant Employee as Employee Service
    participant Kafka
    participant Leave as Leave Service
    participant Payroll as Payroll Service
    participant Notification as Notification Service
    participant Audit as Audit Service
    
    Note over HR,Audit: Employee Onboarding Flow
    
    HR->>Employee: Create Employee Profile
    Employee->>Employee: Validate & Store
    Employee->>Kafka: Publish EmployeeCreated Event
    
    Kafka->>Leave: Employee Created (Auto-subscribe)
    Leave->>Leave: Initialize Leave Balance
    Leave->>Kafka: Publish LeaveAccountCreated Event
    
    Kafka->>Payroll: Employee Created (Auto-subscribe)
    Payroll->>Payroll: Setup Payroll Profile
    Payroll->>Kafka: Publish PayrollAccountCreated Event
    
    Kafka->>Notification: Employee Created (Auto-subscribe)
    Notification->>Notification: Send Welcome Email
    Notification->>Kafka: Publish NotificationSent Event
    
    Kafka->>Audit: All Events (Auto-subscribe)
    Audit->>Audit: Log All Activities
```

#### 2. Attendance Processing Events
```mermaid
sequenceDiagram
    participant Device as Biometric Device
    participant Attendance as Attendance Service
    participant Kafka
    participant Payroll as Payroll Service
    participant Manager as Manager Service
    participant Notification as Notification Service
    
    Note over Device,Notification: Daily Attendance Flow
    
    Device->>Attendance: Clock In Event
    Attendance->>Attendance: Validate & Record
    Attendance->>Kafka: Publish CheckInRecorded Event
    
    loop Throughout Day
        Device->>Attendance: Break Start/End
        Attendance->>Kafka: Publish BreakRecorded Events
    end
    
    Device->>Attendance: Clock Out Event
    Attendance->>Attendance: Calculate Hours Worked
    Attendance->>Kafka: Publish CheckOutRecorded Event
    
    Kafka->>Attendance: Process Daily Summary
    Attendance->>Attendance: Calculate Overtime
    Attendance->>Kafka: Publish OvertimeCalculated Event
    
    alt Overtime Detected
        Kafka->>Manager: Overtime Alert (Auto-subscribe)
        Manager->>Manager: Review Overtime
        Manager->>Kafka: Publish OvertimeApproved Event
        
        Kafka->>Payroll: Update Pay Calculation
        Kafka->>Notification: Notify Employee
    end
```

### Schema Evolution Strategy
```json
{
  "name": "EmployeeCreated",
  "type": "record",
  "namespace": "com.hr.employee.events",
  "version": "1.0",
  "fields": [
    {
      "name": "eventMetadata",
      "type": {
        "name": "EventMetadata",
        "type": "record",
        "fields": [
          {"name": "eventId", "type": "string"},
          {"name": "eventType", "type": "string"},
          {"name": "timestamp", "type": "long"},
          {"name": "version", "type": "string"},
          {"name": "source", "type": "string"},
          {"name": "correlationId", "type": ["null", "string"], "default": null},
          {"name": "causationId", "type": ["null", "string"], "default": null}
        ]
      }
    },
    {
      "name": "employeeData",
      "type": {
        "name": "EmployeeData",
        "type": "record",
        "fields": [
          {"name": "employeeId", "type": "string"},
          {"name": "email", "type": "string"},
          {"name": "firstName", "type": "string"},
          {"name": "lastName", "type": "string"},
          {"name": "departmentId", "type": "string"},
          {"name": "managerId", "type": ["null", "string"], "default": null},
          {"name": "jobTitle", "type": "string"},
          {"name": "startDate", "type": "string"},
          {"name": "employmentType", "type": "string"},
          {"name": "status", "type": "string"},
          {"name": "salary", "type": ["null", "double"], "default": null},
          {"name": "currency", "type": ["null", "string"], "default": null}
        ]
      }
    }
  ]
}
```

## 🐰 RabbitMQ Message Queues

### Queue Architecture
```mermaid
graph TB
    subgraph "RabbitMQ Cluster"
        subgraph "Node 1 (Master)"
            Queue1[notification.email]
            Queue2[workflow.approvals]
            Exchange1[Direct Exchange]
        end
        
        subgraph "Node 2 (Replica)"
            Queue3[notification.sms]
            Queue4[reports.generation]
            Exchange2[Topic Exchange]
        end
        
        subgraph "Node 3 (Replica)"
            Queue5[background.tasks]
            Queue6[file.processing]
            Exchange3[Fanout Exchange]
        end
    end
    
    subgraph "Producers"
        NotificationService[Notification Service]
        WorkflowService[Workflow Service]
        ReportingService[Reporting Service]
        DocumentService[Document Service]
    end
    
    subgraph "Consumers"
        EmailWorker[Email Worker]
        SMSWorker[SMS Worker]
        ApprovalWorker[Approval Worker]
        ReportWorker[Report Worker]
        FileWorker[File Worker]
    end
    
    NotificationService --> Exchange1
    WorkflowService --> Exchange2
    ReportingService --> Exchange2
    DocumentService --> Exchange3
    
    Exchange1 --> Queue1
    Exchange1 --> Queue3
    Exchange2 --> Queue2
    Exchange2 --> Queue4
    Exchange3 --> Queue5
    Exchange3 --> Queue6
    
    Queue1 --> EmailWorker
    Queue3 --> SMSWorker
    Queue2 --> ApprovalWorker
    Queue4 --> ReportWorker
    Queue5 --> FileWorker
    Queue6 --> FileWorker
    
    classDef cluster fill:#e3f2fd
    classDef producer fill:#f3e5f5
    classDef consumer fill:#e8f5e8
    classDef queue fill:#fff3e0
    
    class Queue1,Queue2,Queue3,Queue4,Queue5,Queue6,Exchange1,Exchange2,Exchange3 cluster
    class NotificationService,WorkflowService,ReportingService,DocumentService producer
    class EmailWorker,SMSWorker,ApprovalWorker,ReportWorker,FileWorker consumer
```

### Message Patterns

#### 1. Work Queue Pattern
```mermaid
sequenceDiagram
    participant Producer as Email Service
    participant Queue as RabbitMQ Queue
    participant Worker1 as Email Worker 1
    participant Worker2 as Email Worker 2
    participant Worker3 as Email Worker 3
    
    Note over Producer,Worker3: Load Distribution
    
    Producer->>Queue: Send Email Task 1
    Producer->>Queue: Send Email Task 2
    Producer->>Queue: Send Email Task 3
    Producer->>Queue: Send Email Task 4
    Producer->>Queue: Send Email Task 5
    Producer->>Queue: Send Email Task 6
    
    Queue->>Worker1: Deliver Task 1
    Queue->>Worker2: Deliver Task 2
    Queue->>Worker3: Deliver Task 3
    Queue->>Worker1: Deliver Task 4
    Queue->>Worker2: Deliver Task 5
    Queue->>Worker3: Deliver Task 6
    
    Worker1-->>Queue: ACK Task 1
    Worker2-->>Queue: ACK Task 2
    Worker3-->>Queue: ACK Task 3
    Worker1-->>Queue: ACK Task 4
    Worker2-->>Queue: ACK Task 5
    Worker3-->>Queue: ACK Task 6
```

#### 2. Publish-Subscribe Pattern
```mermaid
graph TB
    subgraph "Publisher"
        EmployeeService[Employee Service]
    end
    
    subgraph "Exchange"
        FanoutExchange[Fanout Exchange<br/>employee.events]
    end
    
    subgraph "Queues"
        NotificationQueue[notification.employee]
        AuditQueue[audit.employee]
        ReportingQueue[reporting.employee]
        WorkflowQueue[workflow.employee]
    end
    
    subgraph "Subscribers"
        NotificationWorker[Notification Worker]
        AuditWorker[Audit Worker]
        ReportingWorker[Reporting Worker]
        WorkflowWorker[Workflow Worker]
    end
    
    EmployeeService --> FanoutExchange
    
    FanoutExchange --> NotificationQueue
    FanoutExchange --> AuditQueue
    FanoutExchange --> ReportingQueue
    FanoutExchange --> WorkflowQueue
    
    NotificationQueue --> NotificationWorker
    AuditQueue --> AuditWorker
    ReportingQueue --> ReportingWorker
    WorkflowQueue --> WorkflowWorker
    
    classDef publisher fill:#e3f2fd
    classDef exchange fill:#f3e5f5
    classDef queue fill:#e8f5e8
    classDef subscriber fill:#fff3e0
    
    class EmployeeService publisher
    class FanoutExchange exchange
    class NotificationQueue,AuditQueue,ReportingQueue,WorkflowQueue queue
    class NotificationWorker,AuditWorker,ReportingWorker,WorkflowWorker subscriber
```

#### 3. RPC Pattern
```mermaid
sequenceDiagram
    participant Client as Payroll Service
    participant RequestQueue as salary.calculation.request
    participant Worker as Calculation Worker
    participant ReplyQueue as salary.calculation.reply
    
    Note over Client,ReplyQueue: Synchronous RPC over Message Queue
    
    Client->>RequestQueue: Calculation Request (correlation_id, reply_to)
    RequestQueue->>Worker: Deliver Request
    Worker->>Worker: Process Calculation
    Worker->>ReplyQueue: Send Result (correlation_id)
    ReplyQueue->>Client: Deliver Result
    Client->>Client: Match correlation_id
```

## 📊 Redis Streams for Real-time Events

### Redis Streams Architecture
```mermaid
graph TB
    subgraph "Redis Cluster"
        subgraph "Master Node"
            Stream1[attendance:stream]
            Stream2[notifications:stream]
            ConsumerGroup1[attendance:processors]
        end
        
        subgraph "Replica Node 1"
            Stream3[audit:stream]
            Stream4[alerts:stream]
            ConsumerGroup2[notification:workers]
        end
        
        subgraph "Replica Node 2"
            Stream5[reports:stream]
            ConsumerGroup3[audit:processors]
        end
    end
    
    subgraph "Producers"
        AttendanceService[Attendance Service]
        NotificationService[Notification Service]
        AuditService[Audit Service]
    end
    
    subgraph "Consumer Groups"
        AttendanceConsumer[Attendance Processor]
        NotificationConsumer[Notification Worker]
        AuditConsumer[Audit Processor]
        RealtimeConsumer[Real-time Dashboard]
    end
    
    AttendanceService --> Stream1
    NotificationService --> Stream2
    AuditService --> Stream3
    
    Stream1 --> ConsumerGroup1
    Stream2 --> ConsumerGroup2
    Stream3 --> ConsumerGroup3
    
    ConsumerGroup1 --> AttendanceConsumer
    ConsumerGroup2 --> NotificationConsumer
    ConsumerGroup3 --> AuditConsumer
    
    Stream1 --> RealtimeConsumer
    Stream2 --> RealtimeConsumer
    Stream4 --> RealtimeConsumer
    
    classDef redis fill:#e3f2fd
    classDef producer fill:#f3e5f5
    classDef consumer fill:#e8f5e8
    
    class Stream1,Stream2,Stream3,Stream4,Stream5,ConsumerGroup1,ConsumerGroup2,ConsumerGroup3 redis
    class AttendanceService,NotificationService,AuditService producer
    class AttendanceConsumer,NotificationConsumer,AuditConsumer,RealtimeConsumer consumer
```

## 🔄 Event Sourcing Pattern

### Event Store Design
```mermaid
graph TB
    subgraph "Event Store"
        EventStream[Event Stream<br/>Immutable Log]
        Snapshots[Snapshots<br/>Performance Optimization]
        Projections[Read Projections<br/>Materialized Views]
    end
    
    subgraph "Command Side (Write)"
        Command[Command Handler]
        Aggregate[Aggregate Root]
        DomainEvents[Domain Events]
    end
    
    subgraph "Query Side (Read)"
        QueryHandler[Query Handler]
        ReadModel[Read Models]
        ViewUpdater[View Updater]
    end
    
    subgraph "Event Handlers"
        ProjectionHandler[Projection Handler]
        IntegrationHandler[Integration Handler]
        NotificationHandler[Notification Handler]
    end
    
    Command --> Aggregate
    Aggregate --> DomainEvents
    DomainEvents --> EventStream
    
    EventStream --> Snapshots
    EventStream --> ProjectionHandler
    ProjectionHandler --> Projections
    
    QueryHandler --> ReadModel
    ReadModel --> Projections
    
    EventStream --> ViewUpdater
    ViewUpdater --> ReadModel
    
    EventStream --> IntegrationHandler
    EventStream --> NotificationHandler
    
    classDef store fill:#e3f2fd
    classDef command fill:#f3e5f5
    classDef query fill:#e8f5e8
    classDef handler fill:#fff3e0
    
    class EventStream,Snapshots,Projections store
    class Command,Aggregate,DomainEvents command
    class QueryHandler,ReadModel,ViewUpdater query
    class ProjectionHandler,IntegrationHandler,NotificationHandler handler
```

### Employee Aggregate Example
```mermaid
stateDiagram-v2
    [*] --> Draft
    Draft --> Active : EmployeeActivated
    Active --> Suspended : EmployeeSuspended
    Suspended --> Active : EmployeeReactivated
    Active --> Terminated : EmployeeTerminated
    Suspended --> Terminated : EmployeeTerminated
    Terminated --> [*]
    
    state Active {
        [*] --> Working
        Working --> OnLeave : LeaveStarted
        OnLeave --> Working : LeaveEnded
        Working --> Probation : ProbationStarted
        Probation --> Working : ProbationCompleted
    }
```

## 🔒 Message Security & Reliability

### Security Patterns
```mermaid
graph TB
    subgraph "Message Security"
        Encryption[Message Encryption<br/>TLS/AES-256]
        Authentication[Authentication<br/>SASL/OAuth 2.0]
        Authorization[Authorization<br/>ACL/RBAC]
        Signing[Message Signing<br/>Digital Signatures]
    end
    
    subgraph "Reliability Patterns"
        Persistence[Message Persistence<br/>Durable Queues]
        Replication[Message Replication<br/>Multiple Replicas]
        Acknowledgment[Message Acknowledgment<br/>At-least-once Delivery]
        DeadLetter[Dead Letter Queues<br/>Error Handling]
    end
    
    subgraph "Monitoring & Observability"
        MessageTracing[Message Tracing<br/>Distributed Tracing]
        Metrics[Message Metrics<br/>Throughput, Latency]
        Alerting[Message Alerting<br/>Error Detection]
        Audit[Message Audit<br/>Compliance Logging]
    end
    
    Encryption --> Authentication
    Authentication --> Authorization
    Authorization --> Signing
    
    Persistence --> Replication
    Replication --> Acknowledgment
    Acknowledgment --> DeadLetter
    
    MessageTracing --> Metrics
    Metrics --> Alerting
    Alerting --> Audit
    
    classDef security fill:#e3f2fd
    classDef reliability fill:#f3e5f5
    classDef monitoring fill:#e8f5e8
    
    class Encryption,Authentication,Authorization,Signing security
    class Persistence,Replication,Acknowledgment,DeadLetter reliability
    class MessageTracing,Metrics,Alerting,Audit monitoring
```

---

**Next**: [Service Implementations](../services/README.md) | [API Gateway](../infrastructure/api-gateway.md) | [Deployment](../deployment/README.md)