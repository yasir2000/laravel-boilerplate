/**
 * Position Form Window
 * Professional form for creating and editing positions
 */
Ext.define('HRApp.view.position.PositionForm', {
    extend: 'Ext.window.Window',
    xtype: 'positionform',
    
    requires: [
        'HRApp.store.DepartmentStore'
    ],
    
    modal: true,
    resizable: true,
    width: 650,
    height: 550,
    layout: 'fit',
    
    // Configuration properties
    mode: 'create', // 'create' or 'edit'
    positionRecord: null,
    departmentId: null, // Pre-selected department
    
    initComponent: function() {
        var me = this;
        
        // Set title based on mode
        me.title = me.mode === 'create' 
            ? '<i class="fa fa-plus-square"></i> Add New Position' 
            : '<i class="fa fa-edit"></i> Edit Position';
        
        // Create form panel
        me.items = [{
            xtype: 'form',
            itemId: 'positionForm',
            layout: 'anchor',
            bodyPadding: 20,
            trackResetOnLoad: true,
            autoScroll: true,
            
            defaults: {
                anchor: '100%',
                labelWidth: 130,
                msgTarget: 'side'
            },
            
            items: [{
                xtype: 'fieldset',
                title: 'Basic Information',
                defaults: {
                    anchor: '100%',
                    labelWidth: 130
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
                        name: 'title',
                        fieldLabel: 'Position Title *',
                        allowBlank: false,
                        maxLength: 100,
                        enforceMaxLength: true,
                        listeners: {
                            blur: function(field) {
                                // Auto-generate code from title if in create mode
                                var form = field.up('form');
                                var codeField = form.down('[name=code]');
                                var value = field.getValue();
                                
                                if (me.mode === 'create' && value && !codeField.getValue()) {
                                    var code = value.toUpperCase()
                                                   .replace(/[^A-Z0-9\s]/g, '')
                                                   .replace(/\s+/g, '-')
                                                   .substring(0, 20);
                                    codeField.setValue(code);
                                }
                            }
                        }
                    }, {
                        xtype: 'textfield',
                        name: 'code',
                        fieldLabel: 'Position Code *',
                        allowBlank: false,
                        maxLength: 20,
                        enforceMaxLength: true,
                        validator: function(value) {
                            if (!value) return true;
                            return /^[A-Z0-9\-_]+$/.test(value) || 'Code can only contain uppercase letters, numbers, hyphens, and underscores';
                        },
                        listeners: {
                            blur: function(field) {
                                // Auto-uppercase the code
                                var value = field.getValue();
                                if (value) {
                                    field.setValue(value.toUpperCase());
                                }
                            }
                        }
                    }]
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
                    emptyText: 'Select department...',
                    value: me.departmentId || null,
                    listConfig: {
                        itemTpl: [
                            '<div class="department-item">',
                                '<strong>{name}</strong><br/>',
                                '<small style="color: #666;">{code} - {location}</small>',
                            '</div>'
                        ]
                    }
                }, {
                    xtype: 'textareafield',
                    name: 'description',
                    fieldLabel: 'Description',
                    rows: 3,
                    maxLength: 1000,
                    enforceMaxLength: true,
                    emptyText: 'Describe the role, responsibilities, and key duties...'
                }]
            }, {
                xtype: 'fieldset',
                title: 'Position Details',
                defaults: {
                    anchor: '100%',
                    labelWidth: 130
                },
                
                items: [{
                    xtype: 'container',
                    layout: 'hbox',
                    defaults: {
                        flex: 1,
                        margins: '0 10 10 0'
                    },
                    
                    items: [{
                        xtype: 'combobox',
                        name: 'level',
                        fieldLabel: 'Position Level *',
                        allowBlank: false,
                        store: [
                            ['entry', 'Entry Level'],
                            ['junior', 'Junior'],
                            ['mid', 'Mid Level'],
                            ['senior', 'Senior'],
                            ['lead', 'Team Lead'],
                            ['manager', 'Manager'],
                            ['director', 'Director'],
                            ['executive', 'Executive']
                        ],
                        editable: false,
                        value: 'mid'
                    }, {
                        xtype: 'combobox',
                        name: 'employment_type',
                        fieldLabel: 'Employment Type',
                        store: [
                            ['full_time', 'Full Time'],
                            ['part_time', 'Part Time'],
                            ['contract', 'Contract'],
                            ['temporary', 'Temporary'],
                            ['intern', 'Internship']
                        ],
                        editable: false,
                        value: 'full_time'
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
                        name: 'min_salary',
                        fieldLabel: 'Minimum Salary',
                        minValue: 0,
                        allowDecimals: true,
                        decimalPrecision: 2,
                        step: 1000,
                        listeners: {
                            change: function(field, newValue) {
                                var maxSalaryField = field.up('form').down('[name=max_salary]');
                                if (newValue && maxSalaryField.getValue() && newValue > maxSalaryField.getValue()) {
                                    maxSalaryField.setValue(newValue);
                                }
                            }
                        }
                    }, {
                        xtype: 'numberfield',
                        name: 'max_salary',
                        fieldLabel: 'Maximum Salary',
                        minValue: 0,
                        allowDecimals: true,
                        decimalPrecision: 2,
                        step: 1000,
                        listeners: {
                            change: function(field, newValue) {
                                var minSalaryField = field.up('form').down('[name=min_salary]');
                                if (newValue && minSalaryField.getValue() && newValue < minSalaryField.getValue()) {
                                    field.markInvalid('Maximum salary cannot be less than minimum salary');
                                } else {
                                    field.clearInvalid();
                                }
                            }
                        }
                    }]
                }]
            }, {
                xtype: 'fieldset',
                title: 'Requirements & Qualifications',
                defaults: {
                    anchor: '100%',
                    labelWidth: 130
                },
                
                items: [{
                    xtype: 'textareafield',
                    name: 'requirements',
                    fieldLabel: 'Requirements',
                    rows: 4,
                    maxLength: 2000,
                    enforceMaxLength: true,
                    emptyText: 'List required skills, experience, education, and certifications...'
                }, {
                    xtype: 'textareafield',
                    name: 'qualifications',
                    fieldLabel: 'Preferred Qualifications',
                    rows: 3,
                    maxLength: 1000,
                    enforceMaxLength: true,
                    emptyText: 'List preferred but not required qualifications...'
                }, {
                    xtype: 'container',
                    layout: 'hbox',
                    defaults: {
                        flex: 1,
                        margins: '0 10 10 0'
                    },
                    
                    items: [{
                        xtype: 'numberfield',
                        name: 'min_experience_years',
                        fieldLabel: 'Min. Experience (years)',
                        minValue: 0,
                        maxValue: 50,
                        allowDecimals: false
                    }, {
                        xtype: 'combobox',
                        name: 'education_level',
                        fieldLabel: 'Education Level',
                        store: [
                            ['high_school', 'High School'],
                            ['associate', 'Associate Degree'],
                            ['bachelor', 'Bachelor\'s Degree'],
                            ['master', 'Master\'s Degree'],
                            ['phd', 'PhD/Doctorate'],
                            ['professional', 'Professional Certificate']
                        ],
                        editable: false
                    }]
                }]
            }, {
                xtype: 'fieldset',
                title: 'Status & Availability',
                defaults: {
                    anchor: '100%',
                    labelWidth: 130
                },
                
                items: [{
                    xtype: 'container',
                    layout: 'hbox',
                    defaults: {
                        flex: 1,
                        margins: '0 10 10 0'
                    },
                    
                    items: [{
                        xtype: 'checkboxfield',
                        name: 'is_active',
                        fieldLabel: 'Status',
                        boxLabel: 'Position is active and available',
                        checked: true
                    }, {
                        xtype: 'numberfield',
                        name: 'max_positions',
                        fieldLabel: 'Max. Openings',
                        minValue: 1,
                        maxValue: 100,
                        allowDecimals: false,
                        value: 1
                    }]
                }]
            }, {
                xtype: 'fieldset',
                title: 'Additional Information',
                collapsible: true,
                collapsed: me.mode === 'create',
                defaults: {
                    anchor: '100%',
                    labelWidth: 130
                },
                
                items: [{
                    xtype: 'displayfield',
                    fieldLabel: 'Position ID',
                    name: 'id',
                    hidden: me.mode === 'create'
                }, {
                    xtype: 'displayfield',
                    fieldLabel: 'Current Employees',
                    name: 'employees_count',
                    hidden: me.mode === 'create',
                    value: 0
                }, {
                    xtype: 'displayfield',
                    fieldLabel: 'Created Date',
                    name: 'created_at',
                    hidden: me.mode === 'create',
                    renderer: function(value) {
                        return value ? Ext.Date.format(new Date(value), 'M d, Y g:i A') : '';
                    }
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
        if (me.mode === 'edit' && me.positionRecord) {
            me.down('form').loadRecord(me.positionRecord);
        }
    },
    
    onSave: function() {
        var me = this,
            form = me.down('form').getForm();
        
        if (form.isValid()) {
            var values = form.getValues();
            
            // Validate salary range
            if (values.min_salary && values.max_salary && parseFloat(values.min_salary) > parseFloat(values.max_salary)) {
                Ext.Msg.alert('Validation Error', 'Maximum salary cannot be less than minimum salary.');
                return;
            }
            
            // Convert empty strings to null for optional fields
            ['min_salary', 'max_salary', 'requirements', 'qualifications', 'min_experience_years', 'education_level'].forEach(function(field) {
                if (values[field] === '') {
                    values[field] = null;
                }
            });
            
            // Show loading mask
            me.setLoading('Saving position...');
            
            // Determine URL and method based on mode
            var url = me.mode === 'create' ? '/api/hr/positions' : '/api/hr/positions/' + me.positionRecord.get('id');
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
                            message: me.mode === 'create' ? 'Position created successfully!' : 'Position updated successfully!',
                            buttons: Ext.Msg.OK,
                            icon: Ext.Msg.INFO,
                            fn: function() {
                                me.close();
                                // Refresh position grid if it exists
                                var grid = Ext.ComponentQuery.query('positionpanel grid')[0];
                                if (grid) {
                                    grid.getStore().load();
                                }
                            }
                        });
                    } else {
                        Ext.Msg.alert('Error', result.message || 'An error occurred while saving the position.');
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
                        var message = result.message || 'An error occurred while saving the position. Please try again.';
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