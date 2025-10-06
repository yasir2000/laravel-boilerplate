# Database Schema Documentation

## 🗄️ Database Architecture Overview

The Laravel HR Boilerplate uses a comprehensive database schema designed for scalability, performance, and data integrity. The system uses UUID primary keys for better distributed system compatibility and security.

## 📊 Complete Entity Relationship Diagram

```mermaid
erDiagram
    %% Core Company & User Management
    COMPANIES ||--o{ USERS : "employs"
    COMPANIES ||--o{ DEPARTMENTS : "has"
    COMPANIES ||--o{ POSITIONS : "defines"
    COMPANIES ||--o{ COMPANY_SETTINGS : "configures"
    
    %% User Relationships
    USERS ||--o{ USER_PROFILES : "has"
    USERS ||--o{ USER_ADDRESSES : "has"
    USERS ||--o{ USER_EMERGENCY_CONTACTS : "has"
    USERS ||--o{ USER_DOCUMENTS : "owns"
    USERS ||--o{ USER_SKILLS : "possesses"
    USERS ||--o{ USER_CERTIFICATIONS : "holds"
    
    %% Department & Position Management
    DEPARTMENTS ||--o{ USERS : "contains"
    DEPARTMENTS ||--o{ POSITIONS : "has"
    POSITIONS ||--o{ USERS : "assigned_to"
    
    %% Attendance System
    USERS ||--o{ ATTENDANCES : "records"
    USERS ||--o{ ATTENDANCE_SUMMARIES : "summarizes"
    ATTENDANCES ||--o{ ATTENDANCE_BREAKS : "includes"
    
    %% Leave Management
    USERS ||--o{ LEAVES : "requests"
    USERS ||--o{ LEAVE_BALANCES : "maintains"
    LEAVE_TYPES ||--o{ LEAVES : "categorizes"
    LEAVE_TYPES ||--o{ LEAVE_POLICIES : "governs"
    
    %% Payroll System
    USERS ||--o{ PAYROLLS : "receives"
    USERS ||--o{ SALARY_COMPONENTS : "has"
    PAYROLLS ||--o{ PAYROLL_ITEMS : "contains"
    PAY_GRADES ||--o{ USERS : "assigned_to"
    
    %% Performance Management
    USERS ||--o{ PERFORMANCE_REVIEWS : "evaluated_in"
    USERS ||--o{ PERFORMANCE_GOALS : "sets"
    PERFORMANCE_REVIEWS ||--o{ PERFORMANCE_RATINGS : "contains"
    PERFORMANCE_TEMPLATES ||--o{ PERFORMANCE_REVIEWS : "structures"
    
    %% Document Management
    USERS ||--o{ DOCUMENTS : "owns"
    DOCUMENT_CATEGORIES ||--o{ DOCUMENTS : "categorizes"
    DOCUMENTS ||--o{ DOCUMENT_VERSIONS : "has"
    
    %% Workflow System
    USERS ||--o{ WORKFLOWS : "initiates"
    WORKFLOWS ||--o{ WORKFLOW_STEPS : "contains"
    WORKFLOW_STEPS ||--o{ WORKFLOW_APPROVALS : "requires"
    WORKFLOW_TEMPLATES ||--o{ WORKFLOWS : "defines"
    
    %% Training & Development
    USERS ||--o{ TRAINING_ENROLLMENTS : "enrolls_in"
    TRAINING_COURSES ||--o{ TRAINING_ENROLLMENTS : "includes"
    TRAINING_COURSES ||--o{ TRAINING_SESSIONS : "scheduled"
    
    %% Project Management
    USERS ||--o{ PROJECT_ASSIGNMENTS : "assigned_to"
    PROJECTS ||--o{ PROJECT_ASSIGNMENTS : "includes"
    PROJECTS ||--o{ PROJECT_TASKS : "contains"
    PROJECT_TASKS ||--o{ TASK_ASSIGNMENTS : "assigned"
    
    %% Communication
    USERS ||--o{ ANNOUNCEMENTS : "creates"
    USERS ||--o{ ANNOUNCEMENT_READS : "reads"
    ANNOUNCEMENTS ||--o{ ANNOUNCEMENT_READS : "tracked"
    
    %% Audit & Permissions
    USERS ||--o{ AUDIT_LOGS : "generates"
    USERS ||--o{ MODEL_HAS_ROLES : "assigned"
    ROLES ||--o{ MODEL_HAS_ROLES : "includes"
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : "grants"
    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : "defined"
    PERMISSIONS ||--o{ MODEL_HAS_PERMISSIONS : "directly_assigned"
    
    %% Companies Table
    COMPANIES {
        uuid id PK
        string name
        string legal_name
        string email
        string phone
        string website
        text address
        string city
        string state
        string country
        string postal_code
        string tax_id
        string industry
        integer employee_count
        json settings
        string logo_path
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }
    
    %% Users Table (Core Employee Data)
    USERS {
        uuid id PK
        string first_name
        string last_name
        string middle_name
        string email UK
        string phone
        timestamp email_verified_at
        timestamp phone_verified_at
        string password
        uuid company_id FK
        uuid department_id FK
        uuid position_id FK
        uuid manager_id FK
        string employee_id UK
        string job_title
        decimal salary
        date hire_date
        date termination_date
        string employment_status
        string employment_type
        boolean is_active
        string locale
        string timezone
        string avatar_path
        text bio
        date date_of_birth
        string gender
        string marital_status
        timestamp last_login_at
        string remember_token
        timestamp two_factor_confirmed_at
        text two_factor_recovery_codes
        text two_factor_secret
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }
    
    %% User Profiles (Extended Information)
    USER_PROFILES {
        uuid id PK
        uuid user_id FK
        string nationality
        string passport_number
        date passport_expiry
        string visa_status
        date visa_expiry
        string social_security_number
        string bank_account_number
        string bank_name
        string bank_branch
        string emergency_contact_name
        string emergency_contact_phone
        string emergency_contact_relationship
        text medical_conditions
        text dietary_restrictions
        json custom_fields
        timestamp created_at
        timestamp updated_at
    }
    
    %% Departments
    DEPARTMENTS {
        uuid id PK
        string name
        string code
        text description
        uuid company_id FK
        uuid manager_id FK
        uuid parent_id FK
        string cost_center
        decimal budget
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }
    
    %% Positions/Job Titles
    POSITIONS {
        uuid id PK
        string title
        string code
        text description
        uuid company_id FK
        uuid department_id FK
        string level
        decimal min_salary
        decimal max_salary
        text requirements
        text responsibilities
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }
    
    %% Attendance Records
    ATTENDANCES {
        uuid id PK
        uuid user_id FK
        date attendance_date
        time check_in
        time check_out
        time break_start
        time break_end
        decimal hours_worked
        decimal overtime_hours
        string status
        string location
        string ip_address
        text notes
        uuid approved_by FK
        timestamp approved_at
        json metadata
        timestamp created_at
        timestamp updated_at
    }
    
    %% Attendance Breaks
    ATTENDANCE_BREAKS {
        uuid id PK
        uuid attendance_id FK
        time break_start
        time break_end
        string break_type
        text reason
        timestamp created_at
        timestamp updated_at
    }
    
    %% Leave Types
    LEAVE_TYPES {
        uuid id PK
        string name
        string code
        text description
        uuid company_id FK
        integer default_days
        boolean is_paid
        boolean requires_approval
        boolean is_active
        json policy_rules
        timestamp created_at
        timestamp updated_at
    }
    
    %% Leave Requests
    LEAVES {
        uuid id PK
        uuid user_id FK
        uuid leave_type_id FK
        date start_date
        date end_date
        decimal days_requested
        text reason
        string status
        uuid approved_by FK
        timestamp approved_at
        text admin_notes
        json attachments
        timestamp created_at
        timestamp updated_at
    }
    
    %% Leave Balances
    LEAVE_BALANCES {
        uuid id PK
        uuid user_id FK
        uuid leave_type_id FK
        integer year
        decimal allocated_days
        decimal used_days
        decimal remaining_days
        decimal carried_forward
        timestamp created_at
        timestamp updated_at
    }
    
    %% Payroll Records
    PAYROLLS {
        uuid id PK
        uuid user_id FK
        string period_type
        date period_start
        date period_end
        decimal basic_salary
        decimal gross_salary
        decimal total_allowances
        decimal total_deductions
        decimal tax_amount
        decimal net_salary
        string status
        timestamp processed_at
        uuid processed_by FK
        json breakdown
        timestamp created_at
        timestamp updated_at
    }
    
    %% Salary Components
    SALARY_COMPONENTS {
        uuid id PK
        uuid user_id FK
        string component_name
        string component_type
        decimal amount
        string calculation_type
        boolean is_taxable
        boolean is_active
        date effective_from
        date effective_to
        timestamp created_at
        timestamp updated_at
    }
    
    %% Performance Reviews
    PERFORMANCE_REVIEWS {
        uuid id PK
        uuid user_id FK
        uuid reviewer_id FK
        uuid template_id FK
        string review_period
        date review_date
        decimal overall_rating
        text strengths
        text areas_for_improvement
        text goals_next_period
        string status
        timestamp created_at
        timestamp updated_at
    }
    
    %% Performance Goals
    PERFORMANCE_GOALS {
        uuid id PK
        uuid user_id FK
        string title
        text description
        string category
        date target_date
        string status
        integer progress_percentage
        decimal weight
        timestamp created_at
        timestamp updated_at
    }
    
    %% Documents
    DOCUMENTS {
        uuid id PK
        uuid user_id FK
        uuid category_id FK
        string title
        text description
        string file_name
        string file_path
        string mime_type
        integer file_size
        string version
        boolean is_confidential
        date expiry_date
        string status
        uuid uploaded_by FK
        timestamp created_at
        timestamp updated_at
    }
    
    %% Workflows
    WORKFLOWS {
        uuid id PK
        uuid user_id FK
        uuid template_id FK
        string title
        text description
        string status
        json data
        timestamp started_at
        timestamp completed_at
        timestamp created_at
        timestamp updated_at
    }
    
    %% Workflow Steps
    WORKFLOW_STEPS {
        uuid id PK
        uuid workflow_id FK
        string step_name
        string step_type
        uuid assigned_to FK
        string status
        text comments
        timestamp due_date
        timestamp completed_at
        timestamp created_at
        timestamp updated_at
    }
    
    %% Training Courses
    TRAINING_COURSES {
        uuid id PK
        string title
        text description
        uuid company_id FK
        string course_type
        integer duration_hours
        decimal cost
        boolean is_mandatory
        boolean is_active
        json prerequisites
        timestamp created_at
        timestamp updated_at
    }
    
    %% Training Enrollments
    TRAINING_ENROLLMENTS {
        uuid id PK
        uuid user_id FK
        uuid course_id FK
        date enrolled_date
        date completion_date
        string status
        decimal score
        text feedback
        timestamp created_at
        timestamp updated_at
    }
    
    %% Projects
    PROJECTS {
        uuid id PK
        string name
        text description
        uuid company_id FK
        uuid manager_id FK
        date start_date
        date end_date
        decimal budget
        string status
        integer progress_percentage
        timestamp created_at
        timestamp updated_at
    }
    
    %% Project Assignments
    PROJECT_ASSIGNMENTS {
        uuid id PK
        uuid project_id FK
        uuid user_id FK
        string role
        date assigned_date
        decimal allocation_percentage
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }
    
    %% Announcements
    ANNOUNCEMENTS {
        uuid id PK
        string title
        text content
        uuid company_id FK
        uuid created_by FK
        string priority
        boolean is_published
        timestamp published_at
        timestamp expires_at
        json target_audience
        timestamp created_at
        timestamp updated_at
    }
    
    %% Roles (Spatie Permission)
    ROLES {
        bigint id PK
        string name
        string guard_name
        timestamp created_at
        timestamp updated_at
    }
    
    %% Permissions (Spatie Permission)
    PERMISSIONS {
        bigint id PK
        string name
        string guard_name
        timestamp created_at
        timestamp updated_at
    }
    
    %% Model Has Roles (Spatie Permission)
    MODEL_HAS_ROLES {
        bigint role_id FK
        string model_type
        uuid model_id
        composite_key role_id,model_type,model_id
    }
    
    %% Role Has Permissions (Spatie Permission)
    ROLE_HAS_PERMISSIONS {
        bigint permission_id FK
        bigint role_id FK
        composite_key permission_id,role_id
    }
    
    %% Model Has Permissions (Spatie Permission)
    MODEL_HAS_PERMISSIONS {
        bigint permission_id FK
        string model_type
        uuid model_id
        composite_key permission_id,model_type,model_id
    }
    
    %% Audit Logs (Spatie Activity Log)
    AUDIT_LOGS {
        bigint id PK
        string log_name
        text description
        uuid subject_id
        string subject_type
        uuid causer_id
        string causer_type
        json properties
        timestamp created_at
        timestamp updated_at
    }
```

