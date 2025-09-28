/**
 * Dashboard KPI Store - Handles main dashboard metrics
 */
Ext.define('HRApp.store.DashboardKPIStore', {
    extend: 'Ext.data.Store',
    
    storeId: 'dashboardKPIStore',
    
    fields: [
        'total_employees',
        'employees_trend', 
        'attendance_rate',
        'attendance_trend',
        'pending_leave_requests',
        'leaves_trend',
        'total_payroll',
        'payroll_trend'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/dashboard',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: false,
    
    listeners: {
        load: function(store, records, successful, operation) {
            if (!successful) {
                console.error('Failed to load dashboard KPI data:', operation.getError());
                Ext.Msg.alert('Error', 'Failed to load dashboard data. Please try again.');
            }
        }
    }
});