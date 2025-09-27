/**
 * Department Store
 * Manages department data from Laravel API
 */
Ext.define('HRApp.store.DepartmentStore', {
    extend: 'Ext.data.Store',
    alias: 'store.departmentstore',
    
    model: 'HRApp.model.Department',
    
    autoLoad: true,
    
    proxy: {
        type: 'rest',
        url: '/api/hr/departments',
        
        reader: {
            type: 'json',
            rootProperty: 'data'
        },
        
        extraParams: {
            all: true // Get all departments without pagination
        },
        
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        
        listeners: {
            beforerequest: function(conn, options) {
                var token = document.querySelector('meta[name="csrf-token"]');
                if (token) {
                    options.headers = options.headers || {};
                    options.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
                }
            }
        }
    },
    
    sorters: [{
        property: 'name',
        direction: 'ASC'
    }]
});