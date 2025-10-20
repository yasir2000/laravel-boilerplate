/**
 * Workflow Start Dialog - For initiating new workflows
 */
Ext.define('HRApp.view.agents.WorkflowStartDialog', {
    extend: 'Ext.window.Window',
    xtype: 'workflowstartdialog',
    
    title: 'Start New Workflow',
    width: 600,
    height: 500,
    modal: true,
    layout: 'fit',
    
    config: {
        workflowType: null
    },
    
    items: [{
        xtype: 'form',
        itemId: 'workflowForm',
        bodyPadding: 20,
        autoScroll: true,
        defaults: {
            anchor: '100%',
            labelWidth: 140
        },
        items: [{
            xtype: 'displayfield',
            itemId: 'workflowTypeDisplay',
            fieldLabel: 'Workflow Type',
            value: 'Loading...'
        }, {
            xtype: 'textfield',
            fieldLabel: 'Workflow Name',
            name: 'workflow_name',
            allowBlank: false
        }, {
            xtype: 'textarea',
            fieldLabel: 'Description',
            name: 'description',
            height: 80
        }, {
            xtype: 'combo',
            fieldLabel: 'Priority',
            name: 'priority',
            store: [
                ['low', 'Low'],
                ['medium', 'Medium'],
                ['high', 'High'],
                ['urgent', 'Urgent']
            ],
            value: 'medium',
            editable: false
        }, {
            xtype: 'fieldset',
            title: 'Workflow Configuration',
            itemId: 'workflowConfig',
            defaults: {
                anchor: '100%',
                labelWidth: 140
            },
            items: []
        }],
        buttons: [{
            text: 'Start Workflow',
            iconCls: 'fa fa-play',
            handler: 'onStartWorkflow',
            formBind: true
        }, {
            text: 'Cancel',
            iconCls: 'fa fa-times',
            handler: function() {
                this.up('window').close();
            }
        }]
    }],
    
    listeners: {
        show: 'onDialogShow'
    },
    
    // Controller methods
    onDialogShow: function() {
        this.setupWorkflowForm();
    },
    
    setupWorkflowForm: function() {
        const workflowType = this.getWorkflowType();
        const form = this.down('#workflowForm');
        const configFieldset = this.down('#workflowConfig');
        const typeDisplay = this.down('#workflowTypeDisplay');
        
        // Set workflow type display
        const workflowNames = {
            'employee_onboarding': 'Employee Onboarding',
            'leave_request': 'Leave Request Processing',
            'performance_review': 'Performance Review Coordination',
            'payroll_exceptions': 'Payroll Exception Handling',
            'recruitment': 'Recruitment Process',
            'compliance_monitoring': 'Compliance Monitoring'
        };
        
        typeDisplay.setValue(workflowNames[workflowType] || workflowType);
        this.setTitle(`Start ${workflowNames[workflowType] || workflowType} Workflow`);
        
        // Clear existing config fields
        configFieldset.removeAll();
        
        // Add workflow-specific configuration fields
        const configFields = this.getWorkflowConfigFields(workflowType);
        configFieldset.add(configFields);
    },
    
    getWorkflowConfigFields: function(workflowType) {
        switch (workflowType) {
            case 'employee_onboarding':
                return [{
                    xtype: 'textfield',
                    fieldLabel: 'Employee Name',
                    name: 'employee_name',
                    allowBlank: false
                }, {
                    xtype: 'textfield',
                    fieldLabel: 'Email',
                    name: 'employee_email',
                    vtype: 'email',
                    allowBlank: false
                }, {
                    xtype: 'combo',
                    fieldLabel: 'Department',
                    name: 'department_id',
                    store: 'DepartmentStore',
                    displayField: 'name',
                    valueField: 'id',
                    allowBlank: false
                }, {
                    xtype: 'combo',
                    fieldLabel: 'Position',
                    name: 'position',
                    store: ['Software Engineer', 'Project Manager', 'HR Manager', 'Data Analyst'],
                    allowBlank: false
                }, {
                    xtype: 'datefield',
                    fieldLabel: 'Start Date',
                    name: 'start_date',
                    value: new Date(),
                    allowBlank: false
                }, {
                    xtype: 'textfield',
                    fieldLabel: 'Manager',
                    name: 'manager_id',
                    allowBlank: false
                }];
                
            case 'leave_request':
                return [{
                    xtype: 'combo',
                    fieldLabel: 'Employee',
                    name: 'employee_id',
                    store: 'EmployeeComboStore',
                    displayField: 'name',
                    valueField: 'id',
                    allowBlank: false
                }, {
                    xtype: 'combo',
                    fieldLabel: 'Leave Type',
                    name: 'leave_type',
                    store: ['Vacation', 'Sick Leave', 'Personal', 'Maternity/Paternity', 'Emergency'],
                    allowBlank: false
                }, {
                    xtype: 'datefield',
                    fieldLabel: 'Start Date',
                    name: 'start_date',
                    allowBlank: false
                }, {
                    xtype: 'datefield',
                    fieldLabel: 'End Date',
                    name: 'end_date',
                    allowBlank: false
                }, {
                    xtype: 'textarea',
                    fieldLabel: 'Reason',
                    name: 'reason',
                    height: 60
                }, {
                    xtype: 'checkbox',
                    fieldLabel: 'Half Day',
                    name: 'is_half_day'
                }];
                
            case 'performance_review':
                return [{
                    xtype: 'combo',
                    fieldLabel: 'Employee',
                    name: 'employee_id',
                    store: 'EmployeeComboStore',
                    displayField: 'name',
                    valueField: 'id',
                    allowBlank: false
                }, {
                    xtype: 'combo',
                    fieldLabel: 'Review Type',
                    name: 'review_type',
                    store: ['Annual', 'Mid-Year', 'Quarterly', 'Probation', 'Project-Based'],
                    value: 'Annual',
                    allowBlank: false
                }, {
                    xtype: 'datefield',
                    fieldLabel: 'Review Period Start',
                    name: 'period_start',
                    allowBlank: false
                }, {
                    xtype: 'datefield',
                    fieldLabel: 'Review Period End',
                    name: 'period_end',
                    allowBlank: false
                }, {
                    xtype: 'datefield',
                    fieldLabel: 'Target Completion',
                    name: 'target_completion',
                    allowBlank: false
                }, {
                    xtype: 'tagfield',
                    fieldLabel: 'Reviewers',
                    name: 'reviewers',
                    store: 'EmployeeComboStore',
                    displayField: 'name',
                    valueField: 'id'
                }];
                
            case 'payroll_exceptions':
                return [{
                    xtype: 'combo',
                    fieldLabel: 'Payroll Period',
                    name: 'payroll_period',
                    store: ['Current Month', 'Previous Month', 'Custom Range'],
                    value: 'Current Month',
                    allowBlank: false
                }, {
                    xtype: 'datefield',
                    fieldLabel: 'Period Start',
                    name: 'period_start',
                    hidden: true
                }, {
                    xtype: 'datefield',
                    fieldLabel: 'Period End',
                    name: 'period_end',
                    hidden: true
                }, {
                    xtype: 'checkbox',
                    fieldLabel: 'Auto-resolve Minor Issues',
                    name: 'auto_resolve',
                    checked: true
                }, {
                    xtype: 'numberfield',
                    fieldLabel: 'Auto-resolve Threshold ($)',
                    name: 'auto_resolve_threshold',
                    value: 10.00,
                    decimalPrecision: 2
                }];
                
            case 'recruitment':
                return [{
                    xtype: 'textfield',
                    fieldLabel: 'Job Title',
                    name: 'job_title',
                    allowBlank: false
                }, {
                    xtype: 'combo',
                    fieldLabel: 'Department',
                    name: 'department_id',
                    store: 'DepartmentStore',
                    displayField: 'name',
                    valueField: 'id',
                    allowBlank: false
                }, {
                    xtype: 'combo',
                    fieldLabel: 'Hiring Manager',
                    name: 'hiring_manager_id',
                    store: 'EmployeeComboStore',
                    displayField: 'name',
                    valueField: 'id',
                    allowBlank: false
                }, {
                    xtype: 'numberfield',
                    fieldLabel: 'Positions to Fill',
                    name: 'positions',
                    value: 1,
                    minValue: 1,
                    allowBlank: false
                }, {
                    xtype: 'datefield',
                    fieldLabel: 'Target Hire Date',
                    name: 'target_hire_date',
                    allowBlank: false
                }, {
                    xtype: 'textarea',
                    fieldLabel: 'Job Description',
                    name: 'job_description',
                    height: 100,
                    allowBlank: false
                }];
                
            case 'compliance_monitoring':
                return [{
                    xtype: 'tagfield',
                    fieldLabel: 'Compliance Frameworks',
                    name: 'frameworks',
                    store: [
                        ['sox', 'Sarbanes-Oxley (SOX)'],
                        ['gdpr', 'GDPR'],
                        ['hipaa', 'HIPAA'],
                        ['iso27001', 'ISO 27001'],
                        ['pci_dss', 'PCI DSS']
                    ],
                    allowBlank: false
                }, {
                    xtype: 'combo',
                    fieldLabel: 'Monitoring Scope',
                    name: 'scope',
                    store: ['Full Organization', 'Department Specific', 'Project Specific'],
                    value: 'Full Organization',
                    allowBlank: false
                }, {
                    xtype: 'combo',
                    fieldLabel: 'Frequency',
                    name: 'frequency',
                    store: ['Continuous', 'Daily', 'Weekly', 'Monthly'],
                    value: 'Daily',
                    allowBlank: false
                }, {
                    xtype: 'checkbox',
                    fieldLabel: 'Auto-generate Reports',
                    name: 'auto_reports',
                    checked: true
                }];
                
            default:
                return [{
                    xtype: 'displayfield',
                    value: 'No specific configuration required for this workflow type.'
                }];
        }
    },
    
    onStartWorkflow: function() {
        const form = this.down('#workflowForm');
        const values = form.getValues();
        
        // Add workflow type to values
        values.workflow_type = this.getWorkflowType();
        
        // Validate form
        if (!form.isValid()) {
            Ext.Msg.alert('Validation Error', 'Please fill in all required fields.');
            return;
        }
        
        // Show loading mask
        const mask = new Ext.LoadMask({
            msg: 'Starting workflow...',
            target: this
        });
        mask.show();
        
        // Submit to backend
        Ext.Ajax.request({
            url: '/api/agents/workflows/start',
            method: 'POST',
            jsonData: values,
            success: function(response) {
                mask.hide();
                const data = Ext.decode(response.responseText);
                
                if (data.success) {
                    Ext.toast({
                        html: `Workflow started successfully! ID: ${data.workflow_id}`,
                        cls: 'success-toast',
                        align: 't'
                    });
                    
                    this.close();
                    
                    // Refresh the main dashboard
                    const dashboard = Ext.ComponentQuery.query('agentsdashboard')[0];
                    if (dashboard && dashboard.getController()) {
                        dashboard.getController().loadActiveWorkflows();
                    }
                } else {
                    Ext.Msg.alert('Error', data.message || 'Failed to start workflow');
                }
            },
            failure: function() {
                mask.hide();
                Ext.Msg.alert('Error', 'Failed to communicate with server');
            },
            scope: this
        });
    }
});