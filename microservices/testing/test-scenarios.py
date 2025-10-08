# ERP Integration Testing Scenarios and Test Data
# This file contains test scenarios and mock data for comprehensive testing

## Test Scenario 1: Employee Synchronization
### Description: Test complete employee data sync between Laravel HR and ERP systems
### Expected Behavior: Employee data should be transformed and synchronized correctly

# Mock Employee Data (Laravel HR Format)
MOCK_EMPLOYEE_LARAVEL = {
    "id": 1001,
    "employee_id": "EMP001",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@company.com",
    "phone": "+1-555-0123",
    "position": "Software Engineer",
    "department": "Engineering",
    "hire_date": "2023-01-15",
    "salary": 75000.00,
    "status": "active",
    "manager_id": 1002,
    "address": {
        "street": "123 Main St",
        "city": "New York",
        "state": "NY",
        "zip": "10001",
        "country": "USA"
    },
    "emergency_contact": {
        "name": "Jane Doe",
        "relationship": "Spouse",
        "phone": "+1-555-0124"
    }
}

# Expected Employee Data (Frappe/ERPNext Format)
EXPECTED_EMPLOYEE_FRAPPE = {
    "doctype": "Employee",
    "employee": "EMP001",
    "employee_name": "John Doe",
    "first_name": "John",
    "last_name": "Doe",
    "personal_email": "john.doe@company.com",
    "cell_number": "+1-555-0123",
    "designation": "Software Engineer",
    "department": "Engineering",
    "date_of_joining": "2023-01-15",
    "status": "Active",
    "reports_to": "EMP002",
    "current_address": "123 Main St, New York, NY 10001, USA",
    "emergency_contact_1": "Jane Doe",
    "emergency_contact_relation_1": "Spouse",
    "emergency_contact_1_no": "+1-555-0124"
}

## Test Scenario 2: Payroll Synchronization
### Description: Test payroll data transformation and sync

# Mock Payroll Data (Laravel Format)
MOCK_PAYROLL_LARAVEL = [
    {
        "id": 2001,
        "employee_id": 1001,
        "pay_period_start": "2023-10-01",
        "pay_period_end": "2023-10-31",
        "gross_salary": 6250.00,
        "basic_salary": 5000.00,
        "allowances": {
            "housing": 800.00,
            "transport": 300.00,
            "meal": 150.00
        },
        "deductions": {
            "tax": 1100.00,
            "insurance": 200.00,
            "retirement": 312.50
        },
        "net_salary": 4637.50,
        "pay_date": "2023-11-05",
        "status": "processed"
    }
]

# Expected Payroll Data (Frappe Format)
EXPECTED_PAYROLL_FRAPPE = {
    "doctype": "Salary Slip",
    "employee": "EMP001",
    "employee_name": "John Doe",
    "posting_date": "2023-11-05",
    "start_date": "2023-10-01",
    "end_date": "2023-10-31",
    "earnings": [
        {"salary_component": "Basic", "amount": 5000.00},
        {"salary_component": "Housing Allowance", "amount": 800.00},
        {"salary_component": "Transport Allowance", "amount": 300.00},
        {"salary_component": "Meal Allowance", "amount": 150.00}
    ],
    "deductions": [
        {"salary_component": "Income Tax", "amount": 1100.00},
        {"salary_component": "Health Insurance", "amount": 200.00},
        {"salary_component": "Retirement Fund", "amount": 312.50}
    ],
    "gross_pay": 6250.00,
    "total_deduction": 1612.50,
    "net_pay": 4637.50
}

## Test Scenario 3: Accounting Integration
### Description: Test chart of accounts and journal entries sync

# Mock Chart of Accounts (Laravel Format)
MOCK_ACCOUNTS_LARAVEL = [
    {
        "id": 3001,
        "account_code": "1000",
        "account_name": "Cash in Hand",
        "account_type": "Asset",
        "parent_account": "1000",
        "is_group": false,
        "balance": 25000.00
    },
    {
        "id": 3002,
        "account_code": "5000",
        "account_name": "Salary Expense",
        "account_type": "Expense",
        "parent_account": "5000",
        "is_group": false,
        "balance": 180000.00
    }
]

