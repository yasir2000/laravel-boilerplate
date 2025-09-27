/**
 * Employee Management Panel
 * Provides a grid view for managing employees
 */
Ext.define('HRApp.view.employee.EmployeePanel', {
    extend: 'Ext.panel.Panel',
    xtype: 'employeepanel',
    
    layout: 'border',
    
    title: '<i class="fa fa-users"></i> Employee Management',
    
    items: [{
        region: 'north',
        xtype: 'toolbar',
        height: 50,
        items: [{
            text: '<i class="fa fa-plus"></i> Add Employee',
            cls: 'hr-btn-primary',
            handler: function() {
                // Open add employee dialog
                Ext.create('HRApp.view.employee.EmployeeForm', {
                    title: 'Add New Employee',
                    mode: 'create'
                }).show();
            }
        }, '-', {
            xtype: 'textfield',
            emptyText: 'Search employees...',
            width: 250,
            listeners: {
                change: function(field, value) {
                    var grid = field.up('panel').down('grid');
                    var store = grid.getStore();
                    
                    if (value) {
                        store.getProxy().setExtraParam('search', value);
                    } else {
                        store.getProxy().setExtraParam('search', null);
                    }
                    store.load();
                }
            }
        }, '-', {
            xtype: 'combobox',
            emptyText: 'Filter by Department',
            width: 200,
            store: Ext.create('HRApp.store.DepartmentStore'),
            displayField: 'name',
            valueField: 'id',
            listeners: {
                select: function(combo, record) {
                    var grid = combo.up('panel').down('grid');
                    var store = grid.getStore();
                    store.getProxy().setExtraParam('department_id', record.get('id'));
                    store.load();
                },
                clear: function(combo) {
                    var grid = combo.up('panel').down('grid');
                    var store = grid.getStore();
                    store.getProxy().setExtraParam('department_id', null);
                    store.load();
                }
            }
        }, '->', {
            text: '<i class="fa fa-refresh"></i> Refresh',
            handler: function() {
                this.up('panel').down('grid').getStore().reload();
            }
        }]
    }, {
        region: 'center',
        xtype: 'grid',
        store: Ext.create('HRApp.store.EmployeeStore'),
        
        columns: [{
            text: 'Photo',
            dataIndex: 'profile_photo',
            width: 60,
            align: 'center',
            renderer: function(value, metaData, record) {
                if (value) {
                    return '<img src="' + value + '" width="32" height="32" style="border-radius: 16px;" />';
                } else {
                    return '<div class="employee-avatar">' + 
                           record.get('first_name').charAt(0) + 
                           record.get('last_name').charAt(0) + 
                           '</div>';
                }
            }
        }, {
            text: 'Employee ID',
            dataIndex: 'employee_id',
            width: 120,
            renderer: function(value) {
                return '<span class="employee-id">' + value + '</span>';
            }
        }, {
            text: 'Name',
            dataIndex: 'first_name',
            flex: 1,
            renderer: function(value, metaData, record) {
                var fullName = record.get('first_name') + ' ' + record.get('last_name');
                var email = record.get('user') ? record.get('user').email : '';
                return '<div class="employee-name">' + fullName + '</div>' +
                       '<div class="employee-email">' + email + '</div>';
            }
        }, {
            text: 'Department',
            dataIndex: 'department',
            width: 150,
            renderer: function(value) {
                if (value) {
                    return '<span class="department-badge">' + value.name + '</span>';
                }
                return '';
            }
        }, {
            text: 'Position',
            dataIndex: 'position',
            width: 150,
            renderer: function(value) {
                if (value) {
                    return value.title;
                }
                return '';
            }
        }, {
            text: 'Hire Date',
            dataIndex: 'hire_date',
            width: 100,
            renderer: function(value) {
                return Ext.Date.format(new Date(value), 'M j, Y');
            }
        }, {
            text: 'Status',
            dataIndex: 'employment_status',
            width: 100,
            align: 'center',
            renderer: function(value) {
                var cls = 'status-' + value;
                var text = value.replace('_', ' ').toUpperCase();
                return '<span class="status-badge ' + cls + '">' + text + '</span>';
            }
        }, {
            text: 'Salary',
            dataIndex: 'salary',
            width: 100,
            align: 'right',
            renderer: function(value, metaData, record) {
                return '$' + Ext.Number.format(value, '0,0');
            }
        }, {
            text: 'Actions',
            width: 120,
            align: 'center',
            renderer: function(value, metaData, record) {
                return [
                    '<a href="#" class="action-btn view-btn" data-action="view" data-id="' + record.get('id') + '">',
                    '<i class="fa fa-eye" title="View Details"></i>',
                    '</a>',
                    '<a href="#" class="action-btn edit-btn" data-action="edit" data-id="' + record.get('id') + '">',
                    '<i class="fa fa-edit" title="Edit Employee"></i>',
                    '</a>',
                    '<a href="#" class="action-btn delete-btn" data-action="delete" data-id="' + record.get('id') + '">',
                    '<i class="fa fa-trash" title="Delete Employee"></i>',
                    '</a>'
                ].join('');
            }
        }],
        
        listeners: {
            cellclick: function(view, td, cellIndex, record, tr, rowIndex, e) {
                var target = e.getTarget('.action-btn');
                if (target) {
                    var action = target.getAttribute('data-action');
                    var employeeId = target.getAttribute('data-id');
                    
                    switch (action) {
                        case 'view':
                            this.showEmployeeDetails(record);
                            break;
                        case 'edit':
                            this.editEmployee(record);
                            break;
                        case 'delete':
                            this.deleteEmployee(record);
                            break;
                    }
                }
            },
            
            showEmployeeDetails: function(record) {
                Ext.create('HRApp.view.employee.EmployeeDetails', {
                    employee: record
                }).show();
            },
            
            editEmployee: function(record) {
                Ext.create('HRApp.view.employee.EmployeeForm', {
                    title: 'Edit Employee - ' + record.get('first_name') + ' ' + record.get('last_name'),
                    mode: 'edit',
                    employee: record
                }).show();
            },
            
            deleteEmployee: function(record) {
                Ext.Msg.confirm(
                    'Delete Employee',
                    'Are you sure you want to delete ' + record.get('first_name') + ' ' + record.get('last_name') + '?',
                    function(btn) {
                        if (btn === 'yes') {
                            // Call API to delete employee
                            Ext.Ajax.request({
                                url: '/api/hr/employees/' + record.get('id'),
                                method: 'DELETE',
                                success: function() {
                                    Ext.toast({
                                        html: 'Employee deleted successfully',
                                        closable: false,
                                        align: 't',
                                        slideInDuration: 400,
                                        minWidth: 400
                                    });
                                    view.getStore().reload();
                                },
                                failure: function() {
                                    Ext.Msg.alert('Error', 'Failed to delete employee');
                                }
                            });
                        }
                    }
                );
            }
        },
        
        viewConfig: {
            stripeRows: true,
            enableTextSelection: true
        },
        
        bbar: {
            xtype: 'pagingtoolbar',
            displayInfo: true,
            displayMsg: 'Displaying employees {0} - {1} of {2}',
            emptyMsg: 'No employees to display'
        }
    }]
});

