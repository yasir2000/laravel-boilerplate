/**
 * Employee Store
 * Manages employee data from Laravel API
 */
Ext.define('HRApp.store.EmployeeStore', {
    extend: 'Ext.data.Store',
    alias: 'store.employeestore',
    
    model: 'HRApp.model.Employee',
    
    autoLoad: true,
    
    pageSize: 25,
    
    proxy: {
        type: 'rest',
        url: '/api/hr/employees',
        
        reader: {
            type: 'json',
            rootProperty: 'data.data', // Laravel pagination structure
            totalProperty: 'data.total'
        },
        
        writer: {
            type: 'json',
            writeAllFields: true
        },
        
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        
        // Add authentication token if needed
        listeners: {
            beforerequest: function(conn, options) {
                // Add CSRF token or authentication token
                var token = document.querySelector('meta[name="csrf-token"]');
                if (token) {
                    options.headers = options.headers || {};
                    options.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
                }
            }
        }
    },
    
    sorters: [{
        property: 'first_name',
        direction: 'ASC'
    }]
});