## 🔍 Database Indexes and Performance

### Primary Indexes
- All tables use UUID primary keys for distributed system compatibility
- Composite indexes on frequently queried column combinations

### Key Indexes

```sql
-- User Performance Indexes
CREATE INDEX idx_users_company_active ON users(company_id, is_active);
CREATE INDEX idx_users_department ON users(department_id);
CREATE INDEX idx_users_manager ON users(manager_id);
CREATE INDEX idx_users_email_active ON users(email, is_active);

-- Attendance Performance Indexes
CREATE INDEX idx_attendance_user_date ON attendances(user_id, attendance_date);
CREATE INDEX idx_attendance_date_range ON attendances(attendance_date, created_at);
CREATE INDEX idx_attendance_status ON attendances(status);

-- Leave Management Indexes
CREATE INDEX idx_leaves_user_dates ON leaves(user_id, start_date, end_date);
CREATE INDEX idx_leaves_status ON leaves(status);
CREATE INDEX idx_leaves_approval ON leaves(approved_by, approved_at);

-- Payroll Indexes
CREATE INDEX idx_payroll_user_period ON payrolls(user_id, period_start, period_end);
CREATE INDEX idx_payroll_status ON payrolls(status);

-- Performance Review Indexes
CREATE INDEX idx_performance_user_period ON performance_reviews(user_id, review_period);
CREATE INDEX idx_performance_reviewer ON performance_reviews(reviewer_id);

-- Document Management Indexes
CREATE INDEX idx_documents_user_category ON documents(user_id, category_id);
CREATE INDEX idx_documents_expiry ON documents(expiry_date);

-- Audit and Security Indexes
CREATE INDEX idx_audit_subject ON audit_logs(subject_type, subject_id);
CREATE INDEX idx_audit_causer ON audit_logs(causer_type, causer_id);
CREATE INDEX idx_audit_created ON audit_logs(created_at);
```

