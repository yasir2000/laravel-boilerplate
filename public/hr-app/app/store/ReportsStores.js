/**
 * Reporting and Analytics Stores
 * Data stores for charts, KPIs, and analytics
 */

/**
 * Attendance Trend Store
 * Daily attendance rate trends over time
 */
Ext.define('HRApp.store.AttendanceTrendStore', {
    extend: 'Ext.data.Store',
    alias: 'store.attendancetrend',
    
    fields: [
        'date', 'attendance_rate', 'total_employees', 
        'present_employees', 'absent_employees'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/reports/attendance-trends',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: true
});

/**
 * Department Distribution Store
 * Employee distribution across departments
 */
Ext.define('HRApp.store.DepartmentDistributionStore', {
    extend: 'Ext.data.Store',
    alias: 'store.departmentdistribution',
    
    fields: [
        'department_name', 'employee_count', 'percentage', 'budget_utilization'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/reports/department-distribution',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: true
});

/**
 * Employment Type Store
 * Distribution of employees by employment type
 */
Ext.define('HRApp.store.EmploymentTypeStore', {
    extend: 'Ext.data.Store',
    alias: 'store.employmenttype',
    
    fields: ['type', 'count', 'percentage'],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/reports/employment-types',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: true
});

/**
 * Employee by Department Store
 * Employee count by department for bar charts
 */
Ext.define('HRApp.store.EmployeeByDepartmentStore', {
    extend: 'Ext.data.Store',
    alias: 'store.employeebydepartment',
    
    fields: [
        'department_name', 'employee_count', 'avg_salary', 
        'turnover_rate', 'satisfaction_score'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/reports/employees-by-department',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: true,
    sorters: [{
        property: 'employee_count',
        direction: 'DESC'
    }]
});

/**
 * Age Distribution Store
 * Employee age distribution for demographics
 */
Ext.define('HRApp.store.AgeDistributionStore', {
    extend: 'Ext.data.Store',
    alias: 'store.agedistribution',
    
    fields: ['age_range', 'count', 'percentage'],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/reports/age-distribution',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: true
});

/**
 * Employee Performance Store
 * Employee performance metrics and ratings
 */
Ext.define('HRApp.store.EmployeePerformanceStore', {
    extend: 'Ext.data.Store',
    alias: 'store.employeeperformance',
    
    fields: [
        'employee_id', 'employee_name', 'department', 'position',
        'attendance_rate', 'avg_daily_hours', 'total_overtime',
        'leave_days_used', 'performance_score', 'last_evaluation'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/reports/employee-performance',
        reader: {
            type: 'json',
            rootProperty: 'data',
            totalProperty: 'total'
        }
    },
    
    autoLoad: true,
    pageSize: 25,
    remoteSort: true,
    
    sorters: [{
        property: 'performance_score',
        direction: 'DESC'
    }]
});

/**
 * Daily Attendance Store
 * Hourly check-in/check-out patterns
 */
Ext.define('HRApp.store.DailyAttendanceStore', {
    extend: 'Ext.data.Store',
    alias: 'store.dailyattendance',
    
    fields: ['hour', 'check_ins', 'check_outs', 'active_employees'],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/reports/daily-attendance-pattern',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: true
});

/**
 * Monthly Attendance Store
 * Monthly attendance summary with present/absent/leave breakdown
 */
Ext.define('HRApp.store.MonthlyAttendanceStore', {
    extend: 'Ext.data.Store',
    alias: 'store.monthlyattendance',
    
    fields: [
        'month', 'present_days', 'absent_days', 'leave_days',
        'total_working_days', 'attendance_rate'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/reports/monthly-attendance',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: true
});

/**
 * Detailed Attendance Store
 * Detailed employee attendance report
 */
Ext.define('HRApp.store.DetailedAttendanceStore', {
    extend: 'Ext.data.Store',
    alias: 'store.detailedattendance',
    
    fields: [
        'employee_id', 'employee_name', 'department', 'present_days',
        'absent_days', 'late_arrivals', 'early_departures', 
        'total_hours', 'overtime_hours', 'attendance_rate'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/reports/detailed-attendance',
        reader: {
            type: 'json',
            rootProperty: 'data',
            totalProperty: 'total'
        }
    },
    
    autoLoad: true,
    pageSize: 25,
    remoteSort: true,
    remoteFilter: true
});

/**
 * Leave Types Store
 * Distribution of leave requests by type
 */
Ext.define('HRApp.store.LeaveTypesStore', {
    extend: 'Ext.data.Store',
    alias: 'store.leavetypes',
    
    fields: ['leave_type', 'count', 'percentage', 'avg_duration'],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/reports/leave-types-distribution',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: true
});

/**
 * Leave Trends Store
 * Monthly leave request trends with approval rates
 */
Ext.define('HRApp.store.LeaveTrendsStore', {
    extend: 'Ext.data.Store',
    alias: 'store.leavetrends',
    
    fields: [
        'month', 'total_requests', 'approved_leaves', 
        'rejected_leaves', 'pending_leaves', 'approval_rate'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/reports/leave-trends',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: true
});

/**
 * Leave Summary Store
 * Leave summary by department with statistics
 */
Ext.define('HRApp.store.LeaveSummaryStore', {
    extend: 'Ext.data.Store',
    alias: 'store.leavesummary',
    
    fields: [
        'department_name', 'total_requests', 'approved_requests',
        'pending_requests', 'rejected_requests', 'approval_rate',
        'avg_days_per_request', 'most_common_type'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/reports/leave-summary',
        reader: {
            type: 'json',
            rootProperty: 'data',
            totalProperty: 'total'
        }
    },
    
    autoLoad: true,
    pageSize: 25,
    remoteSort: true
});

/**
 * Dashboard KPI Store
 * Key performance indicators for executive dashboard
 */
Ext.define('HRApp.store.DashboardKPIStore', {
    extend: 'Ext.data.Store',
    alias: 'store.dashboardkpi',
    
    fields: [
        'kpi_name', 'current_value', 'previous_value', 
        'change_percentage', 'trend', 'target_value'
    ],
    
    proxy: {
        type: 'rest',
        url: '/api/hr/dashboard/kpis',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: true
});