// Add custom CSS for employee grid
Ext.util.CSS.createStyleSheet(`
    .employee-avatar {
        width: 32px;
        height: 32px;
        border-radius: 16px;
        background: #337ab7;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
        margin: 0 auto;
    }
    
    .employee-name {
        font-weight: bold;
        color: #333;
    }
    
    .employee-email {
        font-size: 11px;
        color: #666;
    }
    
    .employee-id {
        font-family: monospace;
        background: #f5f5f5;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
    }
    
    .department-badge {
        background: #5bc0de;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
    }
    
    .status-badge {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .status-active { background: #5cb85c; color: white; }
    .status-inactive { background: #f0ad4e; color: white; }
    .status-terminated { background: #d9534f; color: white; }
    .status-on_leave { background: #337ab7; color: white; }
    
    .action-btn {
        display: inline-block;
        margin: 0 3px;
        padding: 4px 6px;
        border-radius: 3px;
        text-decoration: none;
        transition: background 0.2s;
    }
    
    .view-btn { background: #337ab7; color: white; }
    .edit-btn { background: #5cb85c; color: white; }
    .delete-btn { background: #d9534f; color: white; }
    
    .action-btn:hover {
        opacity: 0.8;
    }
    
    .hr-btn-primary {
        background: linear-gradient(to bottom, #337ab7 0%, #2e6da4 100%) !important;
        border: 1px solid #2e6da4 !important;
        color: white !important;
    }
`, 'employee-grid-styles');