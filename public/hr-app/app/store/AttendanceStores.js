/**
 * Live Attendance Store
 * Real-time attendance data for currently active employees
 */
Ext.define('HRApp.store.LiveAttendanceStore', {
    extend: 'Ext.data.Store',
    alias: 'store.liveattendance',
    
    fields: [
        'id', 'employee_id', 'employee_name', 'employee_avatar',
        'department', 'position', 'status', 'check_in_time', 
        'check_out_time', 'hours_worked', 'location', 'last_activity'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/attendance/live',
        reader: {
            type: 'json',
            rootProperty: 'data',
            totalProperty: 'total'
        }
    },
    
    autoLoad: true,
    pageSize: 50,
    remoteSort: true,
    remoteFilter: true,
    
    sorters: [{
        property: 'employee_name',
        direction: 'ASC'
    }]
});

/**
 * Attendance History Store
 * Historical attendance records with filtering and pagination
 */
Ext.define('HRApp.store.AttendanceHistoryStore', {
    extend: 'Ext.data.Store',
    alias: 'store.attendancehistory',
    
    fields: [
        'id', 'employee_id', 'employee_name', 'department', 
        'date', 'check_in_time', 'check_out_time', 'total_hours',
        'break_duration', 'overtime_hours', 'status', 'location',
        'notes', 'approved_by'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/attendance/history',
        reader: {
            type: 'json',
            rootProperty: 'data',
            totalProperty: 'total'
        }
    },
    
    autoLoad: true,
    pageSize: 25,
    remoteSort: true,
    remoteFilter: true,
    
    sorters: [{
        property: 'date',
        direction: 'DESC'
    }]
});

/**
 * Leave Request Store
 * Store for managing leave requests with approval workflow
 */
Ext.define('HRApp.store.LeaveRequestStore', {
    extend: 'Ext.data.Store',
    alias: 'store.leaverequest',
    
    fields: [
        'id', 'employee_id', 'employee_name', 'department',
        'leave_type', 'start_date', 'end_date', 'total_days',
        'reason', 'status', 'is_half_day', 'is_emergency',
        'covering_employee_id', 'covering_employee_name',
        'coverage_notes', 'approved_by', 'approved_at',
        'rejected_reason', 'created_at'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/leave-requests',
        reader: {
            type: 'json',
            rootProperty: 'data',
            totalProperty: 'total'
        }
    },
    
    autoLoad: true,
    pageSize: 25,
    remoteSort: true,
    remoteFilter: true,
    
    sorters: [{
        property: 'created_at',
        direction: 'DESC'
    }]
});

/**
 * Attendance Summary Store
 * Store for attendance statistics and summaries
 */
Ext.define('HRApp.store.AttendanceSummaryStore', {
    extend: 'Ext.data.Store',
    alias: 'store.attendancesummary',
    
    fields: [
        'employee_id', 'employee_name', 'department',
        'total_days_present', 'total_days_absent', 'total_hours_worked',
        'average_daily_hours', 'total_overtime', 'late_arrivals',
        'early_departures', 'attendance_rate', 'leave_days_used',
        'leave_days_remaining'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/attendance/summary',
        reader: {
            type: 'json',
            rootProperty: 'data',
            totalProperty: 'total'
        }
    },
    
    pageSize: 25,
    remoteSort: true,
    remoteFilter: true
});