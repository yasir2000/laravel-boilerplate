/**
 * Main View Model - Manages application state and data
 */
Ext.define('HRApp.view.main.MainModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.main',
    
    data: {
        // Current user information
        currentUser: {
            name: 'Admin User',
            role: 'Administrator',
            avatar: 'fa fa-user'
        },
        
        // Application state
        appTitle: 'HR Management System',
        appVersion: '1.0.0',
        
        // System status
        systemStatus: {
            database: 'Connected',
            server: 'Online',
            lastUpdate: new Date()
        },
        
        // Quick stats for dashboard
        quickStats: {
            totalEmployees: 0,
            totalDepartments: 0,
            presentToday: 0,
            pendingRequests: 0
        }
    },
    
    stores: {
        // Navigation items
        navigationItems: {
            type: 'tree',
            data: [{
                text: 'HR Management',
                expanded: true,
                children: [{
                    text: 'Employee Management',
                    iconCls: 'fa fa-users',
                    module: 'employee',
                    leaf: true
                }, {
                    text: 'Department Management',
                    iconCls: 'fa fa-building',
                    module: 'department',
                    leaf: true
                }, {
                    text: 'Attendance Management',
                    iconCls: 'fa fa-clock-o',
                    module: 'attendance',
                    leaf: true
                }, {
                    text: 'Reports & Analytics',
                    iconCls: 'fa fa-bar-chart',
                    module: 'reports',
                    leaf: true
                }]
            }, {
                text: 'System',
                expanded: false,
                children: [{
                    text: 'Settings',
                    iconCls: 'fa fa-cog',
                    module: 'settings',
                    leaf: true
                }, {
                    text: 'User Management',
                    iconCls: 'fa fa-user-plus',
                    module: 'users',
                    leaf: true
                }, {
                    text: 'Audit Logs',
                    iconCls: 'fa fa-file-text',
                    module: 'audit',
                    leaf: true
                }]
            }]
        }
    },
    
    formulas: {
        // Current time formatted
        currentTime: function(get) {
            return Ext.Date.format(new Date(), 'H:i:s');
        },
        
        // Welcome message based on time
        welcomeMessage: function(get) {
            var hour = new Date().getHours();
            var userName = get('currentUser.name');
            
            if (hour < 12) {
                return 'Good Morning, ' + userName;
            } else if (hour < 17) {
                return 'Good Afternoon, ' + userName;
            } else {
                return 'Good Evening, ' + userName;
            }
        },
        
        // System status color
        systemStatusColor: function(get) {
            var status = get('systemStatus.database');
            return status === 'Connected' ? 'green' : 'red';
        }
    }
});