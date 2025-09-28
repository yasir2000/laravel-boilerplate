/**
 * Employee Form Window
 * Professional form for creating and editing employees
 */
Ext.define('HRApp.view.employee.EmployeeForm', {
    extend: 'Ext.window.Window',
    xtype: 'employeeform',
    
    requires: [
        'HRApp.store.DepartmentStore',
        'HRApp.store.PositionStore'
    ],
    
    modal: true,
    resizable: true,
    width: 900,
    height: 700,
    maxHeight: 800,
    layout: 'fit',
    
    // Configuration properties
    mode: 'create', // 'create' or 'edit'
    employeeRecord: null,
    
    initComponent: function() {
        var me = this;
        
        // Set title based on mode
        me.title = me.mode === 'create' 
            ? '<i class="fa fa-user-plus"></i> Add New Employee' 
            : '<i class="fa fa-user-edit"></i> Edit Employee';
        
        // Create form panel with tabs for organized sections
        me.items = [{
            xtype: 'form',
            itemId: 'employeeForm',
            layout: 'fit',
            bodyPadding: 0,
            trackResetOnLoad: true,
            
            items: [{
                xtype: 'tabpanel',
                itemId: 'employeeTabs',
                
                items: [{
                    title: 'Basic Information',
                    iconCls: 'fa fa-user',
                    layout: 'anchor',
                    bodyPadding: 20,
                    defaults: {
                        anchor: '100%',
                        labelWidth: 150,
                        msgTarget: 'side'
                    },
                    
                    items: [{
                        xtype: 'container',
                        layout: 'hbox',
                        defaults: {
                            flex: 1,
                            margins: '0 10 10 0'
                        },
                        
                        items: [{
                            xtype: 'textfield',
                            name: 'employee_id',
                            fieldLabel: 'Employee ID *',
                            allowBlank: false,
                            maxLength: 20,
                            enforceMaxLength: true,
                            validator: function(value) {
                                if (!value) return true;
                                return /^[A-Z0-9\-]+$/.test(value) || 'Employee ID can only contain uppercase letters, numbers, and hyphens';
                            }
                        }, {
                            xtype: 'combobox',
                            name: 'department_id',
                            fieldLabel: 'Department *',
                            allowBlank: false,
                            store: Ext.create('HRApp.store.DepartmentStore'),
                            displayField: 'name',
                            valueField: 'id',
                            queryMode: 'local',
                            editable: false,
                            listeners: {
                                change: function(combo, newValue) {
                                    // Update positions store based on selected department
                                    var positionCombo = combo.up('form').down('combobox[name=position_id]');
                                    var positionStore = positionCombo.getStore();
                                    positionStore.clearFilter();
                                    if (newValue) {
                                        positionStore.filter('department_id', newValue);
                                    }
                                    positionCombo.clearValue();
                                }
                            }
                        }]
                    }, {
                        xtype: 'combobox',
                        name: 'position_id',
                        fieldLabel: 'Position *',
                        allowBlank: false,
                        store: Ext.create('HRApp.store.PositionStore'),
                        displayField: 'title',
                        valueField: 'id',
                        queryMode: 'local',
                        editable: false,
                        anchor: '50%'
                    }, {
                        xtype: 'container',
                        layout: 'hbox',
                        defaults: {
                            flex: 1,
                            margins: '0 10 10 0'
                        },
                        
                        items: [{
                            xtype: 'textfield',
                            name: 'first_name',
                            fieldLabel: 'First Name *',
                            allowBlank: false,
                            maxLength: 50,
                            enforceMaxLength: true
                        }, {
                            xtype: 'textfield',
                            name: 'middle_name',
                            fieldLabel: 'Middle Name',
                            maxLength: 50,
                            enforceMaxLength: true
                        }, {
                            xtype: 'textfield',
                            name: 'last_name',
                            fieldLabel: 'Last Name *',
                            allowBlank: false,
                            maxLength: 50,
                            enforceMaxLength: true
                        }]
                    }, {
                        xtype: 'container',
                        layout: 'hbox',
                        defaults: {
                            flex: 1,
                            margins: '0 10 10 0'
                        },
                        
                        items: [{
                            xtype: 'datefield',
                            name: 'date_of_birth',
                            fieldLabel: 'Date of Birth',
                            format: 'Y-m-d',
                            maxValue: new Date()
                        }, {
                            xtype: 'combobox',
                            name: 'gender',
                            fieldLabel: 'Gender',
                            store: ['male', 'female', 'other'],
                            editable: false
                        }, {
                            xtype: 'combobox',
                            name: 'marital_status',
                            fieldLabel: 'Marital Status',
                            store: ['single', 'married', 'divorced', 'widowed'],
                            editable: false
                        }]
                    }, {
                        xtype: 'container',
                        layout: 'hbox',
                        defaults: {
                            flex: 1,
                            margins: '0 10 10 0'
                        },
                        
                        items: [{
                            xtype: 'textfield',
                            name: 'personal_email',
                            fieldLabel: 'Personal Email',
                            vtype: 'email',
                            maxLength: 100,
                            enforceMaxLength: true
                        }, {
                            xtype: 'textfield',
                            name: 'phone',
                            fieldLabel: 'Phone',
                            maxLength: 20,
                            enforceMaxLength: true
                        }, {
                            xtype: 'textfield',
                            name: 'mobile',
                            fieldLabel: 'Mobile',
                            maxLength: 20,
                            enforceMaxLength: true
                        }]
                    }]
                }, {
                    title: 'Employment Details',
                    iconCls: 'fa fa-briefcase',
                    layout: 'anchor',
                    bodyPadding: 20,
                    defaults: {
                        anchor: '100%',
                        labelWidth: 150,
                        msgTarget: 'side'
                    },
                    
                    items: [{
                        xtype: 'container',
                        layout: 'hbox',
                        defaults: {
                            flex: 1,
                            margins: '0 10 10 0'
                        },
                        
                        items: [{
                            xtype: 'datefield',
                            name: 'hire_date',
                            fieldLabel: 'Hire Date *',
                            allowBlank: false,
                            format: 'Y-m-d',
                            value: new Date()
                        }, {
                            xtype: 'combobox',
                            name: 'employment_type',
                            fieldLabel: 'Employment Type *',
                            allowBlank: false,
                            store: [
                                ['full_time', 'Full Time'],
                                ['part_time', 'Part Time'],
                                ['contract', 'Contract'],
                                ['intern', 'Intern']
                            ],
                            editable: false,
                            value: 'full_time'
                        }, {
                            xtype: 'combobox',
                            name: 'employment_status',
                            fieldLabel: 'Status *',
                            allowBlank: false,
                            store: [
                                ['active', 'Active'],
                                ['inactive', 'Inactive'],
                                ['terminated', 'Terminated'],
                                ['on_leave', 'On Leave']
                            ],
                            editable: false,
                            value: 'active'
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
                            name: 'salary',
                            fieldLabel: 'Salary *',
                            allowBlank: false,
                            minValue: 0,
                            allowDecimals: true,
                            decimalPrecision: 2,
                            step: 100
                        }, {
                            xtype: 'combobox',
                            name: 'salary_type',
                            fieldLabel: 'Salary Type',
                            store: [
                                ['monthly', 'Monthly'],
                                ['yearly', 'Yearly'],
                                ['hourly', 'Hourly']
                            ],
                            editable: false,
                            value: 'monthly'
                        }, {
                            xtype: 'textfield',
                            name: 'salary_currency',
                            fieldLabel: 'Currency',
                            value: 'USD',
                            maxLength: 3,
                            enforceMaxLength: true
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
                            name: 'work_hours_per_week',
                            fieldLabel: 'Hours/Week',
                            value: 40,
                            minValue: 1,
                            maxValue: 80,
                            allowDecimals: false
                        }, {
                            xtype: 'textfield',
                            name: 'work_location',
                            fieldLabel: 'Work Location',
                            maxLength: 100,
                            enforceMaxLength: true
                        }, {
                            xtype: 'checkboxfield',
                            name: 'remote_work_allowed',
                            fieldLabel: 'Remote Work',
                            boxLabel: 'Allowed'
                        }]
                    }, {
                        xtype: 'container',
                        layout: 'hbox',
                        defaults: {
                            flex: 1,
                            margins: '0 10 10 0'
                        },
                        
                        items: [{
                            xtype: 'datefield',
                            name: 'contract_start_date',
                            fieldLabel: 'Contract Start',
                            format: 'Y-m-d'
                        }, {
                            xtype: 'datefield',
                            name: 'contract_end_date',
                            fieldLabel: 'Contract End',
                            format: 'Y-m-d'
                        }]
                    }]
                }, {
                    title: 'Address & Emergency',
                    iconCls: 'fa fa-home',
                    layout: 'anchor',
                    bodyPadding: 20,
                    defaults: {
                        anchor: '100%',
                        labelWidth: 150,
                        msgTarget: 'side'
                    },
                    
                    items: [{
                        xtype: 'textareafield',
                        name: 'address',
                        fieldLabel: 'Address',
                        rows: 3,
                        maxLength: 500,
                        enforceMaxLength: true
                    }, {
                        xtype: 'container',
                        layout: 'hbox',
                        defaults: {
                            flex: 1,
                            margins: '0 10 10 0'
                        },
                        
                        items: [{
                            xtype: 'textfield',
                            name: 'city',
                            fieldLabel: 'City',
                            maxLength: 50,
                            enforceMaxLength: true
                        }, {
                            xtype: 'textfield',
                            name: 'state',
                            fieldLabel: 'State/Province',
                            maxLength: 50,
                            enforceMaxLength: true
                        }, {
                            xtype: 'textfield',
                            name: 'postal_code',
                            fieldLabel: 'Postal Code',
                            maxLength: 20,
                            enforceMaxLength: true
                        }]
                    }, {
                        xtype: 'textfield',
                        name: 'country',
                        fieldLabel: 'Country',
                        anchor: '50%',
                        maxLength: 50,
                        enforceMaxLength: true
                    }, {
                        xtype: 'container',
                        layout: 'hbox',
                        defaults: {
                            flex: 1,
                            margins: '0 10 10 0'
                        },
                        
                        items: [{
                            xtype: 'textfield',
                            name: 'emergency_contact_name',
                            fieldLabel: 'Emergency Contact',
                            maxLength: 100,
                            enforceMaxLength: true
                        }, {
                            xtype: 'textfield',
                            name: 'emergency_contact_phone',
                            fieldLabel: 'Emergency Phone',
                            maxLength: 20,
                            enforceMaxLength: true
                        }]
                    }, {
                        xtype: 'container',
                        layout: 'hbox',
                        defaults: {
                            flex: 1,
                            margins: '0 10 10 0'
                        },
                        
                        items: [{
                            xtype: 'textfield',
                            name: 'national_id',
                            fieldLabel: 'National ID',
                            maxLength: 50,
                            enforceMaxLength: true
                        }, {
                            xtype: 'textfield',
                            name: 'passport_number',
                            fieldLabel: 'Passport Number',
                            maxLength: 50,
                            enforceMaxLength: true
                        }, {
                            xtype: 'textfield',
                            name: 'nationality',
                            fieldLabel: 'Nationality',
                            maxLength: 50,
                            enforceMaxLength: true
                        }]
                    }]
                }, {
                    title: 'Benefits & Leave',
                    iconCls: 'fa fa-calendar-check',
                    layout: 'anchor',
                    bodyPadding: 20,
                    defaults: {
                        anchor: '100%',
                        labelWidth: 150,
                        msgTarget: 'side'
                    },
                    
                    items: [{
                        xtype: 'container',
                        layout: 'hbox',
                        defaults: {
                            flex: 1,
                            margins: '0 10 10 0'
                        },
                        
                        items: [{
                            xtype: 'numberfield',
                            name: 'vacation_days_per_year',
                            fieldLabel: 'Vacation Days/Year',
                            value: 21,
                            minValue: 0,
                            maxValue: 365,
                            allowDecimals: false
                        }, {
                            xtype: 'numberfield',
                            name: 'sick_days_per_year',
                            fieldLabel: 'Sick Days/Year',
                            value: 10,
                            minValue: 0,
                            maxValue: 365,
                            allowDecimals: false
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
                            name: 'vacation_days_used',
                            fieldLabel: 'Vacation Used',
                            value: 0,
                            minValue: 0,
                            allowDecimals: false,
                            readOnly: me.mode === 'create'
                        }, {
                            xtype: 'numberfield',
                            name: 'sick_days_used',
                            fieldLabel: 'Sick Days Used',
                            value: 0,
                            minValue: 0,
                            allowDecimals: false,
                            readOnly: me.mode === 'create'
                        }]
                    }]
                }, {
                    title: 'Additional Information',
                    iconCls: 'fa fa-info-circle',
                    layout: 'anchor',
                    bodyPadding: 20,
                    defaults: {
                        anchor: '100%',
                        labelWidth: 150,
                        msgTarget: 'side'
                    },
                    
                    items: [{
                        xtype: 'textareafield',
                        name: 'notes',
                        fieldLabel: 'Notes',
                        rows: 4,
                        maxLength: 2000,
                        enforceMaxLength: true
                    }]
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
        if (me.mode === 'edit' && me.employeeRecord) {
            me.down('form').loadRecord(me.employeeRecord);
        }
    },
    
    onSave: function() {
        var me = this,
            form = me.down('form').getForm();
        
        if (form.isValid()) {
            var values = form.getValues();
            
            // Show loading mask
            me.setLoading('Saving employee...');
            
            // Determine URL and method based on mode
            var url = me.mode === 'create' ? '/api/hr/employees' : '/api/hr/employees/' + me.employeeRecord.get('id');
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
                            message: me.mode === 'create' ? 'Employee created successfully!' : 'Employee updated successfully!',
                            buttons: Ext.Msg.OK,
                            icon: Ext.Msg.INFO,
                            fn: function() {
                                me.close();
                                // Refresh employee grid if it exists
                                var grid = Ext.ComponentQuery.query('employeepanel grid')[0];
                                if (grid) {
                                    grid.getStore().load();
                                }
                            }
                        });
                    } else {
                        Ext.Msg.alert('Error', result.message || 'An error occurred while saving the employee.');
                    }
                },
                
                failure: function(response) {
                    me.setLoading(false);
                    var result = Ext.decode(response.responseText);
                    
                    if (response.status === 422 && result.errors) {
                        // Handle validation errors
                        me.showValidationErrors(result.errors);
                    } else {
                        Ext.Msg.alert('Error', 'An error occurred while saving the employee. Please try again.');
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