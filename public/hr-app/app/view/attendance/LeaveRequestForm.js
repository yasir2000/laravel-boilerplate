/**
 * Leave Request Form Window
 * Form for creating and managing leave requests
 */
Ext.define('HRApp.view.attendance.LeaveRequestForm', {
    extend: 'Ext.window.Window',
    xtype: 'leaverequestform',
    
    modal: true,
    width: 500,
    height: 450,
    layout: 'fit',
    resizable: false,
    
    title: '<i class="fa fa-calendar-plus-o"></i> Leave Request',
    
    initComponent: function() {
        var me = this;
        
        me.items = [{
            xtype: 'form',
            itemId: 'leaveForm',
            bodyPadding: 20,
            autoScroll: true,
            
            fieldDefaults: {
                labelWidth: 100,
                anchor: '100%',
                labelAlign: 'right'
            },
            
            items: [{
                xtype: 'fieldset',
                title: 'Leave Details',
                items: [{
                    xtype: 'combobox',
                    name: 'leave_type',
                    fieldLabel: 'Leave Type',
                    allowBlank: false,
                    store: [
                        ['vacation', 'Vacation'],
                        ['sick', 'Sick Leave'],
                        ['personal', 'Personal Leave'],
                        ['emergency', 'Emergency Leave'],
                        ['maternity', 'Maternity Leave'],
                        ['paternity', 'Paternity Leave'],
                        ['bereavement', 'Bereavement Leave'],
                        ['study', 'Study Leave']
                    ],
                    queryMode: 'local',
                    displayField: 'text',
                    valueField: 'value',
                    
                    listeners: {
                        change: function(combo, value) {
                            var form = combo.up('form');
                            var daysField = form.down('[name=total_days]');
                            
                            // Set default days based on leave type
                            switch(value) {
                                case 'sick':
                                    daysField.setValue(1);
                                    break;
                                case 'maternity':
                                    daysField.setValue(90);
                                    break;
                                case 'paternity':
                                    daysField.setValue(15);
                                    break;
                                case 'bereavement':
                                    daysField.setValue(3);
                                    break;
                                default:
                                    daysField.setValue(1);
                            }
                        }
                    }
                }, {
                    xtype: 'datefield',
                    name: 'start_date',
                    fieldLabel: 'Start Date',
                    allowBlank: false,
                    minValue: new Date(),
                    format: 'Y-m-d',
                    
                    listeners: {
                        change: function(field, value) {
                            var endDateField = field.up('form').down('[name=end_date]');
                            if (value) {
                                endDateField.setMinValue(value);
                                if (!endDateField.getValue() || endDateField.getValue() < value) {
                                    endDateField.setValue(value);
                                }
                            }
                            field.up('form').calculateDays();
                        }
                    }
                }, {
                    xtype: 'datefield',
                    name: 'end_date',
                    fieldLabel: 'End Date',
                    allowBlank: false,
                    format: 'Y-m-d',
                    
                    listeners: {
                        change: function(field, value) {
                            field.up('form').calculateDays();
                        }
                    }
                }, {
                    xtype: 'numberfield',
                    name: 'total_days',
                    fieldLabel: 'Total Days',
                    minValue: 0.5,
                    step: 0.5,
                    value: 1,
                    readOnly: true,
                    cls: 'calculated-field'
                }]
            }, {
                xtype: 'fieldset',
                title: 'Additional Information',
                items: [{
                    xtype: 'textareafield',
                    name: 'reason',
                    fieldLabel: 'Reason',
                    allowBlank: false,
                    height: 80,
                    maxLength: 500,
                    emptyText: 'Please provide a detailed reason for your leave request...'
                }, {
                    xtype: 'checkboxfield',
                    name: 'is_half_day',
                    fieldLabel: 'Half Day',
                    boxLabel: 'This is a half-day leave',
                    
                    listeners: {
                        change: function(checkbox, checked) {
                            var form = checkbox.up('form');
                            var daysField = form.down('[name=total_days]');
                            
                            if (checked) {
                                daysField.setValue(0.5);
                            } else {
                                form.calculateDays();
                            }
                        }
                    }
                }, {
                    xtype: 'checkboxfield',
                    name: 'is_emergency',
                    fieldLabel: 'Emergency',
                    boxLabel: 'This is an emergency leave request'
                }]
            }, {
                xtype: 'fieldset',
                title: 'Coverage Information',
                items: [{
                    xtype: 'combobox',
                    name: 'covering_employee_id',
                    fieldLabel: 'Coverage By',
                    store: Ext.create('HRApp.store.EmployeeComboStore'),
                    displayField: 'name',
                    valueField: 'id',
                    queryMode: 'remote',
                    emptyText: 'Select employee to cover duties...',
                    
                    listConfig: {
                        getInnerTpl: function() {
                            return '<div class="emp-combo-item">' +
                                   '<div class="emp-name">{name}</div>' +
                                   '<div class="emp-position">{position}</div>' +
                                   '</div>';
                        }
                    }
                }, {
                    xtype: 'textareafield',
                    name: 'coverage_notes',
                    fieldLabel: 'Coverage Notes',
                    height: 60,
                    maxLength: 300,
                    emptyText: 'Instructions for the covering employee...'
                }]
            }],
            
            buttons: [{
                text: 'Submit Request',
                formBind: true,
                cls: 'hr-btn-primary',
                handler: function() {
                    me.submitLeaveRequest();
                }
            }, {
                text: 'Save as Draft',
                handler: function() {
                    me.saveDraft();
                }
            }, {
                text: 'Cancel',
                handler: function() {
                    me.close();
                }
            }],
            
            // Custom methods for the form
            calculateDays: function() {
                var form = this;
                var startDate = form.down('[name=start_date]').getValue();
                var endDate = form.down('[name=end_date]').getValue();
                var daysField = form.down('[name=total_days]');
                var isHalfDay = form.down('[name=is_half_day]').getValue();
                
                if (startDate && endDate && !isHalfDay) {
                    var timeDiff = endDate.getTime() - startDate.getTime();
                    var daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                    daysField.setValue(Math.max(1, daysDiff));
                }
            }
        }];
        
        me.callParent();
        
        // Load data if editing
        if (me.leaveData) {
            me.down('form').getForm().setValues(me.leaveData);
        }
    },
    
    submitLeaveRequest: function() {
        var me = this,
            form = me.down('form'),
            values = form.getValues();
            
        if (!form.getForm().isValid()) {
            Ext.Msg.alert('Validation Error', 'Please correct the errors in the form');
            return;
        }
        
        // Validate dates
        if (new Date(values.start_date) > new Date(values.end_date)) {
            Ext.Msg.alert('Date Error', 'End date must be after or equal to start date');
            return;
        }
        
        form.setLoading('Submitting leave request...');
        
        var url = me.leaveData ? '/api/hr/leave-requests/' + me.leaveData.id : '/api/hr/leave-requests';
        var method = me.leaveData ? 'PUT' : 'POST';
        
        // Add status for new requests
        if (!me.leaveData) {
            values.status = 'pending';
        }
        
        Ext.Ajax.request({
            url: url,
            method: method,
            jsonData: values,
            success: function(response) {
                form.setLoading(false);
                
                var result = Ext.decode(response.responseText);
                
                Ext.toast({
                    html: 'Leave request submitted successfully. Request ID: #' + result.data.id,
                    closable: false,
                    align: 't',
                    slideInDuration: 400,
                    minWidth: 400
                });
                
                // Refresh the leave requests grid if it exists
                var leaveGrid = Ext.ComponentQuery.query('attendancepanel #leaveGrid')[0];
                if (leaveGrid) {
                    leaveGrid.getStore().reload();
                }
                
                me.close();
            },
            failure: function(response) {
                form.setLoading(false);
                
                var error = Ext.decode(response.responseText);
                var message = 'Failed to submit leave request';
                
                if (error.errors) {
                    message += ':\n\n';
                    Ext.Object.each(error.errors, function(field, messages) {
                        message += '• ' + messages.join(', ') + '\n';
                    });
                } else if (error.message) {
                    message = error.message;
                }
                
                Ext.Msg.alert('Error', message);
            }
        });
    },
    
    saveDraft: function() {
        var me = this,
            form = me.down('form'),
            values = form.getValues();
        
        values.status = 'draft';
        
        form.setLoading('Saving draft...');
        
        Ext.Ajax.request({
            url: '/api/hr/leave-requests/draft',
            method: 'POST',
            jsonData: values,
            success: function(response) {
                form.setLoading(false);
                
                Ext.toast({
                    html: 'Leave request saved as draft',
                    closable: false,
                    align: 't',
                    slideInDuration: 400
                });
                
                me.close();
            },
            failure: function(response) {
                form.setLoading(false);
                
                var error = Ext.decode(response.responseText);
                Ext.Msg.alert('Error', error.message || 'Failed to save draft');
            }
        });
    }
});