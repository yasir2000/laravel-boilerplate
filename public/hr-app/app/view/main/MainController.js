/**
 * Main Controller - Handles desktop interactions and window management
 */
Ext.define('HRApp.view.main.MainController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.main',
    
    // Store references to open windows for taskbar management
    openWindows: {},
    
    // After main view renders
    onMainViewAfterRender: function() {
        // Start clock update
        this.updateClock();
        
        // Set up window management
        this.setupWindowManager();
        
        console.log('HR Desktop System Ready');
    },
    
    // Update taskbar clock every second
    updateClock: function() {
        var me = this;
        var clockCmp = this.getView().down('#clock');
        
        setInterval(function() {
            if (clockCmp && !clockCmp.destroyed) {
                clockCmp.setText(new Date().toLocaleTimeString());
            }
        }, 1000);
    },
    
    // Set up window management system
    setupWindowManager: function() {
        var me = this;
        
        // Override window creation to add taskbar buttons
        Ext.override(Ext.window.Window, {
            afterRender: function() {
                this.callParent(arguments);
                me.addWindowToTaskbar(this);
            },
            
            onDestroy: function() {
                me.removeWindowFromTaskbar(this);
                this.callParent(arguments);
            }
        });
    },
    
    // Add window button to taskbar
    addWindowToTaskbar: function(win) {
        if (!win.taskbarButton && win.title) {
            var taskbar = this.getView().down('toolbar[cls~=hr-taskbar]');
            
            win.taskbarButton = taskbar.insert(-3, {
                xtype: 'button',
                text: win.title,
                cls: 'taskbar-window-btn',
                style: {
                    background: 'linear-gradient(to bottom, #666 0%, #444 100%)',
                    color: 'white',
                    border: '1px solid #555',
                    margin: '0 2px'
                },
                handler: function() {
                    if (win.minimized) {
                        win.restore();
                    } else if (win.hidden) {
                        win.show();
                    } else {
                        win.toFront();
                    }
                }
            });
            
            this.openWindows[win.id] = win;
        }
    },
    
    // Remove window button from taskbar
    removeWindowFromTaskbar: function(win) {
        if (win.taskbarButton) {
            win.taskbarButton.destroy();
            delete this.openWindows[win.id];
        }
    },
    
    // Employee Management
    onEmployeeClick: function() {
        this.createModuleWindow('employee', 'Employee Management', 'fa fa-users', 'HRApp.view.employee.EmployeePanel');
    },
    
    // Department Management
    onDepartmentClick: function() {
        this.createModuleWindow('department', 'Department Management', 'fa fa-building', 'HRApp.view.department.DepartmentPanel');
    },
    
    // Attendance Management
    onAttendanceClick: function() {
        this.createModuleWindow('attendance', 'Attendance Management', 'fa fa-clock-o', 'HRApp.view.attendance.AttendancePanel');
    },

    // Dashboard
    onDashboardClick: function() {
        this.createModuleWindow('dashboard', 'HR Dashboard', 'fa fa-dashboard', 'HRApp.view.dashboard.DashboardPanel');
    },

    // Reports and Analytics
    onReportsClick: function() {
        this.createModuleWindow('reports', 'Reports & Analytics', 'fa fa-bar-chart', 'HRApp.view.reports.ReportsPanel');
    },

    // File Management
    onFileManagementClick: function() {
        this.createModuleWindow('files', 'File Management', 'fa fa-upload', 'HRApp.view.files.FileUploadPanel');
    },
    onAttendanceClick: function() {
        this.createModuleWindow('attendance', 'Attendance Management', 'fa fa-clock-o');
    },
    
    // Reports & Analytics
    onReportsClick: function() {
        this.createModuleWindow('reports', 'Reports & Analytics', 'fa fa-bar-chart');
    },
    
    // Settings
    onSettingsClick: function() {
        this.createModuleWindow('settings', 'System Settings', 'fa fa-cog');
    },
    
    // Create module window
    createModuleWindow: function(module, title, iconCls, componentClass) {
        var existingWindow = this.openWindows[module + 'Window'];
        
        if (existingWindow && !existingWindow.destroyed) {
            existingWindow.toFront();
            return;
        }
        
        var items;
        if (componentClass) {
            // Use the actual component
            items = [{
                xtype: componentClass.toLowerCase().replace('hrapp.view.', '').replace('.', '')
            }];
        } else {
            // Fallback to placeholder content
            items = [{
                xtype: 'panel',
                html: '<div style="padding: 20px; text-align: center; font-size: 16px; color: #666;">' +
                      '<i class="' + iconCls + '" style="font-size: 48px; color: #337ab7; margin-bottom: 20px;"></i><br>' +
                      '<h3>' + title + '</h3>' +
                      '<p>Module content will be loaded here.</p>' +
                      '<p>Connected to PostgreSQL database for data management.</p>' +
                      '</div>',
                border: false
            }];
        }
        
        var window = Ext.create('Ext.window.Window', {
            id: module + 'Window',
            title: '<i class="' + iconCls + '"></i> ' + title,
            width: componentClass ? 1200 : 900,
            height: componentClass ? 700 : 600,
            layout: 'fit',
            maximizable: true,
            minimizable: true,
            constrain: true,
            cls: 'hr-module-window',
            
            // Window styling for native look
            style: {
                borderRadius: '0px'
            },
            
            items: items,
            
            tools: [{
                type: 'help',
                tooltip: 'Help',
                handler: function() {
                    Ext.Msg.alert('Help', 'Help documentation for ' + title + ' module.');
                }
            }],
            
            listeners: {
                minimize: function() {
                    this.hide();
                },
                
                maximize: function() {
                    // Custom maximize behavior
                },
                
                close: function() {
                    // Clean up any resources
                }
            }
        });
        
        window.show();
        return window;
    },
    
    // Logout
    onLogoutClick: function() {
        Ext.Msg.confirm('Logout', 'Are you sure you want to logout?', function(btn) {
            if (btn === 'yes') {
                // Close all windows
                Ext.each(this.openWindows, function(win) {
                    if (win && !win.destroyed) {
                        win.close();
                    }
                });
                
                // Redirect to login or Laravel logout
                window.location.href = '/logout';
            }
        }, this);
    }
});