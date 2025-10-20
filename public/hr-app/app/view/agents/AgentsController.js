/**
 * AI Agents Controller - Handles all agent-related interactions
 */
Ext.define('HRApp.view.agents.AgentsController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.agents',
    
    // Initialize the controller
    init: function() {
        this.callParent();
        this.loadAgentsData();
        this.startAutoRefresh();
    },
    
    // Load initial agents data
    loadAgentsData: function() {
        this.loadAgentsStatus();
        this.loadActiveWorkflows();
        this.loadActivityFeed();
        this.loadSystemHealth();
        this.loadWorkflowTemplates();
    },
    
    // Load agents status from backend
    loadAgentsStatus: function() {
        Ext.Ajax.request({
            url: '/api/agents/status',
            method: 'GET',
            success: function(response) {
                const data = Ext.decode(response.responseText);
                
                if (data.success) {
                    // Update core agents store
                    const coreAgentsStore = this.getView().down('#coreAgentsView').getStore();
                    coreAgentsStore.loadData(data.core_agents);
                    
                    // Update specialized agents store
                    const specializedAgentsStore = this.getView().down('#specializedAgentsView').getStore();
                    specializedAgentsStore.loadData(data.specialized_agents);
                }
            },
            failure: function() {
                this.showErrorMessage('Failed to load agents status');
            },
            scope: this
        });
    },
    
    // Load active workflows
    loadActiveWorkflows: function() {
        Ext.Ajax.request({
            url: '/api/agents/workflows/active',
            method: 'GET',
            success: function(response) {
                const data = Ext.decode(response.responseText);
                
                if (data.success) {
                    const store = this.getView().down('#activeWorkflowsGrid').getStore();
                    store.loadData(data.workflows);
                }
            },
            scope: this
        });
    },
    
    // Load activity feed
    loadActivityFeed: function() {
        Ext.Ajax.request({
            url: '/api/agents/activity-feed',
            method: 'GET',
            success: function(response) {
                const data = Ext.decode(response.responseText);
                
                if (data.success) {
                    const store = this.getView().down('#activityFeedView').getStore();
                    store.loadData(data.activities);
                }
            },
            scope: this
        });
    },
    
    // Load system health
    loadSystemHealth: function() {
        Ext.Ajax.request({
            url: '/api/agents/system-health',
            method: 'GET',
            success: function(response) {
                const data = Ext.decode(response.responseText);
                
                if (data.success) {
                    const healthPanel = this.getView().down('#systemHealthPanel');
                    healthPanel.update(data.health_data);
                }
            },
            scope: this
        });
    },
    
    // Load workflow templates
    loadWorkflowTemplates: function() {
        const templates = [
            {
                template_id: 'employee_onboarding',
                name: 'Employee Onboarding',
                description: 'Complete new hire onboarding process with multi-agent coordination',
                iconCls: 'fa-user-plus',
                agents_count: 5,
                steps_count: 8,
                estimated_duration: '3-5 days'
            },
            {
                template_id: 'leave_management',
                name: 'Leave Management',
                description: 'Automated leave request processing with approval chains',
                iconCls: 'fa-calendar',
                agents_count: 4,
                steps_count: 6,
                estimated_duration: '1-3 days'
            },
            {
                template_id: 'performance_review',
                name: 'Performance Review',
                description: '360-degree performance review coordination',
                iconCls: 'fa-star',
                agents_count: 3,
                steps_count: 7,
                estimated_duration: '2-3 weeks'
            },
            {
                template_id: 'payroll_exceptions',
                name: 'Payroll Exceptions',
                description: 'Automated payroll discrepancy detection and resolution',
                iconCls: 'fa-dollar',
                agents_count: 4,
                steps_count: 8,
                estimated_duration: '1-2 days'
            },
            {
                template_id: 'recruitment',
                name: 'Recruitment Process',
                description: 'End-to-end recruitment automation with candidate screening',
                iconCls: 'fa-search',
                agents_count: 6,
                steps_count: 10,
                estimated_duration: '2-4 weeks'
            },
            {
                template_id: 'compliance_monitoring',
                name: 'Compliance Monitoring',
                description: 'Automated compliance tracking and audit preparation',
                iconCls: 'fa-shield',
                agents_count: 3,
                steps_count: 8,
                estimated_duration: 'Ongoing'
            }
        ];
        
        const store = this.getView().down('#workflowTemplatesView').getStore();
        store.loadData(templates);
    },
    
    // Start auto-refresh timer
    startAutoRefresh: function() {
        const autoRefreshCheck = this.getView().down('#autoRefreshCheck');
        
        this.refreshTask = Ext.TaskManager.start({
            run: function() {
                if (autoRefreshCheck.getValue()) {
                    this.loadAgentsData();
                }
            },
            interval: 30000, // 30 seconds
            scope: this
        });
    },
    
    // Event handlers
    onAgentClick: function(view, record) {
        this.showAgentDetails(record.get('name'), record.get('type'));
    },
    
    onViewWorkflowDetails: function(view, rowIndex, colIndex, item, e, record) {
        this.showWorkflowDetails(record.get('workflow_id'));
    },
    
    onPauseWorkflow: function(view, rowIndex, colIndex, item, e, record) {
        this.pauseWorkflow(record.get('workflow_id'));
    },
    
    onStartOnboarding: function() {
        this.showWorkflowStartDialog('employee_onboarding');
    },
    
    onStartLeaveRequest: function() {
        this.showWorkflowStartDialog('leave_request');
    },
    
    onStartPerformanceReview: function() {
        this.showWorkflowStartDialog('performance_review');
    },
    
    onStartPayrollException: function() {
        this.showWorkflowStartDialog('payroll_exceptions');
    },
    
    onStartRecruitment: function() {
        this.showWorkflowStartDialog('recruitment');
    },
    
    onStartCompliance: function() {
        this.showWorkflowStartDialog('compliance_monitoring');
    },
    
    onRefreshWorkflows: function() {
        this.loadActiveWorkflows();
    },
    
    onTemplateClick: function(view, record, item, index, e) {
        const target = e.getTarget();
        
        if (target.classList.contains('btn-start')) {
            this.startWorkflowFromTemplate(record.get('template_id'));
        } else if (target.classList.contains('btn-configure')) {
            this.configureWorkflowTemplate(record.get('template_id'));
        }
    },
    
    onClearActivityFeed: function() {
        const store = this.getView().down('#activityFeedView').getStore();
        store.removeAll();
    },
    
    onEmergencyShutdown: function() {
        Ext.Msg.confirm('Emergency Shutdown', 
            'Are you sure you want to perform an emergency shutdown of all agents and workflows?',
            function(btn) {
                if (btn === 'yes') {
                    this.performEmergencyShutdown();
                }
            }, this);
    },
    
    onProcessQuery: function() {
        this.showEmployeeQueryDialog();
    },
    
    onHealthCheck: function() {
        this.runSystemHealthCheck();
    },
    
    onGenerateReport: function() {
        this.showReportGenerationDialog();
    },
    
    onBackupConfigs: function() {
        this.backupAgentConfigurations();
    },
    
    onAgentSelect: function(combo, record) {
        this.loadAgentConfiguration(record.get('type'));
    },
    
    onSaveAgentConfig: function() {
        this.saveAgentConfiguration();
    },
    
    onResetAgentConfig: function() {
        this.resetAgentConfiguration();
    },
    
    // Show workflow start dialog
    showWorkflowStartDialog: function(workflowType) {
        const dialog = Ext.create('HRApp.view.agents.WorkflowStartDialog', {
            workflowType: workflowType
        });
        
        dialog.show();
    },
    
    // Show agent details
    showAgentDetails: function(agentName, agentType) {
        const window = Ext.create('Ext.window.Window', {
            title: `Agent Details - ${agentName}`,
            width: 600,
            height: 400,
            modal: true,
            layout: 'fit',
            items: [{
                xtype: 'panel',
                html: `Loading details for ${agentName}...`
            }]
        });
        
        window.show();
        
        // Load agent details from backend
        Ext.Ajax.request({
            url: `/api/agents/${agentType}/details`,
            method: 'GET',
            success: function(response) {
                const data = Ext.decode(response.responseText);
                // Update window content with agent details
                window.down('panel').update(this.buildAgentDetailsHtml(data));
            },
            scope: this
        });
    },
    
    // Show workflow details
    showWorkflowDetails: function(workflowId) {
        const window = Ext.create('Ext.window.Window', {
            title: `Workflow Details - ${workflowId}`,
            width: 800,
            height: 600,
            modal: true,
            layout: 'fit',
            items: [{
                xtype: 'tabpanel',
                items: [{
                    title: 'Overview',
                    html: `Loading workflow details for ${workflowId}...`
                }, {
                    title: 'Timeline',
                    xtype: 'grid'
                }, {
                    title: 'Agents Involved',
                    xtype: 'dataview'
                }]
            }]
        });
        
        window.show();
    },
    
    // Pause workflow
    pauseWorkflow: function(workflowId) {
        Ext.Ajax.request({
            url: `/api/agents/workflows/${workflowId}/pause`,
            method: 'POST',
            success: function(response) {
                const data = Ext.decode(response.responseText);
                if (data.success) {
                    this.showSuccessMessage('Workflow paused successfully');
                    this.loadActiveWorkflows();
                }
            },
            scope: this
        });
    },
    
    // Start workflow from template
    startWorkflowFromTemplate: function(templateId) {
        this.showWorkflowStartDialog(templateId);
    },
    
    // Configure workflow template
    configureWorkflowTemplate: function(templateId) {
        const window = Ext.create('Ext.window.Window', {
            title: `Configure ${templateId} Template`,
            width: 500,
            height: 400,
            modal: true,
            layout: 'fit',
            items: [{
                xtype: 'form',
                bodyPadding: 20,
                items: [{
                    xtype: 'textfield',
                    fieldLabel: 'Template Name',
                    name: 'name',
                    anchor: '100%'
                }, {
                    xtype: 'textarea',
                    fieldLabel: 'Description',
                    name: 'description',
                    anchor: '100%',
                    height: 100
                }]
            }]
        });
        
        window.show();
    },
    
    // Show employee query dialog
    showEmployeeQueryDialog: function() {
        const dialog = Ext.create('Ext.window.Window', {
            title: 'Process Employee Query',
            width: 500,
            height: 300,
            modal: true,
            layout: 'fit',
            items: [{
                xtype: 'form',
                bodyPadding: 20,
                items: [{
                    xtype: 'combo',
                    fieldLabel: 'Employee',
                    name: 'employee_id',
                    store: 'EmployeeComboStore',
                    displayField: 'name',
                    valueField: 'id',
                    anchor: '100%'
                }, {
                    xtype: 'textarea',
                    fieldLabel: 'Query',
                    name: 'query',
                    anchor: '100%',
                    height: 100
                }, {
                    xtype: 'combo',
                    fieldLabel: 'Priority',
                    name: 'priority',
                    store: ['low', 'medium', 'high', 'urgent'],
                    value: 'medium',
                    anchor: '100%'
                }],
                buttons: [{
                    text: 'Process Query',
                    handler: function() {
                        this.processEmployeeQuery(dialog);
                    },
                    scope: this
                }, {
                    text: 'Cancel',
                    handler: function() {
                        dialog.close();
                    }
                }]
            }]
        });
        
        dialog.show();
    },
    
    // Process employee query
    processEmployeeQuery: function(dialog) {
        const form = dialog.down('form');
        const values = form.getValues();
        
        Ext.Ajax.request({
            url: '/api/agents/employee-query',
            method: 'POST',
            jsonData: values,
            success: function(response) {
                const data = Ext.decode(response.responseText);
                if (data.success) {
                    this.showSuccessMessage('Employee query processed successfully');
                    dialog.close();
                    this.loadActivityFeed();
                }
            },
            scope: this
        });
    },
    
    // Run system health check
    runSystemHealthCheck: function() {
        const mask = new Ext.LoadMask({
            msg: 'Running system health check...',
            target: this.getView()
        });
        
        mask.show();
        
        Ext.Ajax.request({
            url: '/api/agents/health-check',
            method: 'POST',
            success: function(response) {
                mask.hide();
                const data = Ext.decode(response.responseText);
                
                if (data.success) {
                    this.showHealthCheckResults(data.health_report);
                }
            },
            failure: function() {
                mask.hide();
                this.showErrorMessage('Health check failed');
            },
            scope: this
        });
    },
    
    // Show health check results
    showHealthCheckResults: function(healthReport) {
        const window = Ext.create('Ext.window.Window', {
            title: 'System Health Check Results',
            width: 600,
            height: 500,
            modal: true,
            layout: 'fit',
            items: [{
                xtype: 'panel',
                bodyPadding: 20,
                autoScroll: true,
                html: this.buildHealthReportHtml(healthReport)
            }]
        });
        
        window.show();
    },
    
    // Build health report HTML
    buildHealthReportHtml: function(report) {
        return `
            <div class="health-report">
                <h2>System Health Report</h2>
                <div class="overall-health ${report.overall_health}">
                    <strong>Overall Health: ${report.overall_health.toUpperCase()}</strong>
                    <span class="percentage">${report.health_percentage}%</span>
                </div>
                <div class="health-details">
                    <h3>Agent Status</h3>
                    <ul>
                        ${Object.entries(report.agent_status).map(([agent, status]) => 
                            `<li class="${status.status}">${agent}: ${status.status}</li>`
                        ).join('')}
                    </ul>
                    <h3>System Metrics</h3>
                    <ul>
                        <li>Active Workflows: ${report.active_workflows}</li>
                        <li>Memory Usage: ${report.memory_usage || 'N/A'}</li>
                        <li>Response Time: ${report.avg_response_time || 'N/A'}</li>
                    </ul>
                </div>
            </div>
        `;
    },
    
    // Perform emergency shutdown
    performEmergencyShutdown: function() {
        const mask = new Ext.LoadMask({
            msg: 'Performing emergency shutdown...',
            target: this.getView()
        });
        
        mask.show();
        
        Ext.Ajax.request({
            url: '/api/agents/emergency-shutdown',
            method: 'POST',
            jsonData: {
                reason: 'User initiated emergency shutdown'
            },
            success: function(response) {
                mask.hide();
                const data = Ext.decode(response.responseText);
                
                if (data.success) {
                    this.showSuccessMessage('Emergency shutdown completed successfully');
                    this.loadAgentsData();
                }
            },
            failure: function() {
                mask.hide();
                this.showErrorMessage('Emergency shutdown failed');
            },
            scope: this
        });
    },
    
    // Utility methods
    showSuccessMessage: function(message) {
        Ext.toast({
            html: message,
            cls: 'success-toast',
            align: 't'
        });
    },
    
    showErrorMessage: function(message) {
        Ext.toast({
            html: message,
            cls: 'error-toast',
            align: 't'
        });
    },
    
    // Renderers for grid columns
    renderWorkflowType: function(value) {
        const types = {
            'employee_onboarding': 'Employee Onboarding',
            'leave_request': 'Leave Request',
            'performance_review': 'Performance Review',
            'payroll_exceptions': 'Payroll Exceptions',
            'recruitment': 'Recruitment',
            'compliance_monitoring': 'Compliance Monitoring'
        };
        
        return types[value] || value;
    },
    
    renderWorkflowStatus: function(value) {
        const statusMap = {
            'active': '<span class="status-active">Active</span>',
            'completed': '<span class="status-completed">Completed</span>',
            'paused': '<span class="status-paused">Paused</span>',
            'error': '<span class="status-error">Error</span>'
        };
        
        return statusMap[value] || value;
    },
    
    renderProgress: function(value) {
        return `
            <div class="progress-bar">
                <div class="progress-fill" style="width: ${value}%"></div>
                <span class="progress-text">${value}%</span>
            </div>
        `;
    },
    
    renderDateTime: function(value) {
        if (!value) return 'N/A';
        
        const date = new Date(value);
        return Ext.Date.format(date, 'Y-m-d H:i:s');
    }
});