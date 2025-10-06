# HR Modules Documentation

## 🏢 HR System Component Overview

The Laravel HR Boilerplate consists of 10 comprehensive HR modules that work together to provide a complete human resources management solution.

```mermaid
graph TB
    subgraph "Core HR Modules"
        EMP[Employee Management]
        DEPT[Department Management]
        ATT[Attendance System]
        LEAVE[Leave Management]
        PAY[Payroll System]
        PERF[Performance Management]
        DOC[Document Management]
        WF[Workflow Engine]
        REP[Reporting System]
        SET[System Settings]
    end
    
    subgraph "Data Flow"
        EMP --> ATT
        EMP --> LEAVE
        EMP --> PAY
        EMP --> PERF
        EMP --> DOC
        
        DEPT --> EMP
        ATT --> PAY
        LEAVE --> PAY
        PERF --> PAY
        
        WF --> LEAVE
        WF --> PERF
        WF --> DOC
        
        All_Modules[All Modules] --> REP
        SET --> All_Modules
    end
    
    subgraph "Integration Points"
        API[REST API]
        Events[Event System]
        Queue[Queue Jobs]
        Cache[Redis Cache]
        Search[Meilisearch]
    end
    
    All_Modules --> API
    All_Modules --> Events
    All_Modules --> Queue
    All_Modules --> Cache
    REP --> Search
    
    classDef core fill:#e3f2fd
    classDef flow fill:#f3e5f5
    classDef integration fill:#e8f5e8
    
    class EMP,DEPT,ATT,LEAVE,PAY,PERF,DOC,WF,REP,SET core
    class All_Modules flow
    class API,Events,Queue,Cache,Search integration
```

## 👥 Employee Management Module

### Architecture
```mermaid
graph TB
    subgraph "Employee Management Components"
        EmpController[Employee Controller]
        EmpService[Employee Service]
        EmpRepository[Employee Repository]
        UserModel[User Model]
        ProfileModel[User Profile Model]
        EmergencyModel[Emergency Contact Model]
        SkillModel[User Skills Model]
        CertModel[User Certifications Model]
    end
    
    subgraph "Related Systems"
        Auth[Authentication]
        Permission[Permissions]
        Workflow[Workflow System]
        Document[Document Storage]
        Audit[Audit Logging]
    end
    
    EmpController --> EmpService
    EmpService --> EmpRepository
    EmpRepository --> UserModel
    UserModel --> ProfileModel
    UserModel --> EmergencyModel
    UserModel --> SkillModel
    UserModel --> CertModel
    
    EmpService --> Auth
    EmpService --> Permission
    EmpService --> Workflow
    EmpService --> Document
    EmpService --> Audit
    
    classDef controller fill:#e3f2fd
    classDef service fill:#f3e5f5
    classDef model fill:#e8f5e8
    classDef external fill:#fff3e0
    
    class EmpController controller
    class EmpService,EmpRepository service
    class UserModel,ProfileModel,EmergencyModel,SkillModel,CertModel model
    class Auth,Permission,Workflow,Document,Audit external
```

### Key Features
- **Employee Profiles**: Complete employee information management
- **Organizational Hierarchy**: Manager-employee relationships
- **Multi-tenant Support**: Company-based employee isolation
- **Skills & Certifications**: Track employee capabilities
- **Emergency Contacts**: Critical contact information
- **Document Attachments**: Employee-related documents
- **Audit Trail**: Complete history of changes