## 🔒 Data Security and Constraints

### Foreign Key Constraints
```sql
-- User Management Constraints
ALTER TABLE users ADD CONSTRAINT fk_users_company 
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL;
    
ALTER TABLE users ADD CONSTRAINT fk_users_department 
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;
    
ALTER TABLE users ADD CONSTRAINT fk_users_manager 
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL;

-- Attendance Constraints
ALTER TABLE attendances ADD CONSTRAINT fk_attendances_user 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Leave Management Constraints
ALTER TABLE leaves ADD CONSTRAINT fk_leaves_user 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
    
ALTER TABLE leaves ADD CONSTRAINT fk_leaves_type 
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE RESTRICT;

-- Payroll Constraints
ALTER TABLE payrolls ADD CONSTRAINT fk_payrolls_user 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
```

### Data Validation Rules
```sql
-- Check Constraints
ALTER TABLE users ADD CONSTRAINT chk_users_email 
    CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$');

ALTER TABLE attendances ADD CONSTRAINT chk_attendance_times 
    CHECK (check_out IS NULL OR check_out > check_in);

ALTER TABLE leaves ADD CONSTRAINT chk_leave_dates 
    CHECK (end_date >= start_date);

ALTER TABLE payrolls ADD CONSTRAINT chk_payroll_amounts 
    CHECK (gross_salary >= 0 AND net_salary >= 0);
```

