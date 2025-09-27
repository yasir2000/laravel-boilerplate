/**
 * HR Management System - Main Application
 * Desktop-style interface for Windows-like experience
 */
Ext.define('HRApp.Application', {
    extend: 'Ext.app.Application',
    
    name: 'HRApp',
    
    requires: [
        'HRApp.view.main.MainView',
        'HRApp.view.main.MainController',
        'HRApp.view.main.MainModel',
        'HRApp.view.employee.EmployeePanel',
        'HRApp.store.EmployeeStore',
        'HRApp.store.DepartmentStore',
        'HRApp.model.Employee',
        'HRApp.model.Department'
    ],
    
    // Application configuration
    appFolder: 'app',
    
    // Launch function - called when application is ready
    launch: function() {
        console.log('HR Management System - Launching Application');
        
        // Create main viewport with desktop-like interface
        Ext.create('HRApp.view.main.MainView');
        
        // Set up global error handling
        Ext.get(window).on('error', function(e) {
            console.error('Application Error:', e);
        });
        
        // Check authentication status
        this.checkAuthentication();
    },
    
    // Check if user is authenticated
    checkAuthentication: function() {
        // For now, we'll assume authenticated
        // In production, this would check with Laravel backend
        this.authenticated = true;
        
        if (!this.authenticated) {
            this.showLogin();
        }
    },
    
    // Show login window
    showLogin: function() {
        Ext.create('Ext.window.Window', {
            title: 'HR System Login',
            modal: true,
            closable: false,
            width: 350,
            height: 200,
            layout: 'fit',
            items: [{
                xtype: 'form',
                bodyPadding: 20,
                items: [{
                    xtype: 'textfield',
                    name: 'username',
                    fieldLabel: 'Username',
                    allowBlank: false
                }, {
                    xtype: 'textfield',
                    name: 'password',
                    fieldLabel: 'Password',
                    inputType: 'password',
                    allowBlank: false
                }],
                buttons: [{
                    text: 'Login',
                    formBind: true,
                    handler: function() {
                        // Handle login logic
                        this.up('window').close();
                    }
                }]
            }]
        }).show();
    },
    
    // Global configuration
    config: {
        // API base URL for Laravel backend
        apiUrl: '/api',
        
        // Application settings
        settings: {
            theme: 'desktop',
            language: 'en',
            dateFormat: 'Y-m-d',
            timeFormat: 'H:i:s'
        }
    }
});