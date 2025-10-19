<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Agents Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the CrewAI collaborative agent system
    |
    */

    // Base URL for the AI agent service
    'base_url' => env('AI_AGENTS_BASE_URL', 'http://localhost:8001'),
    
    // Request timeout in seconds
    'timeout' => env('AI_AGENTS_TIMEOUT', 30),
    
    // Enable/disable AI agent features
    'enabled' => env('AI_AGENTS_ENABLED', true),
    
    // API authentication
    'api_token' => env('AI_AGENTS_API_TOKEN', ''),
    
    // Automatic workflow triggers
    'auto_workflows' => [
        'employee_onboarding' => env('AI_AUTO_EMPLOYEE_ONBOARDING', true),
        'leave_request_processing' => env('AI_AUTO_LEAVE_PROCESSING', true),
        'project_optimization' => env('AI_AUTO_PROJECT_OPTIMIZATION', false),
        'analytics_generation' => env('AI_AUTO_ANALYTICS', true),
    ],
    
    // Agent-specific configurations
    'agents' => [
        'hr_agent' => [
            'enabled' => true,
            'auto_onboarding' => true,
            'auto_leave_approval' => false, // Requires manual approval
            'notification_channels' => ['email', 'database'],
        ],
        
        'project_agent' => [
            'enabled' => true,
            'auto_task_assignment' => false,
            'resource_optimization_threshold' => 80, // Percentage
            'notification_channels' => ['email', 'database'],
        ],
        
        'analytics_agent' => [
            'enabled' => true,
            'auto_report_generation' => true,
            'report_schedule' => 'daily', // daily, weekly, monthly
            'cache_duration' => 1800, // 30 minutes
        ],
        
        'workflow_agent' => [
            'enabled' => true,
            'auto_workflow_creation' => true,
            'max_approval_levels' => 3,
            'default_timeout' => 72, // hours
        ],
        
        'integration_agent' => [
            'enabled' => true,
            'sync_interval' => 300, // 5 minutes
            'retry_attempts' => 3,
            'health_check_interval' => 60, // 1 minute
        ],
        
        'notification_agent' => [
            'enabled' => true,
            'batch_size' => 100,
            'delivery_retry_attempts' => 3,
            'priority_channels' => [
                'urgent' => ['sms', 'email'],
                'high' => ['email', 'database'],
                'normal' => ['database'],
                'low' => ['database']
            ]
        ]
    ],
    
    // Workflow configurations
    'workflows' => [
        'employee_onboarding' => [
            'steps' => [
                ['name' => 'Document Collection', 'role' => 'hr', 'timeout' => 24],
                ['name' => 'System Account Setup', 'role' => 'it', 'timeout' => 8],
                ['name' => 'Orientation Scheduling', 'role' => 'hr', 'timeout' => 48],
                ['name' => 'Equipment Assignment', 'role' => 'facilities', 'timeout' => 24]
            ],
            'notifications' => [
                'start' => ['employee', 'hr', 'manager'],
                'completion' => ['employee', 'hr'],
                'timeout' => ['hr', 'admin']
            ]
        ],
        
        'leave_request' => [
            'steps' => [
                ['name' => 'Manager Approval', 'role' => 'manager', 'timeout' => 48],
                ['name' => 'HR Review', 'role' => 'hr', 'timeout' => 24]
            ],
            'auto_approve_conditions' => [
                'sick_leave' => ['max_days' => 3],
                'personal_leave' => ['max_days' => 1, 'advance_notice' => 24]
            ]
        ],
        
        'project_planning' => [
            'steps' => [
                ['name' => 'Resource Analysis', 'role' => 'project_manager', 'timeout' => 8],
                ['name' => 'Task Breakdown', 'role' => 'project_manager', 'timeout' => 16],
                ['name' => 'Team Assignment', 'role' => 'project_manager', 'timeout' => 8],
                ['name' => 'Timeline Creation', 'role' => 'project_manager', 'timeout' => 8]
            ]
        ]
    ],
    
    // Analytics and reporting
    'analytics' => [
        'cache_duration' => 1800, // 30 minutes
        'auto_reports' => [
            'employee_summary' => [
                'schedule' => 'weekly',
                'recipients' => ['hr@company.com'],
                'format' => 'json'
            ],
            'project_status' => [
                'schedule' => 'daily', 
                'recipients' => ['pm@company.com'],
                'format' => 'json'
            ],
            'attendance_analysis' => [
                'schedule' => 'monthly',
                'recipients' => ['hr@company.com', 'admin@company.com'],
                'format' => 'pdf'
            ]
        ]
    ],
    
    // Integration settings
    'integrations' => [
        'external_hr_system' => [
            'enabled' => false,
            'sync_frequency' => 'hourly',
            'endpoints' => [
                'employees' => '/api/employees',
                'departments' => '/api/departments'
            ]
        ],
        
        'payroll_system' => [
            'enabled' => false,
            'sync_frequency' => 'daily',
            'endpoints' => [
                'payroll' => '/api/payroll',
                'timesheets' => '/api/timesheets'
            ]
        ]
    ],
    
    // Monitoring and logging
    'monitoring' => [
        'health_check_interval' => 300, // 5 minutes
        'performance_tracking' => true,
        'error_reporting' => true,
        'metrics_retention' => 30, // days
        'log_level' => env('AI_AGENTS_LOG_LEVEL', 'info'),
    ],
    
    // Security settings
    'security' => [
        'rate_limiting' => [
            'requests_per_minute' => 60,
            'burst_limit' => 10
        ],
        'allowed_ips' => [
            '127.0.0.1',
            '::1'
        ],
        'encryption' => [
            'sensitive_data' => true,
            'communication' => true
        ]
    ],
    
    // Development and testing
    'development' => [
        'mock_responses' => env('AI_AGENTS_MOCK', false),
        'debug_mode' => env('AI_AGENTS_DEBUG', false),
        'test_mode' => env('AI_AGENTS_TEST_MODE', false)
    ]
];