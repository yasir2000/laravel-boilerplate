-- Database initialization script for ERP Integration Service
-- This script sets up the basic tables and indexes needed for the integration service

-- Create extension for UUID generation
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Create integration_logs table for storing sync operation logs
CREATE TABLE IF NOT EXISTS integration_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    operation_type VARCHAR(50) NOT NULL,
    operation_status VARCHAR(20) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_count INTEGER DEFAULT 0,
    error_message TEXT,
    sync_start_time TIMESTAMP WITH TIME ZONE NOT NULL,
    sync_end_time TIMESTAMP WITH TIME ZONE,
    duration_ms BIGINT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_integration_logs_operation_type ON integration_logs(operation_type);
CREATE INDEX IF NOT EXISTS idx_integration_logs_status ON integration_logs(operation_status);
CREATE INDEX IF NOT EXISTS idx_integration_logs_entity_type ON integration_logs(entity_type);
CREATE INDEX IF NOT EXISTS idx_integration_logs_created_at ON integration_logs(created_at);

-- Create sync_status table for tracking last sync times
CREATE TABLE IF NOT EXISTS sync_status (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    sync_type VARCHAR(50) NOT NULL UNIQUE,
    last_sync_time TIMESTAMP WITH TIME ZONE,
    last_successful_sync TIMESTAMP WITH TIME ZONE,
    status VARCHAR(20) DEFAULT 'idle',
    next_scheduled_sync TIMESTAMP WITH TIME ZONE,
    configuration JSONB,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Insert default sync status records
INSERT INTO sync_status (sync_type, status) VALUES 
    ('employee', 'idle'),
    ('payroll', 'idle'),
    ('accounting', 'idle')
ON CONFLICT (sync_type) DO NOTHING;

-- Create error_queue table for dead letter queue
CREATE TABLE IF NOT EXISTS error_queue (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    error_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50),
    entity_id VARCHAR(100),
    error_message TEXT NOT NULL,
    stack_trace TEXT,
    original_payload JSONB,
    retry_count INTEGER DEFAULT 0,
    max_retries INTEGER DEFAULT 3,
    next_retry_time TIMESTAMP WITH TIME ZONE,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP WITH TIME ZONE
);

-- Create indexes for error_queue
CREATE INDEX IF NOT EXISTS idx_error_queue_status ON error_queue(status);
CREATE INDEX IF NOT EXISTS idx_error_queue_error_type ON error_queue(error_type);
CREATE INDEX IF NOT EXISTS idx_error_queue_next_retry ON error_queue(next_retry_time);

-- Create data_mapping table for storing field mappings
CREATE TABLE IF NOT EXISTS data_mapping (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    source_system VARCHAR(50) NOT NULL,
    target_system VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    source_field VARCHAR(100) NOT NULL,
    target_field VARCHAR(100) NOT NULL,
    transformation_rule TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create unique constraint for data mappings
CREATE UNIQUE INDEX IF NOT EXISTS idx_data_mapping_unique 
ON data_mapping(source_system, target_system, entity_type, source_field);

-- Insert default employee mappings
INSERT INTO data_mapping (source_system, target_system, entity_type, source_field, target_field) VALUES 
    ('laravel', 'frappe', 'employee', 'employee_id', 'employee_number'),
    ('laravel', 'frappe', 'employee', 'full_name', 'employee_name'),
    ('laravel', 'frappe', 'employee', 'email', 'personal_email'),
    ('laravel', 'frappe', 'employee', 'phone', 'cell_number'),
    ('laravel', 'frappe', 'employee', 'position', 'designation'),
    ('laravel', 'frappe', 'employee', 'department', 'department'),
    ('laravel', 'frappe', 'employee', 'hire_date', 'date_of_joining'),
    ('laravel', 'frappe', 'employee', 'status', 'status')
ON CONFLICT DO NOTHING;

-- Insert default payroll mappings
INSERT INTO data_mapping (source_system, target_system, entity_type, source_field, target_field) VALUES 
    ('laravel', 'frappe', 'payroll', 'employee_id', 'employee'),
    ('laravel', 'frappe', 'payroll', 'pay_period', 'posting_date'),
    ('laravel', 'frappe', 'payroll', 'gross_pay', 'gross_pay'),
    ('laravel', 'frappe', 'payroll', 'net_pay', 'net_pay'),
    ('laravel', 'frappe', 'payroll', 'total_deductions', 'total_deduction')
ON CONFLICT DO NOTHING;

-- Create configuration table for system settings
CREATE TABLE IF NOT EXISTS integration_config (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT NOT NULL,
    config_type VARCHAR(20) DEFAULT 'string',
    description TEXT,
    is_encrypted BOOLEAN DEFAULT false,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Insert default configuration
INSERT INTO integration_config (config_key, config_value, description) VALUES 
    ('employee.batch_size', '50', 'Default batch size for employee synchronization'),
    ('payroll.batch_size', '25', 'Default batch size for payroll synchronization'),
    ('accounting.batch_size', '100', 'Default batch size for accounting synchronization'),
    ('retry.max_attempts', '3', 'Maximum retry attempts for failed operations'),
    ('retry.delay_ms', '5000', 'Delay between retry attempts in milliseconds')
ON CONFLICT (config_key) DO NOTHING;

-- Create metrics table for storing performance metrics
CREATE TABLE IF NOT EXISTS integration_metrics (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2) NOT NULL,
    metric_type VARCHAR(20) NOT NULL, -- counter, gauge, histogram
    tags JSONB,
    timestamp TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for metrics
CREATE INDEX IF NOT EXISTS idx_integration_metrics_name ON integration_metrics(metric_name);
CREATE INDEX IF NOT EXISTS idx_integration_metrics_timestamp ON integration_metrics(timestamp);
CREATE INDEX IF NOT EXISTS idx_integration_metrics_tags ON integration_metrics USING GIN(tags);

-- Grant permissions to integration user
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO integration_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO integration_user;