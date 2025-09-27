/**
 * Department Form Window
 * Form for creating and editing departments
 */
Ext.define('HRApp.view.department.DepartmentForm', {
    extend: 'Ext.window.Window',
    xtype: 'departmentform',
    
    modal: true,
    width: 600,
    height: 550,
    layout: 'fit',
    resizable: false,
    
    initComponent: function() {
        var me = this;
        
        me.items = [{
            xtype: 'form',
            itemId: 'departmentForm',
            bodyPadding: 20,
            autoScroll: true,
            
            fieldDefaults: {
                labelWidth: 120,
                anchor: '100%',
                labelAlign: 'right'
            },
            
            items: [{
                xtype: 'fieldset',
                title: 'Basic Information',
                items: [{
                    xtype: 'textfield',
                    name: 'name',
                    fieldLabel: 'Department Name',
                    allowBlank: false,
                    maxLength: 100,
                    listeners: {
                        change: function(field, value) {
                            // Auto-generate code from name if in create mode
                            if (me.mode === 'create' && value) {
                                var codeField = field.up('form').down('[name=code]');
                                if (!codeField.getValue()) {
                                    var code = value.toUpperCase()
                                                   .replace(/[^A-Z0-9\s]/g, '')
                                                   .replace(/\s+/g, '_')
                                                   .substring(0, 10);
                                    codeField.setValue(code);
                                }
                            }
                        }
                    }
                }, {
                    xtype: 'textfield',
                    name: 'code',
                    fieldLabel: 'Department Code',
                    allowBlank: false,
                    maxLength: 20,
                    regex: /^[A-Z0-9_]+$/,
                    regexText: 'Code must contain only uppercase letters, numbers, and underscores'
                }, {
                    xtype: 'textareafield',
                    name: 'description',
                    fieldLabel: 'Description',
                    height: 80,
                    maxLength: 500
                }, {
                    xtype: 'textfield',
                    name: 'location',
                    fieldLabel: 'Location',
                    maxLength: 100
                }]
            }, {
                xtype: 'fieldset',
                title: 'Organization Structure',
                items: [{
                    xtype: 'combobox',
                    name: 'parent_id',
                    fieldLabel: 'Parent Department',
                    store: Ext.create('HRApp.store.DepartmentComboStore'),
                    displayField: 'name',
                    valueField: 'id',
                    queryMode: 'remote',
                    emptyText: 'Select parent department...',
                    
                    // Custom list item template
                    listConfig: {
                        getInnerTpl: function() {
                            return '<div class="dept-combo-item">' +
                                   '<div class="dept-name">{name}</div>' +
                                   '<div class="dept-code">({code})</div>' +
                                   '</div>';
                        }
                    }
                }, {
                    xtype: 'combobox',
                    name: 'manager_id',
                    fieldLabel: 'Department Manager',
                    store: Ext.create('HRApp.store.EmployeeComboStore'),
                    displayField: 'name',
                    valueField: 'id',
                    queryMode: 'remote',
                    emptyText: 'Select manager...',
                    
                    listConfig: {
                        getInnerTpl: function() {
                            return '<div class="emp-combo-item">' +
                                   '<div class="emp-name">{name}</div>' +
                                   '<div class="emp-position">{position}</div>' +
                                   '</div>';
                        }
                    }
                }]
            }, {
                xtype: 'fieldset',
                title: 'Resources & Capacity',
                items: [{
                    xtype: 'numberfield',
                    name: 'budget',
                    fieldLabel: 'Annual Budget',
                    minValue: 0,
                    step: 1000,
                    
                    // Format display with currency
                    renderer: function(value) {
                        return value ? '$' + Ext.Number.format(value, '0,0') : '';
                    }
                }, {
                    xtype: 'numberfield',
                    name: 'max_employees',
                    fieldLabel: 'Max Employees',
                    minValue: 1,
                    maxValue: 1000,
                    value: 50
                }, {
                    xtype: 'checkboxfield',
                    name: 'is_active',
                    fieldLabel: 'Active',
                    checked: true
                }]
            }],
            
            buttons: [{
                text: 'Save',
                formBind: true,
                cls: 'hr-btn-primary',
                handler: function() {
                    me.saveDepartment();
                }
            }, {
                text: 'Cancel',
                handler: function() {
                    me.close();
                }
            }]
        }];
        
        me.callParent();
        
        // Load data if editing
        if (me.mode === 'edit' && me.departmentData) {
            me.down('form').getForm().setValues(me.departmentData);
        }
        
        // Set parent department if specified
        if (me.parentId) {
            me.down('[name=parent_id]').setValue(me.parentId);
        }
    },
    
    saveDepartment: function() {
        var me = this,
            form = me.down('form'),
            values = form.getValues();
            
        if (!form.getForm().isValid()) {
            Ext.Msg.alert('Validation Error', 'Please correct the errors in the form');
            return;
        }
        
        var url = me.mode === 'create' ? '/api/hr/departments' : '/api/hr/departments/' + me.departmentData.id;
        var method = me.mode === 'create' ? 'POST' : 'PUT';
        
        form.setLoading('Saving department...');
        
        Ext.Ajax.request({
            url: url,
            method: method,
            jsonData: values,
            success: function(response) {
                form.setLoading(false);
                
                var result = Ext.decode(response.responseText);
                
                Ext.toast({
                    html: me.mode === 'create' ? 'Department created successfully' : 'Department updated successfully',
                    closable: false,
                    align: 't',
                    slideInDuration: 400,
                    minWidth: 400
                });
                
                // Refresh the department tree
                var mainPanel = Ext.ComponentQuery.query('departmentpanel')[0];
                if (mainPanel) {
                    var tree = mainPanel.down('treepanel');
                    if (tree) {
                        tree.getStore().reload();
                    }
                }
                
                me.close();
            },
            failure: function(response) {
                form.setLoading(false);
                
                var error = Ext.decode(response.responseText);
                var message = 'Failed to save department';
                
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
    }
});