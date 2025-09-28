/**
 * Leave Request Form Window
 * Professional form for creating and managing leave requests
 */
Ext.define('HRApp.view.leave.LeaveRequestForm', {
    extend: 'Ext.window.Window',
    xtype: 'leaverequestform',
    
    requires: [
        'HRApp.store.EmployeeStore'
    ],
    
    modal: true,
    resizable: true,
    width: 700,
    height: 600,
    layout: 'fit',
    
    // Configuration properties
    mode: 'create', // 'create' or 'edit'
    leaveRequestRecord: null,
    employeeId: null, // Pre-selected employee
    
    initComponent: function() {
        var me = this;
        
        // Set title based on mode
        me.title = me.mode === 'create' 
            ? '<i class="fa fa-calendar-plus-o"></i> Submit Leave Request' 
            : '<i class="fa fa-edit"></i> Edit Leave Request';
        
        // Create form panel with tabs
        me.items = [{
            xtype: 'form',
            itemId: 'leaveRequestForm',
            layout: 'fit',
            bodyPadding: 0,
            trackResetOnLoad: true,
            
            items: [{
                xtype: 'tabpanel',
                itemId: 'leaveRequestTabs',
                
                items: [{
                    title: 'Leave Details',
                    iconCls: 'fa fa-calendar',
                    layout: 'anchor',
                    bodyPadding: 20,
                    defaults: {
                        anchor: '100%',
                        labelWidth: 130,
                        msgTarget: 'side'
                    },
                    
                    items: [{
                        xtype: 'combobox',
                        name: 'employee_id',
                        fieldLabel: 'Employee *',
                        allowBlank: false,
                        store: Ext.create('HRApp.store.EmployeeStore'),
                        displayField: 'full_name',
                        valueField: 'id',
                        queryMode: 'local',
                        editable: true,
                        typeAhead: true,
                        forceSelection: true,
                        emptyText: 'Search and select employee...',
                        listConfig: {
                            itemTpl: [
                                '<div class="employee-item">',
                                    '<strong>{first_name} {last_name}</strong><br/>',
                                    '<small>{employee_id} - {department_name}</small>',
                                '</div>'
                            ]
                        },
                        value: me.employeeId || null,
                        listeners: {
                            change: function(combo, employeeId) {
                                // Load employee's leave balance when selected
                                if (employeeId) {
                                    me.loadEmployeeLeaveBalance(employeeId);
                                }
                            }
                        }
                    }, {
                        xtype: 'combobox',
                        name: 'leave_type',
                        fieldLabel: 'Leave Type *',
                        allowBlank: false,
                        store: [
                            ['vacation', 'Vacation Leave'],
                            ['sick', 'Sick Leave'],
                            ['personal', 'Personal Leave'],
                            ['maternity', 'Maternity Leave'],
                            ['paternity', 'Paternity Leave'],
                            ['bereavement', 'Bereavement Leave'],
                            ['emergency', 'Emergency Leave'],
                            ['unpaid', 'Unpaid Leave'],
                            ['compensatory', 'Compensatory Time Off']
                        ],
                        editable: false,
                        listeners: {
                            change: function(combo, leaveType) {
                                me.updateLeaveTypeInfo(leaveType);
                            }
                        }
                    }, {
                        xtype: 'container',
                        layout: 'hbox',
                        defaults: {
                            flex: 1,
                            margins: '0 10 10 0'
                        },
                        
                        items: [{
                            xtype: 'datefield',
                            name: 'start_date',
                            fieldLabel: 'Start Date *',
                            allowBlank: false,
                            format: 'Y-m-d',
                            minValue: new Date(),
                            listeners: {
                                change: function(field, newValue) {
                                    var endDateField = field.up('form').down('[name=end_date]');
                                    if (newValue) {
                                        endDateField.setMinValue(newValue);
                                        if (!endDateField.getValue() || endDateField.getValue() < newValue) {
                                            endDateField.setValue(newValue);
                                        }
                                    }
                                    me.calculateLeaveDays();
                                }
                            }
                        }, {
                            xtype: 'datefield',
                            name: 'end_date',
                            fieldLabel: 'End Date *',
                            allowBlank: false,
                            format: 'Y-m-d',
                            listeners: {
                                change: function(field, newValue) {
                                    me.calculateLeaveDays();
                                }
                            }
                        }]
                    }, {
                        xtype: 'container',
                        layout: 'hbox',
                        defaults: {
                            flex: 1,
                            margins: '0 10 10 0'
                        },
                        
                        items: [{
                            xtype: 'displayfield',
                            name: 'total_days',
                            fieldLabel: 'Total Days',
                            value: '0',
                            cls: 'hr-total-days'
                        }, {
                            xtype: 'checkboxfield',
                            name: 'is_half_day',
                            fieldLabel: 'Half Day',
                            boxLabel: 'This is a half-day leave',
                            listeners: {
                                change: function(checkbox, newValue) {
                                    me.calculateLeaveDays();
                                }
                            }
                        }]
                    }, {
                        xtype: 'textareafield',
                        name: 'reason',
                        fieldLabel: 'Reason *',
                        allowBlank: false,
                        rows: 4,
                        maxLength: 1000,
                        enforceMaxLength: true,
                        emptyText: 'Please provide a detailed reason for your leave request...'
                    }]
                }, {
                    title: 'Coverage & Handover',
                    iconCls: 'fa fa-handshake-o',
                    layout: 'anchor',
                    bodyPadding: 20,
                    defaults: {
                        anchor: '100%',
                        labelWidth: 130,
                        msgTarget: 'side'
                    },
                    
                    items: [{
                        xtype: 'combobox',
                        name: 'coverage_employee_id',
                        fieldLabel: 'Coverage Person',
                        store: Ext.create('HRApp.store.EmployeeStore'),
                        displayField: 'full_name',
                        valueField: 'id',
                        queryMode: 'local',
                        editable: true,
                        typeAhead: true,
                        emptyText: 'Select colleague who will cover your responsibilities...',
                        listConfig: {
                            itemTpl: [
                                '<div class="employee-item">',
                                    '<strong>{first_name} {last_name}</strong><br/>',
                                    '<small>{employee_id} - {position_title}</small>',
                                '</div>'
                            ]
                        }
                    }, {
                        xtype: 'textareafield',
                        name: 'handover_notes',
                        fieldLabel: 'Handover Notes',
                        rows: 5,
                        maxLength: 2000,
                        enforceMaxLength: true,
                        emptyText: 'List important tasks, deadlines, and contacts for your coverage person...'
                    }, {
                        xtype: 'checkboxfield',
                        name: 'coverage_arranged',
                        fieldLabel: 'Coverage Status',
                        boxLabel: 'I have arranged coverage for my responsibilities',
                        listeners: {
                            change: function(checkbox, checked) {
                                var coverageField = checkbox.up('form').down('[name=coverage_employee_id]');
                                var notesField = checkbox.up('form').down('[name=handover_notes]');
                                
                                if (checked) {
                                    coverageField.allowBlank = false;
                                    notesField.allowBlank = false;
                                } else {
                                    coverageField.allowBlank = true;
                                    notesField.allowBlank = true;
                                }
                            }
                        }
                    }]
                }, {
                    title: 'Leave Balance',
                    iconCls: 'fa fa-balance-scale',
                    layout: 'anchor',
                    bodyPadding: 20,
                    
                    items: [{
                        xtype: 'container',
                        itemId: 'leaveBalanceContainer',
                        html: '<div class="leave-balance-info">Select an employee to view leave balance information.</div>'
                    }]
                }]
            }]
        }];
        
        // Define buttons
        me.buttons = [{
            text: '<i class="fa fa-save"></i> Submit Request',
            cls: 'hr-btn-primary',
            handler: me.onSave,
            scope: me
        }, {
            text: '<i class="fa fa-file-o"></i> Save as Draft',
            cls: 'hr-btn-secondary',
            handler: me.onSaveDraft,
            scope: me
        }, {
            text: '<i class="fa fa-times"></i> Cancel',
            cls: 'hr-btn-secondary',
            handler: function() {
                me.close();
            }
        }];
        
        me.callParent();
        
        // Load data if in edit mode
        if (me.mode === 'edit' && me.leaveRequestRecord) {
            me.down('form').loadRecord(me.leaveRequestRecord);
            me.calculateLeaveDays();
        }
        
        // Load initial leave balance if employee is pre-selected
        if (me.employeeId) {
            me.loadEmployeeLeaveBalance(me.employeeId);
        }
    },
    
    calculateLeaveDays: function() {
        var form = this.down('form');
        var startDate = form.down('[name=start_date]').getValue();
        var endDate = form.down('[name=end_date]').getValue();
        var isHalfDay = form.down('[name=is_half_day]').getValue();
        var totalDaysField = form.down('[name=total_days]');
        
        if (startDate && endDate) {
            var timeDiff = endDate.getTime() - startDate.getTime();
            var daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
            
            // TODO: Exclude weekends and holidays in a real implementation
            var totalDays = daysDiff;
            
            if (isHalfDay && totalDays === 1) {
                totalDays = 0.5;
            }
            
            totalDaysField.setValue(totalDays.toString());
        } else {
            totalDaysField.setValue('0');
        }
    },
    
    loadEmployeeLeaveBalance: function(employeeId) {
        var me = this;
        var container = me.down('#leaveBalanceContainer');
        
        container.setLoading('Loading leave balance...');
        
        // Make AJAX request to get employee leave balance
        Ext.Ajax.request({
            url: '/api/hr/employees/' + employeeId + '/leave-balance',
            method: 'GET',
            
            success: function(response) {
                container.setLoading(false);
                var result = Ext.decode(response.responseText);
                
                if (result.success && result.data) {
                    var balance = result.data;
                    var html = [
                        '<div class="leave-balance-summary">',
                            '<h4>Leave Balance Summary</h4>',
                            '<div class="balance-grid">',
                                '<div class="balance-item">',
                                    '<label>Vacation Days:</label>',
                                    '<span class="balance-value">' + balance.vacation_remaining + '/' + balance.vacation_total + ' days</span>',
                                '</div>',
                                '<div class="balance-item">',
                                    '<label>Sick Days:</label>',
                                    '<span class="balance-value">' + balance.sick_remaining + '/' + balance.sick_total + ' days</span>',
                                '</div>',
                                '<div class="balance-item">',
                                    '<label>Personal Days:</label>',
                                    '<span class="balance-value">' + balance.personal_remaining + '/' + balance.personal_total + ' days</span>',
                                '</div>',
                            '</div>',
                        '</div>'
                    ].join('');
                    
                    container.update(html);
                } else {
                    container.update('<div class="leave-balance-error">Unable to load leave balance information.</div>');
                }
            },
            
            failure: function() {
                container.setLoading(false);
                container.update('<div class="leave-balance-error">Error loading leave balance information.</div>');
            }
        });
    },
    
    updateLeaveTypeInfo: function(leaveType) {
        // This method could show different validation rules or info based on leave type
        // For example, sick leave might require medical certificate for longer periods
    },
    
    onSave: function() {
        this.saveLeaveRequest('pending');
    },
    
    onSaveDraft: function() {
        this.saveLeaveRequest('draft');
    },
    
    saveLeaveRequest: function(status) {
        var me = this,
            form = me.down('form').getForm();
        
        // Set different validation rules based on status
        if (status === 'pending') {
            // Full validation for submitted requests
            if (!form.isValid()) {
                return;
            }
        } else {
            // Minimal validation for drafts
            var employeeField = form.findField('employee_id');
            if (!employeeField.getValue()) {
                Ext.Msg.alert('Validation Error', 'Please select an employee before saving.');
                return;
            }
        }
        
        var values = form.getValues();
        values.status = status;
        
        // Format dates
        if (values.start_date) {
            values.start_date = Ext.Date.format(values.start_date, 'Y-m-d');
        }
        if (values.end_date) {
            values.end_date = Ext.Date.format(values.end_date, 'Y-m-d');
        }
        
        // Calculate total days
        me.calculateLeaveDays();
        values.total_days = parseFloat(form.findField('total_days').getValue()) || 0;
        
        // Show loading mask
        var loadingText = status === 'pending' ? 'Submitting leave request...' : 'Saving draft...';
        me.setLoading(loadingText);
        
        // Determine URL and method based on mode
        var url = me.mode === 'create' ? '/api/hr/leave-requests' : '/api/hr/leave-requests/' + me.leaveRequestRecord.get('id');
        var method = me.mode === 'create' ? 'POST' : 'PUT';
        
        // Use draft endpoint for drafts
        if (status === 'draft' && me.mode === 'create') {
            url = '/api/hr/leave-requests/draft';
        }
        
        // Make AJAX request
        Ext.Ajax.request({
            url: url,
            method: method,
            jsonData: values,
            
            success: function(response) {
                me.setLoading(false);
                var result = Ext.decode(response.responseText);
                
                if (result.success) {
                    var message = status === 'pending' 
                        ? 'Leave request submitted successfully! You will be notified of the approval status.'
                        : 'Leave request draft saved successfully!';
                    
                    Ext.Msg.show({
                        title: 'Success',
                        message: message,
                        buttons: Ext.Msg.OK,
                        icon: Ext.Msg.INFO,
                        fn: function() {
                            me.close();
                            // Refresh leave requests grid if it exists
                            var grid = Ext.ComponentQuery.query('leaverequestpanel grid')[0];
                            if (grid) {
                                grid.getStore().load();
                            }
                        }
                    });
                } else {
                    Ext.Msg.alert('Error', result.message || 'An error occurred while saving the leave request.');
                }
            },
            
            failure: function(response) {
                me.setLoading(false);
                var result;
                
                try {
                    result = Ext.decode(response.responseText);
                } catch (e) {
                    result = {};
                }
                
                if (response.status === 422 && result.errors) {
                    // Handle validation errors
                    me.showValidationErrors(result.errors);
                } else {
                    var message = result.message || 'An error occurred while saving the leave request. Please try again.';
                    Ext.Msg.alert('Error', message);
                }
            }
        });
    },
    
    showValidationErrors: function(errors) {
        var form = this.down('form').getForm();
        
        // Clear existing errors
        form.getFields().each(function(field) {
            field.clearInvalid();
        });
        
        // Show new errors
        Ext.Object.each(errors, function(fieldName, messages) {
            var field = form.findField(fieldName);
            if (field) {
                field.markInvalid(messages.join(', '));
            }
        });
    }
});