### Employee Lifecycle Flow
```mermaid
sequenceDiagram
    participant HR
    participant System
    participant Employee
    participant Manager
    participant Workflow
    
    Note over HR,Workflow: Employee Onboarding
    HR->>System: Create Employee Profile
    System->>Workflow: Trigger Onboarding Workflow
    Workflow->>Manager: Assign New Employee
    Workflow->>Employee: Send Welcome Email
    Employee->>System: Complete Profile
    Manager->>System: Approve Onboarding
    System->>HR: Onboarding Complete
    
    Note over HR,Workflow: Employee Updates
    Employee->>System: Request Profile Update
    System->>Workflow: Trigger Approval Workflow
    Workflow->>Manager: Review Changes
    Manager->>System: Approve/Reject Changes
    System->>Employee: Notify Decision
    
    Note over HR,Workflow: Employee Offboarding
    HR->>System: Initiate Offboarding
    System->>Workflow: Trigger Offboarding Workflow
    Workflow->>Manager: Final Approvals
    Workflow->>System: Disable Access
    System->>HR: Generate Exit Reports
```

## 🏢 Department Management Module

### Department Hierarchy
```mermaid
graph TB
    Company[Company]
    
    Company --> IT[IT Department]
    Company --> HR[HR Department]
    Company --> Finance[Finance Department]
    Company --> Sales[Sales Department]
    
    IT --> Dev[Development Team]
    IT --> QA[QA Team]
    IT --> DevOps[DevOps Team]
    
    HR --> Recruitment[Recruitment]
    HR --> Training[Training & Development]
    
    Finance --> Accounting[Accounting]
    Finance --> Payroll[Payroll Processing]
    
    Sales --> Regional1[Regional Sales 1]
    Sales --> Regional2[Regional Sales 2]
    
    Dev --> Frontend[Frontend Developers]
    Dev --> Backend[Backend Developers]
    
    classDef company fill:#e3f2fd
    classDef department fill:#f3e5f5
    classDef team fill:#e8f5e8
    classDef subteam fill:#fff3e0
    
    class Company company
    class IT,HR,Finance,Sales department
    class Dev,QA,DevOps,Recruitment,Training,Accounting,Payroll,Regional1,Regional2 team
    class Frontend,Backend subteam
```

### Department Features
- **Hierarchical Structure**: Multi-level department organization
- **Budget Management**: Department-wise budget tracking
- **Cost Centers**: Financial reporting by department
- **Manager Assignment**: Department head management
- **Employee Transfer**: Inter-department movement workflows

## ⏰ Attendance System Module

### Attendance Flow
```mermaid
sequenceDiagram
    participant Employee
    participant System
    participant Biometric
    participant Manager
    participant Payroll
    
    Note over Employee,Payroll: Daily Attendance Flow
    Employee->>Biometric: Scan Fingerprint/Badge
    Biometric->>System: Send Attendance Data
    System->>System: Validate Location/Time
    System->>Employee: Confirm Check-in
    
    loop Throughout Day
        Employee->>System: Break Start
        System->>Employee: Break Logged
        Employee->>System: Break End
        System->>Employee: Break Completed
    end
    
    Employee->>System: Check Out
    System->>System: Calculate Hours
    System->>Manager: Send Overtime Alert (if any)
    Manager->>System: Approve/Reject Overtime
    
    Note over Employee,Payroll: Monthly Processing
    System->>System: Generate Monthly Summary
    System->>Payroll: Send Attendance Data
    Payroll->>System: Process Payroll
```

### Attendance Features
- **Multiple Check-in Methods**: Biometric, mobile app, web portal
- **Location Tracking**: GPS-based attendance validation
- **Break Management**: Track break times and duration
- **Overtime Calculation**: Automatic overtime detection
- **Shift Management**: Multiple shift support
- **Attendance Policies**: Flexible policy configuration
- **Real-time Monitoring**: Live attendance dashboard

### Attendance Analytics
```mermaid
graph TB
    subgraph "Attendance Metrics"
        Daily[Daily Attendance]
        Monthly[Monthly Summary]
        Overtime[Overtime Analysis]
        Lateness[Lateness Tracking]
        Absenteeism[Absenteeism Rate]
        Productivity[Productivity Metrics]
    end
    
    subgraph "Reporting Outputs"
        Dashboard[Real-time Dashboard]
        Weekly_Report[Weekly Reports]
        Monthly_Report[Monthly Reports]
        Payroll_Export[Payroll Export]
        Manager_Alerts[Manager Alerts]
    end
    
    Daily --> Dashboard
    Monthly --> Monthly_Report
    Overtime --> Manager_Alerts
    Lateness --> Weekly_Report
    Absenteeism --> Monthly_Report
    Productivity --> Dashboard
    
    Monthly --> Payroll_Export
    
    classDef metrics fill:#e3f2fd
    classDef reports fill:#f3e5f5
    
    class Daily,Monthly,Overtime,Lateness,Absenteeism,Productivity metrics
    class Dashboard,Weekly_Report,Monthly_Report,Payroll_Export,Manager_Alerts reports
```