# Mock Journal Entry (Laravel Format)
MOCK_JOURNAL_ENTRY_LARAVEL = {
    "id": 4001,
    "entry_date": "2023-10-31",
    "reference": "JE-2023-001",
    "description": "Monthly salary payment",
    "entries": [
        {
            "account_id": 3002,
            "account_code": "5000",
            "debit": 180000.00,
            "credit": 0.00,
            "description": "Salary expense for October 2023"
        },
        {
            "account_id": 3001,
            "account_code": "1000",
            "debit": 0.00,
            "credit": 180000.00,
            "description": "Cash payment for salaries"
        }
    ]
}

## Test Scenario 4: Error Handling Validation
### Description: Test system behavior with invalid data

# Invalid Employee Data (Missing Required Fields)
INVALID_EMPLOYEE_DATA = {
    "id": 9999,
    # Missing employee_id, first_name, last_name
    "email": "invalid@test.com",
    "status": "invalid_status"  # Invalid status value
}

# Invalid Payroll Data (Inconsistent Calculations)
INVALID_PAYROLL_DATA = {
    "id": 9998,
    "employee_id": 1001,
    "gross_salary": 5000.00,
    "net_salary": 6000.00,  # Net salary > Gross salary (invalid)
    "deductions": {
        "tax": -100.00  # Negative deduction (invalid)
    }
}

## Test Scenario 5: Performance Testing Data
### Description: Large dataset for performance testing

def generate_bulk_employee_data(count: int = 1000):
    """Generate bulk employee data for performance testing"""
    employees = []
    for i in range(count):
        employee = {
            "id": 10000 + i,
            "employee_id": f"EMP{10000 + i:05d}",
            "first_name": f"Employee{i}",
            "last_name": f"Test{i}",
            "email": f"employee{i}@testcompany.com",
            "position": "Test Position",
            "department": "Test Department",
            "hire_date": "2023-01-01",
            "salary": 50000.00 + (i * 100),
            "status": "active"
        }
        employees.append(employee)
    return employees

## Test Scenario 6: Data Transformation Validation
### Description: Verify correct data mapping between systems

# Data Mapping Tests
EMPLOYEE_FIELD_MAPPING = {
    # Laravel Field -> Frappe Field
    "employee_id": "employee",
    "first_name": "first_name",
    "last_name": "last_name",
    "email": "personal_email",
    "phone": "cell_number",
    "position": "designation",
    "department": "department",
    "hire_date": "date_of_joining",
    "status": "status"  # Requires value transformation (active -> Active)
}

PAYROLL_FIELD_MAPPING = {
    "employee_id": "employee",
    "pay_period_start": "start_date",
    "pay_period_end": "end_date",
    "gross_salary": "gross_pay",
    "net_salary": "net_pay",
    "pay_date": "posting_date"
}

## Test Scenario 7: Scheduled Sync Validation
### Description: Test automated synchronization schedules

SYNC_SCHEDULE_TESTS = {
    "employee_sync": {
        "frequency": "every_2_hours",
        "expected_interval": 7200,  # seconds
        "tolerance": 300  # 5 minutes tolerance
    },
    "payroll_sync": {
        "frequency": "every_3_hours", 
        "expected_interval": 10800,  # seconds
        "tolerance": 300
    },
    "accounting_sync": {
        "frequency": "every_4_hours",
        "expected_interval": 14400,  # seconds
        "tolerance": 300
    }
}

## Test Scenario 8: Integration Status Monitoring
### Description: Test system health and status monitoring

MONITORING_ENDPOINTS = {
    "health_checks": [
        "/health",
        "/integration/status",
        "/employee/status",
        "/payroll/status",
        "/accounting/status"
    ],
    "metrics_endpoints": [
        "/actuator/metrics",
        "/actuator/prometheus",
        "/actuator/health"
    ]
}

EXPECTED_METRICS = [
    "camel_route_exchanges_total",
    "camel_context_status",
    "http_server_requests_seconds_count",
    "jvm_memory_used_bytes",
    "hikaricp_connections_active"
]

## Test Scenario 9: Security Validation
### Description: Test authentication and authorization

