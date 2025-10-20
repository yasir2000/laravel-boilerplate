/**
 * AI Agents Dashboard - Main control center for all AI agents and workflows
 */
Ext.define('HRApp.view.agents.AgentsDashboard', {
    extend: 'Ext.panel.Panel',
    xtype: 'agentsdashboard',
    
    title: 'AI Agents Control Center',
    iconCls: 'fa fa-robot',
    layout: 'border',
    
    requires: [
        'HRApp.view.agents.AgentsController',
        'HRApp.view.agents.AgentsModel'
    ],
    
    controller: 'agents',
    viewModel: 'agents',
    
    items: [{
        // Left sidebar - Agent status and controls
        region: 'west',
        xtype: 'panel',
        title: 'AI Agents Status',
        width: 350,
        split: true,
        collapsible: true,
        layout: 'accordion',
        items: [{
            title: 'Core Agents',
            iconCls: 'fa fa-cogs',
            xtype: 'dataview',
            itemId: 'coreAgentsView',
            store: 'CoreAgentsStore',
            tpl: new Ext.XTemplate(
                '<div class="agents-container">',
                    '<tpl for=".">',
                        '<div class="agent-card {status}">',
                            '<div class="agent-header">',
                                '<i class="fa {iconCls}"></i>',
                                '<span class="agent-name">{name}</span>',
                                '<span class="agent-status status-{status}">{status}</span>',
                            '</div>',
                            '<div class="agent-details">',
                                '<div class="agent-metric">',
                                    '<span class="metric-label">Tasks:</span>',
                                    '<span class="metric-value">{active_tasks}</span>',
                                '</div>',
                                '<div class="agent-metric">',
                                    '<span class="metric-label">Load:</span>',
                                    '<span class="metric-value">{load_percentage}%</span>',
                                '</div>',
                            '</div>',
                        '</div>',
                    '</tpl>',
                '</div>'
            ),
            itemSelector: 'div.agent-card',
            listeners: {
                itemclick: 'onAgentClick'
            }
        }, {
            title: 'Specialized Agents',
            iconCls: 'fa fa-wrench',
            xtype: 'dataview',
            itemId: 'specializedAgentsView',
            store: 'SpecializedAgentsStore',
            tpl: new Ext.XTemplate(
                '<div class="agents-container">',
                    '<tpl for=".">',
                        '<div class="agent-card {status}">',
                            '<div class="agent-header">',
                                '<i class="fa {iconCls}"></i>',
                                '<span class="agent-name">{name}</span>',
                                '<span class="agent-status status-{status}">{status}</span>',
                            '</div>',
                            '<div class="agent-details">',
                                '<div class="agent-metric">',
                                    '<span class="metric-label">Queue:</span>',
                                    '<span class="metric-value">{queue_size}</span>',
                                '</div>',
                                '<div class="agent-metric">',
                                    '<span class="metric-label">Completed:</span>',
                                    '<span class="metric-value">{completed_today}</span>',
                                '</div>',
                            '</div>',
                        '</div>',
                    '</tpl>',
                '</div>'
            ),
            itemSelector: 'div.agent-card'
        }, {
            title: 'System Health',
            iconCls: 'fa fa-heartbeat',
            xtype: 'panel',
            layout: 'vbox',
            items: [{
                xtype: 'panel',
                itemId: 'systemHealthPanel',
                tpl: new Ext.XTemplate(
                    '<div class="health-overview">',
                        '<div class="health-metric overall-health {overall_status}">',
                            '<div class="health-title">System Health</div>',
                            '<div class="health-value">{health_percentage}%</div>',
                            '<div class="health-status">{overall_status}</div>',
                        '</div>',
                        '<div class="health-details">',
                            '<div class="health-item">',
                                '<span class="label">Active Workflows:</span>',
                                '<span class="value">{active_workflows}</span>',
                            '</div>',
                            '<div class="health-item">',
                                '<span class="label">Healthy Agents:</span>',
                                '<span class="value">{healthy_agents}/{total_agents}</span>',
                            '</div>',
                            '<div class="health-item">',
                                '<span class="label">Memory Usage:</span>',
                                '<span class="value">{memory_usage}%</span>',
                            '</div>',
                        '</div>',
                    '</div>'
                )
            }, {
                xtype: 'button',
                text: 'Emergency Shutdown',
                iconCls: 'fa fa-power-off',
                cls: 'emergency-button',
                margin: '10 0 0 0',
                handler: 'onEmergencyShutdown'
            }]
        }]
    }, {
        // Main content area - Workflows and monitoring
        region: 'center',
        xtype: 'tabpanel',
        items: [{
            title: 'Active Workflows',
            iconCls: 'fa fa-tasks',
            xtype: 'grid',
            itemId: 'activeWorkflowsGrid',
            store: 'ActiveWorkflowsStore',
            columns: [{
                text: 'Workflow ID',
                dataIndex: 'workflow_id',
                width: 150
            }, {
                text: 'Type',
                dataIndex: 'workflow_type',
                width: 150,
                renderer: 'renderWorkflowType'
            }, {
                text: 'Employee',
                dataIndex: 'employee_name',
                width: 150
            }, {
                text: 'Status',
                dataIndex: 'status',
                width: 100,
                renderer: 'renderWorkflowStatus'
            }, {
                text: 'Progress',
                dataIndex: 'progress_percentage',
                width: 120,
                renderer: 'renderProgress'
            }, {
                text: 'Started',
                dataIndex: 'started_at',
                width: 150,
                renderer: 'renderDateTime'
            }, {
                text: 'Estimated Completion',
                dataIndex: 'estimated_completion',
                width: 180,
                renderer: 'renderDateTime'
            }, {
                text: 'Actions',
                xtype: 'actioncolumn',
                width: 100,
                items: [{
                    iconCls: 'fa fa-eye',
                    tooltip: 'View Details',
                    handler: 'onViewWorkflowDetails'
                }, {
                    iconCls: 'fa fa-pause',
                    tooltip: 'Pause Workflow',
                    handler: 'onPauseWorkflow'
                }]
            }],
            tbar: [{
                text: 'Start New Workflow',
                iconCls: 'fa fa-plus',
                menu: [{
                    text: 'Employee Onboarding',
                    iconCls: 'fa fa-user-plus',
                    handler: 'onStartOnboarding'
                }, {
                    text: 'Leave Request',
                    iconCls: 'fa fa-calendar',
                    handler: 'onStartLeaveRequest'
                }, {
                    text: 'Performance Review',
                    iconCls: 'fa fa-star',
                    handler: 'onStartPerformanceReview'
                }, {
                    text: 'Payroll Exception',
                    iconCls: 'fa fa-dollar',
                    handler: 'onStartPayrollException'
                }, {
                    text: 'Recruitment Process',
                    iconCls: 'fa fa-search',
                    handler: 'onStartRecruitment'
                }, {
                    text: 'Compliance Audit',
                    iconCls: 'fa fa-shield',
                    handler: 'onStartCompliance'
                }]
            }, '-', {
                text: 'Refresh',
                iconCls: 'fa fa-refresh',
                handler: 'onRefreshWorkflows'
            }]
        }, {
            title: 'Workflow Templates',
            iconCls: 'fa fa-sitemap',
            xtype: 'dataview',
            itemId: 'workflowTemplatesView',
            store: 'WorkflowTemplatesStore',
            tpl: new Ext.XTemplate(
                '<div class="workflow-templates">',
                    '<tpl for=".">',
                        '<div class="template-card" data-template="{template_id}">',
                            '<div class="template-header">',
                                '<i class="fa {iconCls}"></i>',
                                '<h3>{name}</h3>',
                            '</div>',
                            '<div class="template-body">',
                                '<p class="description">{description}</p>',
                                '<div class="template-stats">',
                                    '<div class="stat">',
                                        '<span class="label">Agents:</span>',
                                        '<span class="value">{agents_count}</span>',
                                    '</div>',
                                    '<div class="stat">',
                                        '<span class="label">Steps:</span>',
                                        '<span class="value">{steps_count}</span>',
                                    '</div>',
                                    '<div class="stat">',
                                        '<span class="label">Duration:</span>',
                                        '<span class="value">{estimated_duration}</span>',
                                    '</div>',
                                '</div>',
                            '</div>',
                            '<div class="template-footer">',
                                '<button class="btn-start" data-action="start">Start Workflow</button>',
                                '<button class="btn-configure" data-action="configure">Configure</button>',
                            '</div>',
                        '</div>',
                    '</tpl>',
                '</div>'
            ),
            itemSelector: 'div.template-card',
            listeners: {
                itemclick: 'onTemplateClick'
            }
        }, {
            title: 'Agent Activity Feed',
            iconCls: 'fa fa-rss',
            xtype: 'dataview',
            itemId: 'activityFeedView',
            store: 'ActivityFeedStore',
            autoScroll: true,
            tpl: new Ext.XTemplate(
                '<div class="activity-feed">',
                    '<tpl for=".">',
                        '<div class="activity-item {type}">',
                            '<div class="activity-time">{timestamp:date("H:i:s")}</div>',
                            '<div class="activity-icon">',
                                '<i class="fa {iconCls}"></i>',
                            '</div>',
                            '<div class="activity-content">',
                                '<div class="activity-title">{title}</div>',
                                '<div class="activity-description">{description}</div>',
                                '<tpl if="agent_name">',
                                    '<div class="activity-agent">Agent: {agent_name}</div>',
                                '</tpl>',
                            '</div>',
                        '</div>',
                    '</tpl>',
                '</div>'
            ),
            tbar: [{
                xtype: 'button',
                text: 'Clear Feed',
                iconCls: 'fa fa-trash',
                handler: 'onClearActivityFeed'
            }, '->', {
                xtype: 'checkbox',
                boxLabel: 'Auto Refresh',
                checked: true,
                itemId: 'autoRefreshCheck'
            }]
        }, {
            title: 'Analytics & Reports',
            iconCls: 'fa fa-chart-line',
            layout: 'border',
            items: [{
                region: 'north',
                height: 200,
                xtype: 'panel',
                title: 'Key Performance Indicators',
                layout: 'hbox',
                defaults: {
                    flex: 1,
                    margin: 5
                },
                items: [{
                    xtype: 'panel',
                    itemId: 'kpiPanel1',
                    cls: 'kpi-panel',
                    tpl: new Ext.XTemplate(
                        '<div class="kpi-content">',
                            '<div class="kpi-value">{value}</div>',
                            '<div class="kpi-label">{label}</div>',
                            '<div class="kpi-change {change_type}">{change}%</div>',
                        '</div>'
                    )
                }, {
                    xtype: 'panel',
                    itemId: 'kpiPanel2',
                    cls: 'kpi-panel'
                }, {
                    xtype: 'panel',
                    itemId: 'kpiPanel3',
                    cls: 'kpi-panel'
                }, {
                    xtype: 'panel',
                    itemId: 'kpiPanel4',
                    cls: 'kpi-panel'
                }]
            }, {
                region: 'center',
                xtype: 'tabpanel',
                items: [{
                    title: 'Workflow Performance',
                    xtype: 'cartesian',
                    itemId: 'workflowPerformanceChart',
                    store: 'WorkflowPerformanceStore',
                    axes: [{
                        type: 'numeric',
                        position: 'left',
                        title: 'Completion Time (hours)'
                    }, {
                        type: 'category',
                        position: 'bottom',
                        title: 'Workflow Type'
                    }],
                    series: [{
                        type: 'bar',
                        xField: 'workflow_type',
                        yField: 'avg_completion_time'
                    }]
                }, {
                    title: 'Agent Utilization',
                    xtype: 'polar',
                    itemId: 'agentUtilizationChart',
                    store: 'AgentUtilizationStore',
                    series: [{
                        type: 'pie',
                        angleField: 'utilization_percentage',
                        label: {
                            field: 'agent_name'
                        }
                    }]
                }, {
                    title: 'Success Rates',
                    xtype: 'cartesian',
                    itemId: 'successRatesChart',
                    store: 'SuccessRatesStore',
                    axes: [{
                        type: 'numeric',
                        position: 'left',
                        title: 'Success Rate %'
                    }, {
                        type: 'time',
                        position: 'bottom',
                        title: 'Date'
                    }],
                    series: [{
                        type: 'line',
                        xField: 'date',
                        yField: 'success_rate'
                    }]
                }]
            }]
        }]
    }, {
        // Right sidebar - Configuration and tools
        region: 'east',
        xtype: 'panel',
        title: 'Configuration & Tools',
        width: 300,
        split: true,
        collapsible: true,
        layout: 'accordion',
        items: [{
            title: 'Quick Actions',
            iconCls: 'fa fa-bolt',
            xtype: 'panel',
            layout: 'vbox',
            defaults: {
                xtype: 'button',
                width: '100%',
                margin: '5 0'
            },
            items: [{
                text: 'Process Employee Query',
                iconCls: 'fa fa-question-circle',
                handler: 'onProcessQuery'
            }, {
                text: 'Run System Health Check',
                iconCls: 'fa fa-stethoscope',
                handler: 'onHealthCheck'
            }, {
                text: 'Generate Analytics Report',
                iconCls: 'fa fa-file-pdf-o',
                handler: 'onGenerateReport'
            }, {
                text: 'Backup Agent Configurations',
                iconCls: 'fa fa-save',
                handler: 'onBackupConfigs'
            }]
        }, {
            title: 'Agent Configuration',
            iconCls: 'fa fa-cog',
            xtype: 'form',
            itemId: 'agentConfigForm',
            bodyPadding: 10,
            items: [{
                xtype: 'combo',
                fieldLabel: 'Select Agent',
                name: 'agent_type',
                store: 'AgentTypesStore',
                displayField: 'name',
                valueField: 'type',
                listeners: {
                    select: 'onAgentSelect'
                }
            }, {
                xtype: 'fieldset',
                title: 'Agent Settings',
                itemId: 'agentSettings',
                defaults: {
                    xtype: 'textfield',
                    anchor: '100%'
                },
                items: [{
                    fieldLabel: 'Max Concurrent Tasks',
                    name: 'max_concurrent_tasks',
                    value: 5
                }, {
                    fieldLabel: 'Priority Level',
                    xtype: 'combo',
                    name: 'priority_level',
                    store: ['low', 'medium', 'high', 'critical'],
                    value: 'medium'
                }, {
                    fieldLabel: 'Auto Restart',
                    xtype: 'checkbox',
                    name: 'auto_restart',
                    checked: true
                }]
            }],
            buttons: [{
                text: 'Save Configuration',
                iconCls: 'fa fa-save',
                handler: 'onSaveAgentConfig'
            }, {
                text: 'Reset to Defaults',
                iconCls: 'fa fa-undo',
                handler: 'onResetAgentConfig'
            }]
        }, {
            title: 'System Monitoring',
            iconCls: 'fa fa-monitor',
            xtype: 'panel',
            layout: 'vbox',
            items: [{
                xtype: 'panel',
                itemId: 'memoryUsagePanel',
                title: 'Memory Usage',
                height: 100,
                tpl: new Ext.XTemplate(
                    '<div class="memory-usage">',
                        '<div class="usage-bar">',
                            '<div class="usage-fill" style="width: {percentage}%"></div>',
                        '</div>',
                        '<div class="usage-text">{used}MB / {total}MB ({percentage}%)</div>',
                    '</div>'
                )
            }, {
                xtype: 'panel',
                itemId: 'responseTimePanel',
                title: 'Avg Response Time',
                height: 60,
                tpl: new Ext.XTemplate(
                    '<div class="response-time">',
                        '<span class="time-value">{avg_response_time}ms</span>',
                        '<span class="time-trend {trend_direction}">{trend_percentage}%</span>',
                    '</div>'
                )
            }]
        }]
    }],
    
    // Custom CSS for styling
    cls: 'agents-dashboard'
});