## 🏖️ Leave Management Module

### Leave Request Workflow
```mermaid
stateDiagram-v2
    [*] --> Draft
    Draft --> Submitted : Employee Submits
    Submitted --> ManagerReview : Auto Assignment
    ManagerReview --> ManagerApproved : Manager Approves
    ManagerReview --> ManagerRejected : Manager Rejects
    ManagerApproved --> HRReview : Requires HR Approval
    ManagerApproved --> Approved : Auto Approve
    HRReview --> Approved : HR Approves
    HRReview --> Rejected : HR Rejects
    ManagerRejected --> [*]
    Rejected --> [*]
    Approved --> InProgress : Leave Date Arrives
    InProgress --> Completed : Employee Returns
    Approved --> Cancelled : Employee Cancels
    Cancelled --> [*]
    Completed --> [*]
```

### Leave Types & Policies
```mermaid
graph TB
    subgraph "Leave Types"
        Annual[Annual Leave]
        Sick[Sick Leave]
        Maternity[Maternity Leave]
        Paternity[Paternity Leave]
        Emergency[Emergency Leave]
        Study[Study Leave]
        Unpaid[Unpaid Leave]
        Compensatory[Compensatory Leave]
    end
    
    subgraph "Policy Rules"
        Allocation[Annual Allocation]
        Approval[Approval Requirements]
        Carryover[Carryover Rules]
        Encashment[Leave Encashment]
        Prorating[Pro-rating Rules]
        Blackout[Blackout Periods]
    end
    
    subgraph "Integration Points"
        Calendar[Company Calendar]
        Payroll_Sys[Payroll System]
        Attendance_Sys[Attendance System]
        Workflow_Sys[Workflow System]
    end
    
    Annual --> Allocation
    Annual --> Carryover
    Annual --> Encashment
    
    Sick --> Approval
    Maternity --> Approval
    Paternity --> Approval
    
    All_Leave[All Leave Types] --> Calendar
    All_Leave --> Payroll_Sys
    All_Leave --> Attendance_Sys
    All_Leave --> Workflow_Sys
    
    classDef leave fill:#e3f2fd
    classDef policy fill:#f3e5f5
    classDef integration fill:#e8f5e8
    
    class Annual,Sick,Maternity,Paternity,Emergency,Study,Unpaid,Compensatory leave
    class Allocation,Approval,Carryover,Encashment,Prorating,Blackout policy
    class Calendar,Payroll_Sys,Attendance_Sys,Workflow_Sys integration
```

## 💰 Payroll System Module

### Payroll Processing Flow
```mermaid
sequenceDiagram
    participant HR
    participant Payroll_System
    participant Attendance
    participant Leave
    participant Tax_Engine
    participant Bank
    participant Employee
    
    Note over HR,Employee: Monthly Payroll Processing
    HR->>Payroll_System: Initiate Payroll Run
    Payroll_System->>Attendance: Fetch Attendance Data
    Payroll_System->>Leave: Fetch Leave Data
    Payroll_System->>Payroll_System: Calculate Basic Components
    Payroll_System->>Tax_Engine: Calculate Taxes
    Tax_Engine-->>Payroll_System: Tax Amounts
    Payroll_System->>Payroll_System: Generate Payslips
    Payroll_System->>HR: Review Payroll
    HR->>Payroll_System: Approve Payroll
    Payroll_System->>Bank: Generate Bank File
    Bank-->>Payroll_System: Confirmation
    Payroll_System->>Employee: Send Payslip
```

