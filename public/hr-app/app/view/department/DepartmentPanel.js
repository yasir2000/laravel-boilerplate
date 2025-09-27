/**
 * Department Management Panel
 * Provides tree view and organizational chart for department management
 */
Ext.define('HRApp.view.department.DepartmentPanel', {
    extend: 'Ext.panel.Panel',
    xtype: 'departmentpanel',
    
    layout: 'border',
    
    title: '<i class="fa fa-building"></i> Department Management',
    
    items: [{
        region: 'north',
        xtype: 'toolbar',
        height: 50,
        items: [{
            text: '<i class="fa fa-plus"></i> Add Department',
            cls: 'hr-btn-primary',
            handler: function() {
                Ext.create('HRApp.view.department.DepartmentForm', {
                    title: 'Add New Department',
                    mode: 'create'
                }).show();
            }
        }, '-', {
            text: '<i class="fa fa-sitemap"></i> Organization Chart',
            cls: 'hr-btn-secondary',
            handler: function() {
                var panel = this.up('departmentpanel');
                panel.showOrganizationChart();
            }
        }, '-', {
            text: '<i class="fa fa-list"></i> Grid View',
            cls: 'hr-btn-secondary',
            handler: function() {
                var panel = this.up('departmentpanel');
                panel.showGridView();
            }
        }, '-', {
            xtype: 'textfield',
            emptyText: 'Search departments...',
            width: 250,
            listeners: {
                change: function(field, value) {
                    var tree = field.up('panel').down('treepanel');
                    if (tree) {
                        tree.getStore().clearFilter();
                        if (value) {
                            tree.getStore().filterBy(function(record) {
                                return record.get('name').toLowerCase().indexOf(value.toLowerCase()) !== -1 ||
                                       record.get('code').toLowerCase().indexOf(value.toLowerCase()) !== -1;
                            });
                        }
                    }
                }
            }
        }, '->', {
            text: '<i class="fa fa-refresh"></i> Refresh',
            handler: function() {
                var tree = this.up('panel').down('treepanel');
                var grid = this.up('panel').down('grid');
                if (tree) tree.getStore().reload();
                if (grid) grid.getStore().reload();
            }
        }]
    }, {
        region: 'west',
        title: 'Department Hierarchy',
        width: 350,
        collapsible: true,
        split: true,
        layout: 'fit',
        items: [{
            xtype: 'treepanel',
            itemId: 'departmentTree',
            store: Ext.create('HRApp.store.DepartmentTreeStore'),
            rootVisible: false,
            useArrows: true,
            
            columns: [{
                xtype: 'treecolumn',
                text: 'Department',
                dataIndex: 'name',
                flex: 1,
                renderer: function(value, metaData, record) {
                    var icon = record.get('is_active') ? 'fa-building' : 'fa-building-o';
                    var color = record.get('is_active') ? '#337ab7' : '#999';
                    return '<i class="fa ' + icon + '" style="color: ' + color + '; margin-right: 5px;"></i>' + value;
                }
            }, {
                text: 'Code',
                dataIndex: 'code',
                width: 80,
                renderer: function(value) {
                    return '<span class="dept-code">' + value + '</span>';
                }
            }, {
                text: 'Employees',
                dataIndex: 'employees_count',
                width: 80,
                align: 'center',
                renderer: function(value, metaData, record) {
                    var count = value || 0;
                    var maxEmployees = record.get('max_employees');
                    var color = '#337ab7';
                    
                    if (maxEmployees && count > maxEmployees * 0.9) {
                        color = '#d9534f'; // Red if near capacity
                    } else if (maxEmployees && count > maxEmployees * 0.7) {
                        color = '#f0ad4e'; // Orange if 70%+ capacity
                    }
                    
                    return '<span style="color: ' + color + '; font-weight: bold;">' + count + '</span>';
                }
            }],
            
            listeners: {
                selectionchange: function(tree, selected) {
                    if (selected.length > 0) {
                        var record = selected[0];
                        var detailPanel = tree.up('departmentpanel').down('#departmentDetail');
                        detailPanel.loadDepartmentDetails(record);
                    }
                },
                
                itemcontextmenu: function(view, record, item, index, e) {
                    e.stopEvent();
                    
                    var menu = Ext.create('Ext.menu.Menu', {
                        items: [{
                            text: '<i class="fa fa-plus"></i> Add Sub-Department',
                            handler: function() {
                                Ext.create('HRApp.view.department.DepartmentForm', {
                                    title: 'Add Sub-Department to ' + record.get('name'),
                                    mode: 'create',
                                    parentDepartment: record
                                }).show();
                            }
                        }, {
                            text: '<i class="fa fa-edit"></i> Edit Department',
                            handler: function() {
                                Ext.create('HRApp.view.department.DepartmentForm', {
                                    title: 'Edit Department - ' + record.get('name'),
                                    mode: 'edit',
                                    department: record
                                }).show();
                            }
                        }, '-', {
                            text: '<i class="fa fa-users"></i> View Employees',
                            handler: function() {
                                // Open employee list filtered by this department
                                var empWindow = Ext.create('Ext.window.Window', {
                                    title: 'Employees in ' + record.get('name'),
                                    width: 900,
                                    height: 600,
                                    layout: 'fit',
                                    maximizable: true,
                                    items: [{
                                        xtype: 'employeepanel',
                                        departmentFilter: record.get('id')
                                    }]
                                });
                                empWindow.show();
                            }
                        }, {
                            text: '<i class="fa fa-bar-chart"></i> Department Statistics',
                            handler: function() {
                                // Show department statistics
                                view.up('departmentpanel').showDepartmentStats(record);
                            }
                        }, '-', {
                            text: '<i class="fa fa-trash"></i> Delete Department',
                            cls: 'menu-item-danger',
                            disabled: record.get('employees_count') > 0,
                            handler: function() {
                                Ext.Msg.confirm(
                                    'Delete Department',
                                    'Are you sure you want to delete the department "' + record.get('name') + '"?',
                                    function(btn) {
                                        if (btn === 'yes') {
                                            // Call API to delete department
                                            Ext.Ajax.request({
                                                url: '/api/hr/departments/' + record.get('id'),
                                                method: 'DELETE',
                                                success: function() {
                                                    Ext.toast({
                                                        html: 'Department deleted successfully',
                                                        closable: false,
                                                        align: 't',
                                                        slideInDuration: 400,
                                                        minWidth: 400
                                                    });
                                                    tree.getStore().reload();
                                                },
                                                failure: function(response) {
                                                    var error = Ext.decode(response.responseText);
                                                    Ext.Msg.alert('Error', error.message || 'Failed to delete department');
                                                }
                                            });
                                        }
                                    }
                                );
                            }
                        }]
                    });
                    
                    menu.showAt(e.getXY());
                }
            }
        }]
    }, {
        region: 'center',
        itemId: 'departmentDetail',
        title: 'Department Details',
        layout: 'fit',
        
        items: [{
            xtype: 'panel',
            html: '<div class="empty-state">' +
                  '<i class="fa fa-building" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>' +
                  '<h3>Select a Department</h3>' +
                  '<p>Choose a department from the tree view to see detailed information.</p>' +
                  '</div>',
            cls: 'empty-state-panel'
        }],
        
        loadDepartmentDetails: function(record) {
            var me = this;
            
            // Load full department details
            Ext.Ajax.request({
                url: '/api/hr/departments/' + record.get('id'),
                method: 'GET',
                success: function(response) {
                    var data = Ext.decode(response.responseText).data;
                    me.showDepartmentDetails(data);
                },
                failure: function() {
                    Ext.Msg.alert('Error', 'Failed to load department details');
                }
            });
        },
        
        showDepartmentDetails: function(department) {
            var me = this;
            
            me.removeAll();
            me.add({
                xtype: 'panel',
                autoScroll: true,
                bodyPadding: 20,
                tpl: new Ext.XTemplate(
                    '<div class="department-details">',
                    '  <div class="dept-header">',
                    '    <h2><i class="fa fa-building"></i> {name}</h2>',
                    '    <div class="dept-meta">',
                    '      <span class="dept-code">{code}</span>',
                    '      <tpl if="is_active">',
                    '        <span class="status-badge status-active">ACTIVE</span>',
                    '      <tpl else>',
                    '        <span class="status-badge status-inactive">INACTIVE</span>',
                    '      </tpl>',
                    '    </div>',
                    '  </div>',
                    
                    '  <div class="dept-info-grid">',
                    '    <div class="info-card">',
                    '      <h4><i class="fa fa-info-circle"></i> Basic Information</h4>',
                    '      <table class="info-table">',
                    '        <tr><td>Description:</td><td>{description:htmlEncode}</td></tr>',
                    '        <tr><td>Location:</td><td>{location:htmlEncode}</td></tr>',
                    '        <tr><td>Budget:</td><td>${budget:number("0,0")}</td></tr>',
                    '        <tr><td>Max Employees:</td><td>{max_employees}</td></tr>',
                    '      </table>',
                    '    </div>',
                    
                    '    <div class="info-card">',
                    '      <h4><i class="fa fa-users"></i> Team Information</h4>',
                    '      <table class="info-table">',
                    '        <tr><td>Current Employees:</td><td><strong>{employees_count}</strong></td></tr>',
                    '        <tr><td>Capacity:</td><td>{[this.getCapacityBar(values.employees_count, values.max_employees)]}</td></tr>',
                    '        <tpl if="manager">',
                    '          <tr><td>Manager:</td><td>{manager.name}</td></tr>',
                    '        <tpl else>',
                    '          <tr><td>Manager:</td><td><em>No manager assigned</em></td></tr>',
                    '        </tpl>',
                    '      </table>',
                    '    </div>',
                    
                    '    <tpl if="parent">',
                    '      <div class="info-card">',
                    '        <h4><i class="fa fa-level-up"></i> Parent Department</h4>',
                    '        <p><strong>{parent.name}</strong> ({parent.code})</p>',
                    '      </div>',
                    '    </tpl>',
                    
                    '    <tpl if="children && children.length &gt; 0">',
                    '      <div class="info-card">',
                    '        <h4><i class="fa fa-level-down"></i> Sub-Departments</h4>',
                    '        <ul class="sub-dept-list">',
                    '        <tpl for="children">',
                    '          <li><strong>{name}</strong> ({code}) - {employees_count} employees</li>',
                    '        </tpl>',
                    '        </ul>',
                    '      </div>',
                    '    </tpl>',
                    '  </div>',
                    '</div>',
                    {
                        getCapacityBar: function(current, max) {
                            if (!max) return current + ' employees';
                            
                            var percentage = Math.round((current / max) * 100);
                            var color = '#5cb85c';
                            
                            if (percentage > 90) color = '#d9534f';
                            else if (percentage > 70) color = '#f0ad4e';
                            
                            return '<div class="capacity-bar">' +
                                   '<div class="capacity-fill" style="width: ' + percentage + '%; background: ' + color + ';"></div>' +
                                   '<span class="capacity-text">' + current + ' / ' + max + ' (' + percentage + '%)</span>' +
                                   '</div>';
                        }
                    }
                ),
                data: department
            });
            
            // Add action toolbar
            me.addDocked({
                xtype: 'toolbar',
                dock: 'bottom',
                items: [{
                    text: '<i class="fa fa-edit"></i> Edit Department',
                    cls: 'hr-btn-primary',
                    handler: function() {
                        Ext.create('HRApp.view.department.DepartmentForm', {
                            title: 'Edit Department - ' + department.name,
                            mode: 'edit',
                            departmentData: department
                        }).show();
                    }
                }, '-', {
                    text: '<i class="fa fa-plus"></i> Add Sub-Department',
                    handler: function() {
                        Ext.create('HRApp.view.department.DepartmentForm', {
                            title: 'Add Sub-Department to ' + department.name,
                            mode: 'create',
                            parentId: department.id
                        }).show();
                    }
                }, '-', {
                    text: '<i class="fa fa-users"></i> View Employees',
                    handler: function() {
                        me.up('departmentpanel').showDepartmentEmployees(department);
                    }
                }, '->', {
                    text: '<i class="fa fa-bar-chart"></i> Statistics',
                    handler: function() {
                        me.up('departmentpanel').showDepartmentStats(department);
                    }
                }]
            });
        }
    }],
    
    // Methods
    showOrganizationChart: function() {
        var chartWindow = Ext.create('Ext.window.Window', {
            title: '<i class="fa fa-sitemap"></i> Organization Chart',
            width: 1000,
            height: 700,
            layout: 'fit',
            maximizable: true,
            modal: true,
            
            items: [{
                xtype: 'panel',
                itemId: 'orgChart',
                html: '<div id="org-chart-container" style="width: 100%; height: 100%; padding: 20px;">' +
                      '<div class="loading-chart">Loading organization chart...</div>' +
                      '</div>',
                
                listeners: {
                    afterrender: function() {
                        this.loadOrganizationChart();
                    }
                },
                
                loadOrganizationChart: function() {
                    var container = Ext.get('org-chart-container');
                    
                    // Load department hierarchy data
                    Ext.Ajax.request({
                        url: '/api/hr/departments/hierarchy',
                        method: 'GET',
                        success: function(response) {
                            var data = Ext.decode(response.responseText).data;
                            container.setHtml(this.renderOrgChart(data));
                        }.bind(this),
                        failure: function() {
                            container.setHtml('<div class="error-state">Failed to load organization chart</div>');
                        }
                    });
                },
                
                renderOrgChart: function(departments) {
                    var html = '<div class="org-chart">';
                    
                    // Render root level departments
                    departments.forEach(function(dept) {
                        html += this.renderDepartmentNode(dept, 0);
                    }.bind(this));
                    
                    html += '</div>';
                    return html;
                },
                
                renderDepartmentNode: function(dept, level) {
                    var html = '<div class="dept-node level-' + level + '">';
                    html += '<div class="dept-box">';
                    html += '<div class="dept-name">' + dept.name + '</div>';
                    html += '<div class="dept-info">' + dept.code + ' • ' + (dept.employees_count || 0) + ' employees</div>';
                    if (dept.manager) {
                        html += '<div class="dept-manager">Manager: ' + dept.manager.name + '</div>';
                    }
                    html += '</div>';
                    
                    // Render children if any
                    if (dept.children && dept.children.length > 0) {
                        html += '<div class="dept-children">';
                        dept.children.forEach(function(child) {
                            html += this.renderDepartmentNode(child, level + 1);
                        }.bind(this));
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    return html;
                }
            }]
        });
        
        chartWindow.show();
    },
    
    showGridView: function() {
        var gridWindow = Ext.create('Ext.window.Window', {
            title: '<i class="fa fa-list"></i> Departments Grid View',
            width: 900,
            height: 600,
            layout: 'fit',
            maximizable: true,
            
            items: [{
                xtype: 'grid',
                store: Ext.create('HRApp.store.DepartmentGridStore'),
                
                columns: [{
                    text: 'Name',
                    dataIndex: 'name',
                    flex: 2
                }, {
                    text: 'Code',
                    dataIndex: 'code',
                    width: 80
                }, {
                    text: 'Parent',
                    dataIndex: 'parent',
                    width: 150,
                    renderer: function(value) {
                        return value ? value.name : '-';
                    }
                }, {
                    text: 'Manager',
                    dataIndex: 'manager',
                    width: 150,
                    renderer: function(value) {
                        return value ? value.name : 'Not assigned';
                    }
                }, {
                    text: 'Employees',
                    dataIndex: 'employees_count',
                    width: 100,
                    align: 'center'
                }, {
                    text: 'Budget',
                    dataIndex: 'budget',
                    width: 120,
                    align: 'right',
                    renderer: function(value) {
                        return value ? '$' + Ext.Number.format(value, '0,0') : '-';
                    }
                }, {
                    text: 'Status',
                    dataIndex: 'is_active',
                    width: 80,
                    align: 'center',
                    renderer: function(value) {
                        return value ? 
                            '<span class="status-badge status-active">Active</span>' :
                            '<span class="status-badge status-inactive">Inactive</span>';
                    }
                }],
                
                bbar: {
                    xtype: 'pagingtoolbar',
                    displayInfo: true
                }
            }]
        });
        
        gridWindow.show();
    },
    
    showDepartmentEmployees: function(department) {
        // Implementation for showing department employees
        console.log('Show employees for department:', department.name);
    },
    
    showDepartmentStats: function(department) {
        // Implementation for showing department statistics
        console.log('Show statistics for department:', department.name);
    }
});