/**
 * Main View - Windows Desktop-like Interface
 * Provides taskbar, desktop icons, and window management
 */
Ext.define('HRApp.view.main.MainView', {
    extend: 'Ext.container.Viewport',
    xtype: 'mainview',
    
    controller: 'main',
    viewModel: 'main',
    
    layout: 'border',
    
    cls: 'desktop-main-view',
    
    style: {
        background: 'linear-gradient(135deg, #1e3a5f 0%, #2d5a8b 100%)'
    },
    
    items: [{
        // Desktop area - main content
        region: 'center',
        xtype: 'panel',
        layout: 'absolute',
        cls: 'hr-desktop',
        border: false,
        items: [
            // Desktop shortcuts
            {
                xtype: 'panel',
                x: 30,
                y: 30,
                width: 80,
                height: 100,
                cls: 'desktop-shortcut',
                layout: {
                    type: 'vbox',
                    align: 'center'
                },
                items: [{
                    xtype: 'button',
                    iconCls: 'fa fa-users',
                    scale: 'large',
                    cls: 'desktop-icon',
                    tooltip: 'Employee Management',
                    handler: 'onEmployeeClick'
                }, {
                    xtype: 'label',
                    text: 'Employees',
                    cls: 'desktop-label'
                }]
            },
            {
                xtype: 'panel',
                x: 130,
                y: 30,
                width: 80,
                height: 100,
                cls: 'desktop-shortcut',
                layout: {
                    type: 'vbox',
                    align: 'center'
                },
                items: [{
                    xtype: 'button',
                    iconCls: 'fa fa-building',
                    scale: 'large',
                    cls: 'desktop-icon',
                    tooltip: 'Department Management',
                    handler: 'onDepartmentClick'
                }, {
                    xtype: 'label',
                    text: 'Departments',
                    cls: 'desktop-label'
                }]
            },
            {
                xtype: 'panel',
                x: 230,
                y: 30,
                width: 80,
                height: 100,
                cls: 'desktop-shortcut',
                layout: {
                    type: 'vbox',
                    align: 'center'
                },
                items: [{
                    xtype: 'button',
                    iconCls: 'fa fa-clock-o',
                    scale: 'large',
                    cls: 'desktop-icon',
                    tooltip: 'Attendance Management',
                    handler: 'onAttendanceClick'
                }, {
                    xtype: 'label',
                    text: 'Attendance',
                    cls: 'desktop-label'
                }]
            },
            {
                xtype: 'panel',
                x: 330,
                y: 30,
                width: 80,
                height: 100,
                cls: 'desktop-shortcut',
                layout: {
                    type: 'vbox',
                    align: 'center'
                },
                items: [{
                    xtype: 'button',
                    iconCls: 'fa fa-bar-chart',
                    scale: 'large',
                    cls: 'desktop-icon',
                    tooltip: 'Reports & Analytics',
                    handler: 'onReportsClick'
                }, {
                    xtype: 'label',
                    text: 'Reports',
                    cls: 'desktop-label'
                }]
            }
        ]
    }, {
        // Taskbar at bottom - Windows-like
        region: 'south',
        xtype: 'toolbar',
        height: 40,
        cls: 'hr-taskbar',
        style: {
            background: 'linear-gradient(to bottom, #4a4a4a 0%, #2c2c2c 100%)',
            borderTop: '1px solid #666'
        },
        items: [{
            // Start button
            xtype: 'button',
            text: '<i class="fa fa-windows"></i> HR System',
            cls: 'start-button',
            style: {
                background: 'linear-gradient(to bottom, #5cb85c 0%, #449d44 100%)',
                color: 'white',
                border: '1px solid #449d44'
            },
            menu: [{
                text: 'Employee Management',
                iconCls: 'fa fa-users',
                handler: 'onEmployeeClick'
            }, {
                text: 'Department Management',
                iconCls: 'fa fa-building',
                handler: 'onDepartmentClick'
            }, {
                text: 'Attendance Management',
                iconCls: 'fa fa-clock-o',
                handler: 'onAttendanceClick'
            }, {
                text: 'Reports & Analytics',
                iconCls: 'fa fa-bar-chart',
                handler: 'onReportsClick'
            }, '-', {
                text: 'Settings',
                iconCls: 'fa fa-cog',
                handler: 'onSettingsClick'
            }, {
                text: 'Logout',
                iconCls: 'fa fa-sign-out',
                handler: 'onLogoutClick'
            }]
        }, '->', {
            // System tray area
            xtype: 'tbtext',
            text: '<i class="fa fa-user"></i> Admin User',
            cls: 'taskbar-user'
        }, {
            xtype: 'tbseparator'
        }, {
            xtype: 'tbtext',
            text: new Date().toLocaleTimeString(),
            cls: 'taskbar-clock',
            itemId: 'clock'
        }]
    }],
    
    listeners: {
        afterrender: 'onMainViewAfterRender'
    }
});

// Add custom CSS styles
Ext.util.CSS.createStyleSheet(`
    .desktop-main-view {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif !important;
    }
    
    .hr-desktop {
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="%231e3a5f"/></svg>');
    }
    
    .desktop-shortcut {
        background: transparent !important;
        border: none !important;
        cursor: pointer;
    }
    
    .desktop-shortcut:hover {
        background: rgba(255, 255, 255, 0.1) !important;
        border-radius: 5px;
    }
    
    .desktop-icon {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }
    
    .desktop-icon .x-btn-inner {
        font-size: 32px !important;
        color: white !important;
        text-shadow: 0 1px 3px rgba(0,0,0,0.5);
    }
    
    .desktop-label {
        color: white !important;
        font-weight: bold;
        text-shadow: 0 1px 3px rgba(0,0,0,0.7);
        font-size: 11px;
        margin-top: 5px;
    }
    
    .hr-taskbar {
        box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
    }
    
    .start-button {
        font-weight: bold !important;
    }
    
    .taskbar-user, .taskbar-clock {
        color: white !important;
        font-size: 12px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.7);
    }
`, 'hr-desktop-styles');