/**
 * Employee Combo Store
 * Store for employee selection dropdowns
 */
Ext.define('HRApp.store.EmployeeComboStore', {
    extend: 'Ext.data.Store',
    alias: 'store.employeecombo',
    
    model: 'HRApp.model.Employee',
    
    proxy: {
        type: 'rest',
        url: '/api/hr/employees/list',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: true,
    sorters: ['name']
});