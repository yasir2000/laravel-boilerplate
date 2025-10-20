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
        
        /* Dashboard styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Figtree', sans-serif;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .agent-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .agent-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        /* Responsive grid */
        @media (max-width: 768px) {
            .agents-grid {
                grid-template-columns: 1fr !important;
            }
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
            console.log('ExtJS is ready, initializing AI Agents Dashboard...');
            
            try {
                // Create the main viewport directly
                var viewport = Ext.create('Ext.container.Viewport', {
                    layout: 'fit',
                    items: [{
                        xtype: 'panel',
                        title: 'AI Agents Dashboard',
                        layout: 'border',
                        items: [
                            {
                                region: 'center',
                                xtype: 'panel',
                                html: '<div id="dashboard-content"><h2>Loading AI Agents Dashboard...</h2></div>',
                                listeners: {
                                    afterrender: function() {
                                        // Initialize dashboard content after panel renders
                                        initializeDashboard();
                                    }
                                }
                            }
                        ]
                    }]
                });
                
                // Hide loading overlay
                document.getElementById('loading-overlay').style.display = 'none';
                
                console.log('AI Agents Dashboard viewport created successfully');
                
            } catch (error) {
                console.error('Error creating dashboard:', error);
                document.getElementById('loading-overlay').innerHTML = '<div style="text-align: center;"><h3>Error Loading Dashboard</h3><p>' + error.message + '</p><button onclick="location.reload()">Retry</button></div>';
            }
        });

        // Initialize dashboard function
        function initializeDashboard() {
            console.log('Initializing dashboard content...');
            
            // Test API connectivity
            Ext.Ajax.request({
                url: '/api/test/agents/status',
                method: 'GET',
                success: function(response) {
                    console.log('API test successful:', response.responseText);
                    var data = Ext.decode(response.responseText);
                    
                    if (data.success) {
                        createDashboardContent(data);
                    } else {
                        showError('API returned error: ' + (data.message || 'Unknown error'));
                    }
                },
                failure: function(response) {
                    console.error('API test failed:', response);
                    showError('Failed to connect to API. Status: ' + response.status);
                }
            });
        }
        
        function createDashboardContent(data) {
            console.log('Creating dashboard content with data:', data);
            
            var html = '<div style="padding: 20px;">';
            html += '<h2><i class="fas fa-robot"></i> AI Agents System Status</h2>';
            html += '<div style="margin-bottom: 20px;">';
            html += '<div class="alert alert-success"><i class="fas fa-check-circle"></i> System is operational</div>';
            html += '</div>';
            
            // Core Agents Section
            html += '<h3><i class="fas fa-users"></i> Core Agents (' + data.core_agents.length + ')</h3>';
            html += '<div class="agents-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-bottom: 30px;">';
            
            data.core_agents.forEach(function(agent) {
                var statusClass = agent.status === 'active' ? 'success' : 'warning';
                var loadColor = agent.load_percentage > 70 ? '#e74c3c' : agent.load_percentage > 40 ? '#f39c12' : '#27ae60';
                
                html += '<div class="agent-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: #f9f9f9;">';
                html += '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                html += '<i class="fas ' + agent.iconCls + ' fa-2x" style="color: #3498db; margin-right: 15px;"></i>';
                html += '<div>';
                html += '<h4 style="margin: 0; color: #2c3e50;">' + agent.name + '</h4>';
                html += '<span class="badge badge-' + statusClass + '" style="background: ' + (statusClass === 'success' ? '#27ae60' : '#f39c12') + '; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px;">' + agent.status.toUpperCase() + '</span>';
                html += '</div></div>';
                html += '<div style="margin-bottom: 8px;"><strong>Active Tasks:</strong> ' + agent.active_tasks + '</div>';
                html += '<div style="margin-bottom: 8px;"><strong>Load:</strong> ' + agent.load_percentage + '%</div>';
                html += '<div style="background: #ecf0f1; height: 8px; border-radius: 4px; overflow: hidden;">';
                html += '<div style="background: ' + loadColor + '; height: 100%; width: ' + agent.load_percentage + '%; transition: width 0.3s;"></div></div>';
                html += '</div>';
            });
            
            html += '</div>';
            
            // Specialized Agents Section
            html += '<h3><i class="fas fa-cogs"></i> Specialized Agents (' + data.specialized_agents.length + ')</h3>';
            html += '<div class="agents-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-bottom: 30px;">';
            
            data.specialized_agents.forEach(function(agent) {
                var statusClass = agent.status === 'active' ? 'success' : 'warning';
                
                html += '<div class="agent-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: #f9f9f9;">';
                html += '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
                html += '<i class="fas ' + agent.iconCls + ' fa-2x" style="color: #9b59b6; margin-right: 15px;"></i>';
                html += '<div>';
                html += '<h4 style="margin: 0; color: #2c3e50;">' + agent.name + '</h4>';
                html += '<span class="badge badge-' + statusClass + '" style="background: ' + (statusClass === 'success' ? '#27ae60' : '#f39c12') + '; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px;">' + agent.status.toUpperCase() + '</span>';
                html += '</div></div>';
                html += '<div style="margin-bottom: 8px;"><strong>Queue Size:</strong> ' + agent.queue_size + '</div>';
                html += '<div style="margin-bottom: 8px;"><strong>Completed Today:</strong> ' + agent.completed_today + '</div>';
                html += '<div style="margin-bottom: 8px;"><strong>Specialization:</strong> ' + agent.specialization + '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            
            // Action Buttons
            html += '<div style="text-align: center; padding: 20px;">';
            html += '<button onclick="refreshDashboard()" style="background: #3498db; color: white; border: none; padding: 12px 24px; border-radius: 6px; margin: 0 10px; cursor: pointer;"><i class="fas fa-sync-alt"></i> Refresh Status</button>';
            html += '<button onclick="viewSystemHealth()" style="background: #27ae60; color: white; border: none; padding: 12px 24px; border-radius: 6px; margin: 0 10px; cursor: pointer;"><i class="fas fa-heartbeat"></i> System Health</button>';
            html += '<button onclick="viewActiveWorkflows()" style="background: #f39c12; color: white; border: none; padding: 12px 24px; border-radius: 6px; margin: 0 10px; cursor: pointer;"><i class="fas fa-tasks"></i> Active Workflows</button>';
            html += '</div>';
            
            html += '</div>';
            
            // Update the dashboard content
            var contentDiv = document.getElementById('dashboard-content');
            if (contentDiv) {
                contentDiv.innerHTML = html;
            } else {
                // Find the center panel and update its content
                var centerPanel = Ext.ComponentQuery.query('panel[region=center]')[0];
                if (centerPanel) {
                    centerPanel.update(html);
                }
            }
        }
        
        function showError(message) {
            var html = '<div style="padding: 40px; text-align: center;">';
            html += '<i class="fas fa-exclamation-triangle fa-3x" style="color: #e74c3c; margin-bottom: 20px;"></i>';
            html += '<h3 style="color: #e74c3c;">Dashboard Error</h3>';
            html += '<p>' + message + '</p>';
            html += '<button onclick="location.reload()" style="background: #3498db; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;"><i class="fas fa-redo"></i> Retry</button>';
            html += '</div>';
            
            var contentDiv = document.getElementById('dashboard-content');
            if (contentDiv) {
                contentDiv.innerHTML = html;
            }
        }
        
        // Dashboard action functions
        function refreshDashboard() {
            console.log('Refreshing dashboard...');
            showLoadingMessage('Refreshing agent status...');
            initializeDashboard();
        }
        
        function viewSystemHealth() {
            showLoadingMessage('Loading system health...');
            Ext.Ajax.request({
                url: '/api/test/system/health',
                method: 'GET',
                success: function(response) {
                    var data = Ext.decode(response.responseText);
                    alert('System Health: ' + data.health_data.overall_health + ' (' + data.health_data.health_percentage + '%)');
                    initializeDashboard(); // Refresh main view
                },
                failure: function() {
                    alert('Failed to load system health data');
                    initializeDashboard(); // Refresh main view
                }
            });
        }
        
        function viewActiveWorkflows() {
            showLoadingMessage('Loading active workflows...');
            Ext.Ajax.request({
                url: '/api/test/workflows/active',
                method: 'GET',
                success: function(response) {
                    var data = Ext.decode(response.responseText);
                    var count = data.workflows ? data.workflows.length : 0;
                    alert('Active Workflows: ' + count + ' currently running');
                    initializeDashboard(); // Refresh main view
                },
                failure: function() {
                    alert('Failed to load active workflows data');
                    initializeDashboard(); // Refresh main view
                }
            });
        }
        
        function showLoadingMessage(message) {
            var contentDiv = document.getElementById('dashboard-content');
            if (contentDiv) {
                contentDiv.innerHTML = '<div style="padding: 40px; text-align: center;"><i class="fas fa-spinner fa-spin fa-2x" style="color: #3498db;"></i><p style="margin-top: 20px;">' + message + '</p></div>';
            }
        }

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
    <script src="{{ asset('hr-app/app/view/agents/AgentsModel.js') }}"></script>
    <script src="{{ asset('hr-app/app/view/agents/AgentsDashboard.js') }}"></script>
    <script src="{{ asset('hr-app/app/view/agents/WorkflowStartDialog.js') }}"></script>
    <script src="{{ asset('hr-app/app/view/agents/AgentsController.js') }}"></script>
</body>
</html>