### Salary Components
```mermaid
graph TB
    subgraph "Earnings"
        Basic[Basic Salary]
        HRA[House Rent Allowance]
        Transport[Transport Allowance]
        Medical[Medical Allowance]
        Overtime[Overtime Pay]
        Bonus[Performance Bonus]
        Commission[Sales Commission]
        Other_Allowances[Other Allowances]
    end
    
    subgraph "Deductions"
        Tax[Income Tax]
        PF[Provident Fund]
        ESI[Employee State Insurance]
        Loan[Loan Deduction]
        Advance[Salary Advance]
        LOP[Loss of Pay]
        Other_Deductions[Other Deductions]
    end
    
    subgraph "Calculations"
        Gross[Gross Salary]
        Total_Deductions[Total Deductions]
        Net[Net Salary]
    end
    
    Basic --> Gross
    HRA --> Gross
    Transport --> Gross
    Medical --> Gross
    Overtime --> Gross
    Bonus --> Gross
    Commission --> Gross
    Other_Allowances --> Gross
    
    Tax --> Total_Deductions
    PF --> Total_Deductions
    ESI --> Total_Deductions
    Loan --> Total_Deductions
    Advance --> Total_Deductions
    LOP --> Total_Deductions
    Other_Deductions --> Total_Deductions
    
    Gross --> Net
    Total_Deductions --> Net
    
    classDef earnings fill:#e8f5e8
    classDef deductions fill:#ffebee
    classDef calculations fill:#e3f2fd
    
    class Basic,HRA,Transport,Medical,Overtime,Bonus,Commission,Other_Allowances earnings
    class Tax,PF,ESI,Loan,Advance,LOP,Other_Deductions deductions
    class Gross,Total_Deductions,Net calculations
```

## 📊 Performance Management Module

### Performance Review Cycle
```mermaid
graph TB
    subgraph "Performance Cycle"
        Goal_Setting[Goal Setting]
        Mid_Review[Mid-Year Review]
        Self_Assessment[Self Assessment]
        Manager_Review[Manager Review]
        Calibration[Calibration]
        Final_Review[Final Review]
        Development_Plan[Development Planning]
    end
    
    subgraph "Stakeholders"
        Employee[Employee]
        Manager[Direct Manager]
        HR[HR Team]
        Senior_Manager[Senior Manager]
        Peers[Peer Reviewers]
    end
    
    Goal_Setting --> Mid_Review
    Mid_Review --> Self_Assessment
    Self_Assessment --> Manager_Review
    Manager_Review --> Calibration
    Calibration --> Final_Review
    Final_Review --> Development_Plan
    Development_Plan --> Goal_Setting
    
    Employee --> Goal_Setting
    Employee --> Self_Assessment
    Manager --> Goal_Setting
    Manager --> Manager_Review
    HR --> Calibration
    Senior_Manager --> Calibration
    Peers --> Manager_Review
    
    classDef cycle fill:#e3f2fd
    classDef stakeholder fill:#f3e5f5
    
    class Goal_Setting,Mid_Review,Self_Assessment,Manager_Review,Calibration,Final_Review,Development_Plan cycle
    class Employee,Manager,HR,Senior_Manager,Peers stakeholder
```

