<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ERP Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for ERP system integration using Apache Camel
    |
    */

    'service_url' => env('INTEGRATION_SERVICE_URL', 'http://localhost:8083/integration'),
    'api_key' => env('INTEGRATION_API_KEY', 'default-api-key'),
    'timeout' => env('INTEGRATION_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Synchronization Settings
    |--------------------------------------------------------------------------
    */
    'sync' => [
        'employee' => [
            'enabled' => env('EMPLOYEE_SYNC_ENABLED', true),
            'batch_size' => env('EMPLOYEE_BATCH_SIZE', 100),
            'schedule' => env('EMPLOYEE_SYNC_SCHEDULE', '0 2 * * *'), // Daily at 2 AM
        ],
        'payroll' => [
            'enabled' => env('PAYROLL_SYNC_ENABLED', true),
            'batch_size' => env('PAYROLL_BATCH_SIZE', 50),
            'schedule' => env('PAYROLL_SYNC_SCHEDULE', '0 3 * * *'), // Daily at 3 AM
        ],
        'accounting' => [
            'enabled' => env('ACCOUNTING_SYNC_ENABLED', true),
            'batch_size' => env('ACCOUNTING_BATCH_SIZE', 200),
            'schedule' => env('ACCOUNTING_SYNC_SCHEDULE', '0 4 * * *'), // Daily at 4 AM
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ERP System Configuration
    |--------------------------------------------------------------------------
    */
    'erp' => [
        'frappe' => [
            'enabled' => env('FRAPPE_ENABLED', true),
            'base_url' => env('FRAPPE_BASE_URL', 'http://localhost:8000'),
            'api_key' => env('FRAPPE_API_KEY'),
            'api_secret' => env('FRAPPE_API_SECRET'),
            'timeout' => env('FRAPPE_TIMEOUT', 30000),
            'retry_attempts' => env('FRAPPE_RETRY_ATTEMPTS', 3),
        ],
        'generic' => [
            'enabled' => env('GENERIC_ERP_ENABLED', false),
            'base_url' => env('GENERIC_ERP_BASE_URL'),
            'auth_type' => env('GENERIC_ERP_AUTH_TYPE', 'bearer'), // bearer, basic, api-key
            'token' => env('GENERIC_ERP_TOKEN'),
            'username' => env('GENERIC_ERP_USERNAME'),
            'password' => env('GENERIC_ERP_PASSWORD'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'provider' => env('QUEUE_PROVIDER', 'rabbitmq'), // rabbitmq, activemq
        'rabbitmq' => [
            'host' => env('RABBITMQ_HOST', 'localhost'),
            'port' => env('RABBITMQ_PORT', 5672),
            'username' => env('RABBITMQ_USERNAME', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'exchange' => env('RABBITMQ_EXCHANGE', 'erp.exchange'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Mapping Configuration
    |--------------------------------------------------------------------------
    */
    'mapping' => [
        'employee' => [
            'fields' => [
                'employee_id' => 'employee_number',
                'full_name' => 'employee_name',
                'email' => 'personal_email',
                'phone' => 'cell_number',
                'position' => 'designation',
                'hire_date' => 'date_of_joining',
                'status' => 'status',
            ],
        ],
        'payroll' => [
            'fields' => [
                'employee_id' => 'employee',
                'pay_period' => 'posting_date',
                'gross_pay' => 'gross_pay',
                'net_pay' => 'net_pay',
                'total_deductions' => 'total_deduction',
            ],
        ],
        'accounting' => [
            'fields' => [
                'account_code' => 'account',
                'account_name' => 'account_name',
                'account_type' => 'account_type',
                'balance' => 'balance',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Logging
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('INTEGRATION_MONITORING_ENABLED', true),
        'log_level' => env('INTEGRATION_LOG_LEVEL', 'info'),
        'metrics_enabled' => env('INTEGRATION_METRICS_ENABLED', true),
        'health_check_interval' => env('INTEGRATION_HEALTH_CHECK_INTERVAL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'encryption_enabled' => env('INTEGRATION_ENCRYPTION_ENABLED', true),
        'api_rate_limit' => env('INTEGRATION_RATE_LIMIT', 100), // requests per minute
        'allowed_ips' => env('INTEGRATION_ALLOWED_IPS', '*'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    */
    'error_handling' => [
        'retry_attempts' => env('INTEGRATION_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('INTEGRATION_RETRY_DELAY', 5000), // milliseconds
        'dead_letter_enabled' => env('INTEGRATION_DEAD_LETTER_ENABLED', true),
        'error_notification_enabled' => env('INTEGRATION_ERROR_NOTIFICATION_ENABLED', true),
    ],
];