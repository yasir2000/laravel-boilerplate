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
    
    // Multi-LLM Configuration
    'llm_providers' => [
        'default' => env('AI_DEFAULT_LLM_PROVIDER', 'openai'),
        
        'providers' => [
            'openai' => [
                'enabled' => env('OPENAI_ENABLED', true),
                'api_key' => env('OPENAI_API_KEY', ''),
                'api_base' => env('OPENAI_API_BASE', 'https://api.openai.com/v1'),
                'models' => [
                    'gpt-4o' => [
                        'name' => 'gpt-4o',
                        'context_length' => 128000,
                        'cost_per_1k_tokens' => ['input' => 0.005, 'output' => 0.015],
                        'capabilities' => ['text', 'vision', 'function_calling', 'json_mode']
                    ],
                    'gpt-4o-mini' => [
                        'name' => 'gpt-4o-mini',
                        'context_length' => 128000,
                        'cost_per_1k_tokens' => ['input' => 0.00015, 'output' => 0.0006],
                        'capabilities' => ['text', 'vision', 'function_calling', 'json_mode']
                    ],
                    'gpt-3.5-turbo' => [
                        'name' => 'gpt-3.5-turbo',
                        'context_length' => 16384,
                        'cost_per_1k_tokens' => ['input' => 0.001, 'output' => 0.002],
                        'capabilities' => ['text', 'function_calling', 'json_mode']
                    ]
                ],
                'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
                'timeout' => 60,
                'max_retries' => 3
            ],
            
            'anthropic' => [
                'enabled' => env('ANTHROPIC_ENABLED', false),
                'api_key' => env('ANTHROPIC_API_KEY', ''),
                'api_base' => env('ANTHROPIC_API_BASE', 'https://api.anthropic.com/v1'),
                'models' => [
                    'claude-3-5-sonnet-20241022' => [
                        'name' => 'claude-3-5-sonnet-20241022',
                        'context_length' => 200000,
                        'cost_per_1k_tokens' => ['input' => 0.003, 'output' => 0.015],
                        'capabilities' => ['text', 'vision', 'tool_use', 'json_mode']
                    ],
                    'claude-3-5-haiku-20241022' => [
                        'name' => 'claude-3-5-haiku-20241022',
                        'context_length' => 200000,
                        'cost_per_1k_tokens' => ['input' => 0.0008, 'output' => 0.004],
                        'capabilities' => ['text', 'vision', 'tool_use', 'json_mode']
                    ],
                    'claude-3-opus-20240229' => [
                        'name' => 'claude-3-opus-20240229',
                        'context_length' => 200000,
                        'cost_per_1k_tokens' => ['input' => 0.015, 'output' => 0.075],
                        'capabilities' => ['text', 'vision', 'tool_use', 'json_mode']
                    ]
                ],
                'default_model' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-3-5-haiku-20241022'),
                'timeout' => 60,
                'max_retries' => 3
            ],
            
            'google' => [
                'enabled' => env('GOOGLE_AI_ENABLED', false),
                'api_key' => env('GOOGLE_AI_API_KEY', ''),
                'api_base' => env('GOOGLE_AI_API_BASE', 'https://generativelanguage.googleapis.com/v1beta'),
                'models' => [
                    'gemini-1.5-pro' => [
                        'name' => 'gemini-1.5-pro',
                        'context_length' => 2097152,
                        'cost_per_1k_tokens' => ['input' => 0.00125, 'output' => 0.005],
                        'capabilities' => ['text', 'vision', 'function_calling', 'json_mode']
                    ],
                    'gemini-1.5-flash' => [
                        'name' => 'gemini-1.5-flash',
                        'context_length' => 1048576,
                        'cost_per_1k_tokens' => ['input' => 0.000075, 'output' => 0.0003],
                        'capabilities' => ['text', 'vision', 'function_calling', 'json_mode']
                    ]
                ],
                'default_model' => env('GOOGLE_AI_DEFAULT_MODEL', 'gemini-1.5-flash'),
                'timeout' => 60,
                'max_retries' => 3
            ],
            
            'mistral' => [
                'enabled' => env('MISTRAL_ENABLED', false),
                'api_key' => env('MISTRAL_API_KEY', ''),
                'api_base' => env('MISTRAL_API_BASE', 'https://api.mistral.ai/v1'),
                'models' => [
                    'mistral-large-latest' => [
                        'name' => 'mistral-large-latest',
                        'context_length' => 128000,
                        'cost_per_1k_tokens' => ['input' => 0.004, 'output' => 0.012],
                        'capabilities' => ['text', 'function_calling', 'json_mode']
                    ],
                    'mistral-small-latest' => [
                        'name' => 'mistral-small-latest',
                        'context_length' => 128000,
                        'cost_per_1k_tokens' => ['input' => 0.001, 'output' => 0.003],
                        'capabilities' => ['text', 'function_calling', 'json_mode']
                    ]
                ],
                'default_model' => env('MISTRAL_DEFAULT_MODEL', 'mistral-small-latest'),
                'timeout' => 60,
                'max_retries' => 3
            ],
            
            'ollama' => [
                'enabled' => env('OLLAMA_ENABLED', false),
                'api_base' => env('OLLAMA_API_BASE', 'http://localhost:11434'),
                'models' => [
                    'llama3.2:latest' => [
                        'name' => 'llama3.2:latest',
                        'context_length' => 131072,
                        'cost_per_1k_tokens' => ['input' => 0.0, 'output' => 0.0], // Local model - no API costs
                        'capabilities' => ['text', 'function_calling'],
                        'local' => true
                    ],
                    'llama3.2:3b' => [
                        'name' => 'llama3.2:3b',
                        'context_length' => 131072,
                        'cost_per_1k_tokens' => ['input' => 0.0, 'output' => 0.0],
                        'capabilities' => ['text'],
                        'local' => true
                    ],
                    'llama3.1:8b' => [
                        'name' => 'llama3.1:8b',
                        'context_length' => 131072,
                        'cost_per_1k_tokens' => ['input' => 0.0, 'output' => 0.0],
                        'capabilities' => ['text', 'function_calling'],
                        'local' => true
                    ],
                    'llama3.1:70b' => [
                        'name' => 'llama3.1:70b',
                        'context_length' => 131072,
                        'cost_per_1k_tokens' => ['input' => 0.0, 'output' => 0.0],
                        'capabilities' => ['text', 'function_calling'],
                        'local' => true
                    ],
                    'codellama:latest' => [
                        'name' => 'codellama:latest',
                        'context_length' => 16384,
                        'cost_per_1k_tokens' => ['input' => 0.0, 'output' => 0.0],
                        'capabilities' => ['text', 'code_generation'],
                        'local' => true
                    ],
                    'mistral:latest' => [
                        'name' => 'mistral:latest',
                        'context_length' => 32768,
                        'cost_per_1k_tokens' => ['input' => 0.0, 'output' => 0.0],
                        'capabilities' => ['text', 'function_calling'],
                        'local' => true
                    ],
                    'neural-chat:latest' => [
                        'name' => 'neural-chat:latest',
                        'context_length' => 4096,
                        'cost_per_1k_tokens' => ['input' => 0.0, 'output' => 0.0],
                        'capabilities' => ['text'],
                        'local' => true
                    ],
                    'phi3:latest' => [
                        'name' => 'phi3:latest',
                        'context_length' => 131072,
                        'cost_per_1k_tokens' => ['input' => 0.0, 'output' => 0.0],
                        'capabilities' => ['text', 'reasoning'],
                        'local' => true
                    ]
                ],
                'default_model' => env('OLLAMA_DEFAULT_MODEL', 'llama3.2:latest'),
                'timeout' => 120,
                'max_retries' => 2,
                'auto_pull' => env('OLLAMA_AUTO_PULL', true), // Automatically pull models if not available
                'keep_alive' => env('OLLAMA_KEEP_ALIVE', '5m') // Keep model loaded for 5 minutes
            ]
        ],
        
        // Agent-specific LLM assignments
        'agent_llm_mapping' => [
            'hr_agent' => [
                'primary' => env('HR_AGENT_PRIMARY_LLM', 'openai:gpt-4o-mini'),
                'fallback' => env('HR_AGENT_FALLBACK_LLM', 'ollama:llama3.2:latest'),
                'use_case' => 'conversational'
            ],
            'project_agent' => [
                'primary' => env('PROJECT_AGENT_PRIMARY_LLM', 'anthropic:claude-3-5-haiku-20241022'),
                'fallback' => env('PROJECT_AGENT_FALLBACK_LLM', 'ollama:llama3.1:8b'),
                'use_case' => 'analytical'
            ],
            'analytics_agent' => [
                'primary' => env('ANALYTICS_AGENT_PRIMARY_LLM', 'google:gemini-1.5-flash'),
                'fallback' => env('ANALYTICS_AGENT_FALLBACK_LLM', 'ollama:llama3.1:70b'),
                'use_case' => 'data_analysis'
            ],
            'workflow_agent' => [
                'primary' => env('WORKFLOW_AGENT_PRIMARY_LLM', 'openai:gpt-4o'),
                'fallback' => env('WORKFLOW_AGENT_FALLBACK_LLM', 'ollama:mistral:latest'),
                'use_case' => 'process_management'
            ],
            'integration_agent' => [
                'primary' => env('INTEGRATION_AGENT_PRIMARY_LLM', 'mistral:mistral-small-latest'),
                'fallback' => env('INTEGRATION_AGENT_FALLBACK_LLM', 'ollama:codellama:latest'),
                'use_case' => 'technical'
            ],
            'notification_agent' => [
                'primary' => env('NOTIFICATION_AGENT_PRIMARY_LLM', 'openai:gpt-3.5-turbo'),
                'fallback' => env('NOTIFICATION_AGENT_FALLBACK_LLM', 'ollama:neural-chat:latest'),
                'use_case' => 'communication'
            ]
        ],
        
        // Load balancing and failover
        'load_balancing' => [
            'enabled' => env('LLM_LOAD_BALANCING', true),
            'strategy' => env('LLM_LOAD_BALANCE_STRATEGY', 'round_robin'), // round_robin, least_cost, fastest_response
            'health_check_interval' => 300, // 5 minutes
            'failover_enabled' => env('LLM_FAILOVER_ENABLED', true),
            'max_failures_before_failover' => 3
        ],
        
        // Cost management
        'cost_management' => [
            'enabled' => env('LLM_COST_TRACKING', true),
            'daily_budget_limit' => env('LLM_DAILY_BUDGET', 50.0), // USD
            'monthly_budget_limit' => env('LLM_MONTHLY_BUDGET', 1000.0), // USD
            'cost_alerts' => [
                'thresholds' => [75, 90, 100], // Percentage of budget
                'recipients' => ['admin@company.com']
            ]
        ],
        
        // Performance optimization
        'performance' => [
            'caching' => [
                'enabled' => env('LLM_CACHING', true),
                'ttl' => env('LLM_CACHE_TTL', 3600), // 1 hour
                'similarity_threshold' => 0.95 // Cache hit threshold for similar requests
            ],
            'batching' => [
                'enabled' => env('LLM_BATCHING', true),
                'max_batch_size' => env('LLM_MAX_BATCH_SIZE', 10),
                'batch_timeout' => env('LLM_BATCH_TIMEOUT', 5) // seconds
            ]
        ]
    ],
    
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