### Performance Metrics
```mermaid
graph TB
    subgraph "Performance Categories"
        Technical[Technical Skills]
        Behavioral[Behavioral Competencies]
        Goals[Goal Achievement]
        Leadership[Leadership Skills]
        Innovation[Innovation & Creativity]
    end
    
    subgraph "Rating Methods"
        Numeric[Numeric Scale (1-5)]
        Descriptive[Descriptive Ratings]
        Ranking[Forced Ranking]
        Continuous[Continuous Feedback]
    end
    
    subgraph "Review Types"
        Annual[Annual Review]
        Quarterly[Quarterly Check-in]
        Project[Project-based Review]
        Peer[360-Degree Feedback]
        Probation[Probation Review]
    end
    
    Technical --> Numeric
    Behavioral --> Descriptive
    Goals --> Numeric
    Leadership --> Peer
    Innovation --> Continuous
    
    All_Categories[All Categories] --> Annual
    All_Categories --> Quarterly
    Technical --> Project
    All_Categories --> Peer
    All_Categories --> Probation
    
    classDef category fill:#e3f2fd
    classDef method fill:#f3e5f5
    classDef type fill:#e8f5e8
    
    class Technical,Behavioral,Goals,Leadership,Innovation category
    class Numeric,Descriptive,Ranking,Continuous method
    class Annual,Quarterly,Project,Peer,Probation type
```

## 📄 Document Management Module

### Document Lifecycle
```mermaid
stateDiagram-v2
    [*] --> Upload
    Upload --> Review : Auto/Manual
    Review --> Approved : Document Valid
    Review --> Rejected : Issues Found
    Rejected --> Upload : Re-upload
    Approved --> Active : Document Live
    Active --> Expired : Expiry Date Reached
    Active --> Archived : Manual Archive
    Active --> Updated : New Version
    Updated --> Review
    Expired --> Renewed : Renewal Process
    Expired --> Archived : No Renewal
    Renewed --> Review
    Archived --> [*]
```

### Document Categories & Security
```mermaid
graph TB
    subgraph "Document Categories"
        Personal[Personal Documents]
        Employment[Employment Documents]
        Training[Training Certificates]
        Compliance[Compliance Documents]
        Project[Project Documents]
        Policy[HR Policies]
    end
    
    subgraph "Security Levels"
        Public[Public Access]
        Internal[Internal Only]
        Confidential[Confidential]
        Restricted[Restricted Access]
    end
    
    subgraph "Access Control"
        Owner[Document Owner]
        Manager[Manager Access]
        HR_Access[HR Team Access]
        Read_Only[Read-Only Users]
        No_Access[No Access]
    end
    
    Personal --> Confidential
    Employment --> Internal
    Training --> Public
    Compliance --> Internal
    Project --> Internal
    Policy --> Public
    
    Public --> Read_Only
    Internal --> HR_Access
    Confidential --> Owner
    Restricted --> Manager
    
    classDef category fill:#e3f2fd
    classDef security fill:#f3e5f5
    classDef access fill:#e8f5e8
    
    class Personal,Employment,Training,Compliance,Project,Policy category
    class Public,Internal,Confidential,Restricted security
    class Owner,Manager,HR_Access,Read_Only,No_Access access
```

## 🔄 Workflow Engine Module

### Workflow Types
```mermaid
graph TB
    subgraph "HR Workflows"
        Leave_WF[Leave Approval]
        Performance_WF[Performance Review]
        Recruitment_WF[Recruitment Process]
        Onboarding_WF[Employee Onboarding]
        Document_WF[Document Approval]
        Expense_WF[Expense Approval]
        Training_WF[Training Request]
        Transfer_WF[Department Transfer]
    end
    
    subgraph "Workflow Components"
        Trigger[Workflow Trigger]
        Steps[Workflow Steps]
        Conditions[Conditional Logic]
        Approvers[Approval Matrix]
        Notifications[Email/SMS Alerts]
        Escalation[Escalation Rules]
    end
    
    subgraph "Workflow States"
        Draft[Draft State]
        InProgress[In Progress]
        Pending[Pending Approval]
        Approved[Approved]
        Rejected[Rejected]
        Cancelled[Cancelled]
    end
    
    All_Workflows[All Workflows] --> Trigger
    Trigger --> Steps
    Steps --> Conditions
    Conditions --> Approvers
    Approvers --> Notifications
    Notifications --> Escalation
    
    Draft --> InProgress
    InProgress --> Pending
    Pending --> Approved
    Pending --> Rejected
    InProgress --> Cancelled
    
    classDef workflow fill:#e3f2fd
    classDef component fill:#f3e5f5
    classDef state fill:#e8f5e8
    
    class Leave_WF,Performance_WF,Recruitment_WF,Onboarding_WF,Document_WF,Expense_WF,Training_WF,Transfer_WF workflow
    class Trigger,Steps,Conditions,Approvers,Notifications,Escalation component
    class Draft,InProgress,Pending,Approved,Rejected,Cancelled state
```