SECURITY_TESTS = {
    "authentication": {
        "valid_credentials": {"username": "admin", "password": "admin123"},
        "invalid_credentials": {"username": "invalid", "password": "wrong"}
    },
    "authorization": {
        "admin_endpoints": ["/actuator/env", "/actuator/configprops"],
        "public_endpoints": ["/health", "/integration/status"]
    }
}

## Test Scenario 10: Disaster Recovery
### Description: Test system recovery and resilience

RESILIENCE_TESTS = {
    "database_connectivity": {
        "test_description": "Simulate database connection loss and recovery",
        "recovery_time_sla": 60  # seconds
    },
    "erp_connectivity": {
        "test_description": "Simulate ERP system unavailability", 
        "retry_attempts": 3,
        "backoff_strategy": "exponential"
    },
    "message_queue": {
        "test_description": "Test RabbitMQ resilience",
        "queue_durability": true,
        "message_persistence": true
    }
}

## Validation Functions for Testing

def validate_employee_transformation(laravel_data, frappe_data):
    """Validate employee data transformation"""
    validations = []
    
    # Check required field mappings
    for laravel_field, frappe_field in EMPLOYEE_FIELD_MAPPING.items():
        if laravel_field in laravel_data and frappe_field in frappe_data:
            laravel_value = laravel_data[laravel_field]
            frappe_value = frappe_data[frappe_field]
            
            # Handle status transformation
            if laravel_field == "status":
                expected_value = laravel_value.title()  # active -> Active
                validation = frappe_value == expected_value
            else:
                validation = str(laravel_value) == str(frappe_value)
            
            validations.append({
                "field": laravel_field,
                "passed": validation,
                "laravel_value": laravel_value,
                "frappe_value": frappe_value
            })
    
    return validations

def validate_payroll_calculation(payroll_data):
    """Validate payroll calculation accuracy"""
    validations = []
    
    # Check gross salary calculation
    allowances_total = sum(payroll_data.get("allowances", {}).values())
    calculated_gross = payroll_data.get("basic_salary", 0) + allowances_total
    actual_gross = payroll_data.get("gross_salary", 0)
    
    validations.append({
        "check": "gross_salary_calculation",
        "passed": abs(calculated_gross - actual_gross) < 0.01,
        "calculated": calculated_gross,
        "actual": actual_gross
    })
    
    # Check net salary calculation
    deductions_total = sum(payroll_data.get("deductions", {}).values())
    calculated_net = actual_gross - deductions_total
    actual_net = payroll_data.get("net_salary", 0)
    
    validations.append({
        "check": "net_salary_calculation", 
        "passed": abs(calculated_net - actual_net) < 0.01,
        "calculated": calculated_net,
        "actual": actual_net
    })
    
    return validations

def validate_accounting_entry_balance(journal_entry):
    """Validate accounting journal entry balance"""
    total_debits = sum(entry.get("debit", 0) for entry in journal_entry.get("entries", []))
    total_credits = sum(entry.get("credit", 0) for entry in journal_entry.get("entries", []))
    
    return {
        "balanced": abs(total_debits - total_credits) < 0.01,
        "total_debits": total_debits,
        "total_credits": total_credits,
        "difference": total_debits - total_credits
    }

## Performance Benchmarks

PERFORMANCE_BENCHMARKS = {
    "response_time": {
        "health_endpoint": {"max": 1.0, "target": 0.5},  # seconds
        "sync_trigger": {"max": 5.0, "target": 2.0},
        "status_endpoint": {"max": 2.0, "target": 1.0}
    },
    "throughput": {
        "employee_records_per_minute": {"min": 100, "target": 500},
        "payroll_records_per_minute": {"min": 50, "target": 200},
        "accounting_entries_per_minute": {"min": 75, "target": 300}
    },
    "resource_usage": {
        "memory_heap_max": 1024,  # MB
        "cpu_usage_max": 80,      # percentage
        "database_connections_max": 20
    }
}

## Test Execution Priority

TEST_EXECUTION_ORDER = [
    "infrastructure_health",
    "database_connectivity", 
    "service_availability",
    "camel_routes_status",
    "api_endpoints",
    "data_transformation",
    "scheduled_sync",
    "performance_baseline",
    "error_handling",
    "security_validation",
    "monitoring_integration",
    "resilience_testing"
]