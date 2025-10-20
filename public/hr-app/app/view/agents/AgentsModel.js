/**
 * AI Agents Model - Data model for agents dashboard
 */
Ext.define('HRApp.view.agents.AgentsModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.agents',
    
    stores: {
        // Core agents store
        CoreAgentsStore: {
            fields: [
                'id', 'name', 'type', 'status', 'active_tasks', 
                'load_percentage', 'iconCls', 'last_activity'
            ],
            data: []
        },
        
        // Specialized agents store
        SpecializedAgentsStore: {
            fields: [
                'id', 'name', 'type', 'status', 'queue_size', 
                'completed_today', 'iconCls', 'specialization'
            ],
            data: []
        },
        
        // Active workflows store
        ActiveWorkflowsStore: {
            fields: [
                'workflow_id', 'workflow_type', 'employee_id', 'employee_name',
                'status', 'progress_percentage', 'started_at', 'estimated_completion',
                'current_step', 'assigned_agents', 'priority'
            ],
            data: []
        },
        
        // Workflow templates store
        WorkflowTemplatesStore: {
            fields: [
                'template_id', 'name', 'description', 'iconCls',
                'agents_count', 'steps_count', 'estimated_duration',
                'success_rate', 'last_used'
            ],
            data: []
        },
        
        // Activity feed store
        ActivityFeedStore: {
            fields: [
                'id', 'timestamp', 'type', 'title', 'description',
                'agent_name', 'workflow_id', 'iconCls', 'severity'
            ],
            data: []
        },
        
        // Agent types store for configuration
        AgentTypesStore: {
            fields: ['type', 'name', 'category'],
            data: [
                { type: 'hr_agent', name: 'HR Management Agent', category: 'core' },
                { type: 'project_manager_agent', name: 'Project Management Agent', category: 'core' },
                { type: 'analytics_agent', name: 'Analytics Agent', category: 'core' },
                { type: 'workflow_engine_agent', name: 'Workflow Engine Agent', category: 'core' },
                { type: 'integration_agent', name: 'Integration Agent', category: 'core' },
                { type: 'notification_agent', name: 'Notification Agent', category: 'core' },
                { type: 'it_support_agent', name: 'IT Support Agent', category: 'specialized' },
                { type: 'compliance_agent', name: 'Compliance & Audit Agent', category: 'specialized' },
                { type: 'training_agent', name: 'Training & Development Agent', category: 'specialized' },
                { type: 'payroll_agent', name: 'Payroll Processing Agent', category: 'specialized' },
                { type: 'leave_processing_agent', name: 'Leave Processing Agent', category: 'specialized' },
                { type: 'coverage_agent', name: 'Coverage Management Agent', category: 'specialized' }
            ]
        },
        
        // Workflow performance metrics store
        WorkflowPerformanceStore: {
            fields: [
                'workflow_type', 'avg_completion_time', 'success_rate',
                'total_executions', 'efficiency_score'
            ],
            data: []
        },
        
        // Agent utilization store
        AgentUtilizationStore: {
            fields: [
                'agent_name', 'utilization_percentage', 'tasks_completed',
                'average_task_time', 'efficiency_rating'
            ],
            data: []
        },
        
        // Success rates store
        SuccessRatesStore: {
            fields: [
                'date', 'success_rate', 'total_workflows',
                'successful_workflows', 'failed_workflows'
            ],
            data: []
        }
    },
    
    data: {
        // Dashboard configuration
        dashboardConfig: {
            autoRefresh: true,
            refreshInterval: 30000,
            showNotifications: true,
            compactView: false
        },
        
        // System status
        systemStatus: {
            overall_health: 'healthy',
            health_percentage: 95,
            active_workflows: 0,
            healthy_agents: 0,
            total_agents: 12,
            memory_usage: 0,
            avg_response_time: 0
        },
        
        // Current user permissions
        permissions: {
            can_start_workflows: true,
            can_pause_workflows: true,
            can_configure_agents: true,
            can_emergency_shutdown: true,
            can_view_analytics: true
        }
    },
    
    formulas: {
        // Calculate agent health percentage
        agentHealthPercentage: function(get) {
            const healthy = get('systemStatus.healthy_agents');
            const total = get('systemStatus.total_agents');
            return total > 0 ? Math.round((healthy / total) * 100) : 0;
        },
        
        // Format memory usage
        formattedMemoryUsage: function(get) {
            const usage = get('systemStatus.memory_usage');
            return `${usage}%`;
        },
        
        // Format response time
        formattedResponseTime: function(get) {
            const time = get('systemStatus.avg_response_time');
            return `${time}ms`;
        },
        
        // Determine system status color
        systemStatusColor: function(get) {
            const health = get('systemStatus.health_percentage');
            if (health >= 90) return 'green';
            if (health >= 70) return 'orange';
            return 'red';
        },
        
        // Check if emergency shutdown is available
        emergencyShutdownAvailable: function(get) {
            return get('permissions.can_emergency_shutdown') && 
                   get('systemStatus.active_workflows') > 0;
        }
    }
});