## 📊 Reporting System Module

### Report Categories
```mermaid
graph TB
    subgraph "Operational Reports"
        Daily_Attendance[Daily Attendance]
        Leave_Summary[Leave Summary]
        Overtime_Report[Overtime Report]
        Headcount[Headcount Report]
        New_Joiners[New Joiners]
        Exits[Employee Exits]
    end
    
    subgraph "Financial Reports"
        Payroll_Summary[Payroll Summary]
        Cost_Center[Cost Center Analysis]
        Benefit_Costs[Benefit Costs]
        Tax_Reports[Tax Reports]
        Compliance_Costs[Compliance Costs]
    end
    
    subgraph "Analytics Reports"
        Performance_Analytics[Performance Analytics]
        Attendance_Trends[Attendance Trends]
        Leave_Patterns[Leave Patterns]
        Turnover_Analysis[Turnover Analysis]
        Training_Effectiveness[Training ROI]
        Diversity_Metrics[Diversity Metrics]
    end
    
    subgraph "Compliance Reports"
        Regulatory[Regulatory Reports]
        Audit_Trail[Audit Trail]
        Policy_Compliance[Policy Compliance]
        Training_Compliance[Training Compliance]
    end
    
    classDef operational fill:#e3f2fd
    classDef financial fill:#f3e5f5
    classDef analytics fill:#e8f5e8
    classDef compliance fill:#fff3e0
    
    class Daily_Attendance,Leave_Summary,Overtime_Report,Headcount,New_Joiners,Exits operational
    class Payroll_Summary,Cost_Center,Benefit_Costs,Tax_Reports,Compliance_Costs financial
    class Performance_Analytics,Attendance_Trends,Leave_Patterns,Turnover_Analysis,Training_Effectiveness,Diversity_Metrics analytics
    class Regulatory,Audit_Trail,Policy_Compliance,Training_Compliance compliance
```

## ⚙️ System Settings Module

### Configuration Areas
```mermaid
graph TB
    subgraph "Company Settings"
        Company_Info[Company Information]
        Branding[Branding & Logo]
        Locations[Office Locations]
        Holidays[Company Holidays]
        Work_Calendar[Work Calendar]
    end
    
    subgraph "Policy Settings"
        Leave_Policies[Leave Policies]
        Attendance_Rules[Attendance Rules]
        Overtime_Rules[Overtime Rules]
        Performance_Settings[Performance Settings]
        Workflow_Config[Workflow Configuration]
    end
    
    subgraph "System Settings"
        User_Roles[User Roles]
        Permissions[Permission Matrix]
        Email_Templates[Email Templates]
        Notification_Rules[Notification Rules]
        Integration_Config[Integration Settings]
    end
    
    subgraph "Security Settings"
        Password_Policy[Password Policy]
        Session_Config[Session Configuration]
        API_Keys[API Key Management]
        Audit_Settings[Audit Settings]
        Backup_Config[Backup Configuration]
    end
    
    classDef company fill:#e3f2fd
    classDef policy fill:#f3e5f5
    classDef system fill:#e8f5e8
    classDef security fill:#fff3e0
    
    class Company_Info,Branding,Locations,Holidays,Work_Calendar company
    class Leave_Policies,Attendance_Rules,Overtime_Rules,Performance_Settings,Workflow_Config policy
    class User_Roles,Permissions,Email_Templates,Notification_Rules,Integration_Config system
    class Password_Policy,Session_Config,API_Keys,Audit_Settings,Backup_Config security
```

---

**Next**: [Security Architecture](../architecture/security-architecture.md) | [Development Guide](../development/getting-started.md)