## 📊 Data Relationships Summary

### Core Relationships
1. **Company → Users**: One-to-Many (company employs multiple users)
2. **Department → Users**: One-to-Many (department contains multiple users)
3. **User → User**: Self-referencing (manager-employee relationship)
4. **User → Attendances**: One-to-Many (user records multiple attendances)
5. **User → Leaves**: One-to-Many (user requests multiple leaves)
6. **User → Payrolls**: One-to-Many (user receives multiple payrolls)

### Advanced Relationships
1. **Workflow System**: Template → Workflow → Steps → Approvals
2. **Performance Management**: Template → Review → Ratings/Goals
3. **Document Management**: Category → Document → Versions
4. **Training System**: Course → Enrollment → Sessions
5. **Project Management**: Project → Assignment → Tasks

## 🔄 Database Migrations Strategy

### Migration Naming Convention
```
YYYY_MM_DD_HHMMSS_create_table_name_table.php
YYYY_MM_DD_HHMMSS_add_column_to_table_name_table.php
YYYY_MM_DD_HHMMSS_modify_column_in_table_name_table.php
```

### Migration Categories
1. **Core Migrations** (2024_01_01_000001 - 000010): Essential tables
2. **HR Modules** (2024_01_01_000011 - 000050): HR-specific functionality
3. **Extensions** (2024_01_01_000051+): Additional features

## 📈 Scalability Considerations

### Partitioning Strategy
- **Attendance**: Partition by date (monthly/yearly)
- **Audit Logs**: Partition by created_at (monthly)
- **Payrolls**: Partition by period (yearly)

### Archive Strategy
- **Audit Logs**: Archive logs older than 2 years
- **Attendances**: Archive attendance older than 5 years
- **Documents**: Soft delete with configurable retention

### Read Replicas
- Read-heavy operations (reports, analytics)
- Attendance queries
- Performance review data
- Historical payroll data

---

**Next**: [Application Structure](./application-structure.md) | [Data Flow](./data-flow.md)