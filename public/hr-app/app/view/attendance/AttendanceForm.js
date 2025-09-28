/**
 * Attendance Form Window
 * Professional form for managing attendance records
 */
Ext.define('HRApp.view.attendance.AttendanceForm', {
    extend: 'Ext.window.Window',
    xtype: 'attendanceform',
    
    requires: [
        'HRApp.store.EmployeeStore'
    ],
    
    modal: true,
    resizable: true,
    width: 600,
    height: 450,
    layout: 'fit',
    
    // Configuration properties
    mode: 'create', // 'create' or 'edit'
    attendanceRecord: null,
    employeeId: null, // Pre-selected employee
    
    initComponent: function() {
        var me = this;
        
        // Set title based on mode
        me.title = me.mode === 'create' 
            ? '<i class="fa fa-clock-o"></i> Record Attendance' 
            : '<i class="fa fa-edit"></i> Edit Attendance Record';
        
        // Create form panel
        me.items = [{
            xtype: 'form',
            itemId: 'attendanceForm',
            layout: 'anchor',
            bodyPadding: 20,
            trackResetOnLoad: true,
            
            defaults: {
                anchor: '100%',
                labelWidth: 120,
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
                            '<small>{employee_id} - {department_name} - {position_title}</small>',
                        '</div>'
                    ]
                },
                value: me.employeeId || null
            }, {
                xtype: 'datefield',
                name: 'date',
                fieldLabel: 'Date *',
                allowBlank: false,
                format: 'Y-m-d',
                value: new Date(),
                maxValue: new Date() // Cannot record future attendance
            }, {
                xtype: 'container',
                layout: 'hbox',
                defaults: {
                    flex: 1,
                    margins: '0 10 10 0'
                },
                
                items: [{
                    xtype: 'timefield',
                    name: 'check_in',
                    fieldLabel: 'Check In *',
                    allowBlank: false,
                    format: 'H:i',
                    increment: 15,
                    value: '09:00',
                    listeners: {
                        change: function(field, newValue) {
                            // Auto-calculate hours when check-in changes
                            me.calculateWorkHours();
                        }
                    }
                }, {
                    xtype: 'timefield',
                    name: 'check_out',
                    fieldLabel: 'Check Out',
                    format: 'H:i',
                    increment: 15,
                    value: '17:30',
                    listeners: {
                        change: function(field, newValue) {
                            // Auto-calculate hours when check-out changes
                            me.calculateWorkHours();
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
                    xtype: 'numberfield',
                    name: 'break_duration',
                    fieldLabel: 'Break Duration (min)',
                    value: 60,
                    minValue: 0,
                    maxValue: 480,
                    allowDecimals: false,
                    step: 15,
                    listeners: {
                        change: function(field, newValue) {
                            // Recalculate work hours when break changes
                            me.calculateWorkHours();
                        }
                    }
                }, {
                    xtype: 'displayfield',
                    name: 'total_hours',
                    fieldLabel: 'Total Work Hours',
                    value: '8.0',
                    cls: 'hr-total-hours'
                }]
            }, {
                xtype: 'combobox',
                name: 'status',
                fieldLabel: 'Status *',
                allowBlank: false,
                store: [
                    ['present', 'Present'],
                    ['absent', 'Absent'],
                    ['late', 'Late'],
                    ['half_day', 'Half Day'],
                    ['work_from_home', 'Work from Home']
                ],
                editable: false,
                value: 'present',
                listeners: {
                    change: function(combo, newValue) {
                        var form = combo.up('form');
                        var checkOutField = form.down('[name=check_out]');
                        var breakField = form.down('[name=break_duration]');
                        
                        // Adjust fields based on status
                        if (newValue === 'absent') {
                            checkOutField.setDisabled(true);
                            breakField.setDisabled(true);
                            form.down('[name=check_in]').setDisabled(true);
                        } else if (newValue === 'half_day') {
                            checkOutField.setDisabled(false);
                            breakField.setValue(30);
                            form.down('[name=check_in]').setDisabled(false);
                        } else {
                            checkOutField.setDisabled(false);
                            breakField.setDisabled(false);
                            form.down('[name=check_in]').setDisabled(false);
                        }
                        
                        me.calculateWorkHours();
                    }
                }
            }, {
                xtype: 'combobox',
                name: 'overtime_type',
                fieldLabel: 'Overtime Type',
                store: [
                    ['none', 'None'],
                    ['regular', 'Regular Overtime'],
                    ['weekend', 'Weekend Overtime'],
                    ['holiday', 'Holiday Overtime']
                ],
                editable: false,
                value: 'none'
            }, {
                xtype: 'textareafield',
                name: 'notes',
                fieldLabel: 'Notes',
                rows: 3,
                maxLength: 500,
                enforceMaxLength: true,
                emptyText: 'Any additional notes or comments...'
            }, {
                xtype: 'fieldset',
                title: 'Location & Device Info',
                collapsible: true,
                collapsed: true,
                
                items: [{
                    xtype: 'textfield',
                    name: 'location',
                    fieldLabel: 'Location',
                    maxLength: 100,
                    enforceMaxLength: true,
                    emptyText: 'Office, Home, Client Site, etc.'
                }, {
                    xtype: 'textfield',
                    name: 'ip_address',
                    fieldLabel: 'IP Address',
                    readOnly: true,
                    value: 'Auto-detected'
                }]
            }]
        }];
        
        // Define buttons
        me.buttons = [{
            text: '<i class="fa fa-save"></i> Save',
            cls: 'hr-btn-primary',
            handler: me.onSave,
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
        if (me.mode === 'edit' && me.attendanceRecord) {
            me.down('form').loadRecord(me.attendanceRecord);
            me.calculateWorkHours();
        } else {
            // Calculate initial work hours
            me.calculateWorkHours();
        }
        
        // Auto-detect IP address
        me.detectIPAddress();
    },
    
    calculateWorkHours: function() {
        var form = this.down('form');
        var checkIn = form.down('[name=check_in]').getValue();
        var checkOut = form.down('[name=check_out]').getValue();
        var breakDuration = form.down('[name=break_duration]').getValue() || 0;
        var status = form.down('[name=status]').getValue();
        var totalHoursField = form.down('[name=total_hours]');
        
        if (checkIn && checkOut && status !== 'absent') {
            // Convert times to minutes for calculation
            var checkInMinutes = checkIn.getHours() * 60 + checkIn.getMinutes();
            var checkOutMinutes = checkOut.getHours() * 60 + checkOut.getMinutes();
            
            // Handle overnight shifts
            if (checkOutMinutes < checkInMinutes) {
                checkOutMinutes += 24 * 60; // Add 24 hours in minutes
            }
            
            var totalMinutes = checkOutMinutes - checkInMinutes - breakDuration;
            var totalHours = Math.max(0, totalMinutes / 60);
            
            // Apply status-based adjustments
            if (status === 'half_day') {
                totalHours = Math.min(totalHours, 4);
            }
            
            totalHoursField.setValue(totalHours.toFixed(1));
        } else if (status === 'absent') {
            totalHoursField.setValue('0.0');
        } else {
            totalHoursField.setValue('--');
        }
    },
    
    detectIPAddress: function() {
        // In a real application, you might use a service to detect IP
        // For now, we'll just show a placeholder
        var ipField = this.down('form').down('[name=ip_address]');
        if (ipField) {
            // You could make an AJAX call to get the real IP address
            ipField.setValue('Auto-detecting...');
            
            // Simulate IP detection
            setTimeout(function() {
                ipField.setValue('192.168.1.100'); // Example IP
            }, 1000);
        }
    },
    
    onSave: function() {
        var me = this,
            form = me.down('form').getForm();
        
        if (form.isValid()) {
            var values = form.getValues();
            
            // Format time values
            if (values.check_in) {
                values.check_in = Ext.Date.format(values.check_in, 'H:i:s');
            }
            if (values.check_out) {
                values.check_out = Ext.Date.format(values.check_out, 'H:i:s');
            }
            
            // Format date
            if (values.date) {
                values.date = Ext.Date.format(values.date, 'Y-m-d');
            }
            
            // Calculate final work hours
            me.calculateWorkHours();
            values.work_hours = parseFloat(form.findField('total_hours').getValue()) || 0;
            
            // Show loading mask
            me.setLoading('Saving attendance record...');
            
            // Determine URL and method based on mode
            var url = me.mode === 'create' ? '/api/hr/attendance' : '/api/hr/attendance/' + me.attendanceRecord.get('id');
            var method = me.mode === 'create' ? 'POST' : 'PUT';
            
            // Make AJAX request
            Ext.Ajax.request({
                url: url,
                method: method,
                jsonData: values,
                
                success: function(response) {
                    me.setLoading(false);
                    var result = Ext.decode(response.responseText);
                    
                    if (result.success) {
                        Ext.Msg.show({
                            title: 'Success',
                            message: me.mode === 'create' ? 'Attendance recorded successfully!' : 'Attendance updated successfully!',
                            buttons: Ext.Msg.OK,
                            icon: Ext.Msg.INFO,
                            fn: function() {
                                me.close();
                                // Refresh attendance grid if it exists
                                var grid = Ext.ComponentQuery.query('attendancepanel grid')[0];
                                if (grid) {
                                    grid.getStore().load();
                                }
                            }
                        });
                    } else {
                        Ext.Msg.alert('Error', result.message || 'An error occurred while saving the attendance record.');
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
                        var message = result.message || 'An error occurred while saving the attendance record. Please try again.';
                        Ext.Msg.alert('Error', message);
                    }
                }
            });
        }
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