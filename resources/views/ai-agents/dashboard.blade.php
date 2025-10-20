<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - AI Agents Dashboard</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- ExtJS CDN -->
    <link rel="stylesheet" type="text/css" href="https://cdn.sencha.com/ext/gpl/7.0.0/classic/theme-triton/resources/theme-triton-all.css">
    <script type="text/javascript" src="https://cdn.sencha.com/ext/gpl/7.0.0/ext-all.js"></script>
    <script type="text/javascript" src="https://cdn.sencha.com/ext/gpl/7.0.0/classic/theme-triton/theme-triton.js"></script>

    <!-- AI Agents Dashboard CSS -->
    <style>
        /* Loading spinner styles */
        .loading-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }
        
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Custom styles for AI Agents Dashboard */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Figtree', sans-serif;
        }

        #ai-agents-viewport {
            width: 100%;
            height: 100vh;
        }

        /* Override ExtJS default styles */
        .x-panel-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .x-panel-header .x-panel-header-text {
            color: white;
            font-weight: 600;
        }

        .agent-status-healthy {
            color: #10b981;
        }

        .agent-status-warning {
            color: #f59e0b;
        }

        .agent-status-error {
            color: #ef4444;
        }

        .agent-status-inactive {
            color: #6b7280;
        }

        .workflow-priority-high {
            color: #ef4444;
            font-weight: bold;
        }

        .workflow-priority-medium {
            color: #f59e0b;
        }

        .workflow-priority-low {
            color: #6b7280;
        }

        .system-health-excellent {
            color: #10b981;
        }

        .system-health-good {
            color: #84cc16;
        }

        .system-health-warning {
            color: #f59e0b;
        }

        .system-health-critical {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <!-- Loading overlay -->
    <div id="loading-overlay" class="loading-container">
        <div class="loading-spinner"></div>
    </div>

    <!-- ExtJS viewport container -->
    <div id="ai-agents-viewport"></div>

    <script>
        // Global configuration
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}',
            user: @json(auth()->user()),
            apiBaseUrl: '{{ url("/api") }}',
            pusher: {
                key: '{{ config("broadcasting.connections.pusher.key") }}',
                cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}'
            }
        };

        // ExtJS ready handler
        Ext.onReady(function() {
            // Hide loading overlay
            document.getElementById('loading-overlay').style.display = 'none';
            
            // Initialize AI Agents application
            Ext.application({
                name: 'AIAgentsApp',
                appFolder: '/hr-app/ai-agents',
                
                models: ['AgentsModel'],
                controllers: ['AgentsController'],
                views: ['AgentsDashboard', 'WorkflowStartDialog'],
                
                launch: function() {
                    // Create the main viewport
                    var dashboard = Ext.create('AIAgentsApp.view.AgentsDashboard', {
                        renderTo: 'ai-agents-viewport'
                    });
                    
                    // Initialize the controller
                    var controller = Ext.create('AIAgentsApp.controller.AgentsController');
                    controller.init();
                    
                    console.log('AI Agents Dashboard initialized successfully');
                }
            });
        });

        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('JavaScript error:', e.error);
            Ext.Msg.alert('Error', 'An unexpected error occurred. Please refresh the page and try again.');
        });

        // Handle authentication errors globally
        Ext.Ajax.on('requestexception', function(conn, response, options) {
            if (response.status === 401) {
                window.location.href = '/login';
            } else if (response.status === 403) {
                Ext.Msg.alert('Access Denied', 'You do not have permission to perform this action.');
            } else if (response.status >= 500) {
                Ext.Msg.alert('Server Error', 'A server error occurred. Please try again later.');
            }
        });
    </script>

    <!-- Load AI Agents Dashboard JavaScript files -->
    <script src="{{ asset('hr-app/ai-agents/model/AgentsModel.js') }}"></script>
    <script src="{{ asset('hr-app/ai-agents/view/AgentsDashboard.js') }}"></script>
    <script src="{{ asset('hr-app/ai-agents/view/WorkflowStartDialog.js') }}"></script>
    <script src="{{ asset('hr-app/ai-agents/controller/AgentsController.js') }}"></script>
</body>
</html>