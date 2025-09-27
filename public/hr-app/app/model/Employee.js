/**
 * Employee Model
 * Defines the structure for employee data
 */
Ext.define('HRApp.model.Employee', {
    extend: 'Ext.data.Model',
    
    fields: [
        { name: 'id', type: 'int' },
        { name: 'user_id', type: 'int' },
        { name: 'employee_id', type: 'string' },
        { name: 'department_id', type: 'int' },
        { name: 'position_id', type: 'int' },
        { name: 'supervisor_id', type: 'int' },
        
        // Personal information
        { name: 'first_name', type: 'string' },
        { name: 'last_name', type: 'string' },
        { name: 'middle_name', type: 'string' },
        { name: 'date_of_birth', type: 'date' },
        { name: 'gender', type: 'string' },
        { name: 'marital_status', type: 'string' },
        { name: 'nationality', type: 'string' },
        { name: 'national_id', type: 'string' },
        { name: 'passport_number', type: 'string' },
        
        // Contact information
        { name: 'personal_email', type: 'string' },
        { name: 'phone', type: 'string' },
        { name: 'mobile', type: 'string' },
        { name: 'emergency_contact_name', type: 'string' },
        { name: 'emergency_contact_phone', type: 'string' },
        { name: 'address', type: 'string' },
        { name: 'city', type: 'string' },
        { name: 'state', type: 'string' },
        { name: 'postal_code', type: 'string' },
        { name: 'country', type: 'string' },
        
        // Employment information
        { name: 'hire_date', type: 'date' },
        { name: 'contract_start_date', type: 'date' },
        { name: 'contract_end_date', type: 'date' },
        { name: 'employment_type', type: 'string' },
        { name: 'employment_status', type: 'string' },
        { name: 'salary', type: 'float' },
        { name: 'salary_currency', type: 'string' },
        { name: 'salary_type', type: 'string' },
        { name: 'hourly_rate', type: 'float' },
        
        // Work information
        { name: 'work_hours_per_week', type: 'int' },
        { name: 'work_location', type: 'string' },
        { name: 'remote_work_allowed', type: 'boolean' },
        
        // Leave information
        { name: 'vacation_days_per_year', type: 'int' },
        { name: 'sick_days_per_year', type: 'int' },
        { name: 'vacation_days_used', type: 'int' },
        { name: 'sick_days_used', type: 'int' },
        
        // Files and documents
        { name: 'profile_photo', type: 'string' },
        { name: 'documents', type: 'auto' },
        
        // Additional information
        { name: 'notes', type: 'string' },
        { name: 'skills', type: 'auto' },
        { name: 'certifications', type: 'auto' },
        { name: 'education', type: 'auto' },
        { name: 'metadata', type: 'auto' },
        
        // Relationships
        { name: 'user', type: 'auto' },
        { name: 'department', type: 'auto' },
        { name: 'position', type: 'auto' },
        { name: 'supervisor', type: 'auto' },
        
        // Timestamps
        { name: 'created_at', type: 'date' },
        { name: 'updated_at', type: 'date' }
    ],
    
    // Calculated fields
    getFullName: function() {
        var firstName = this.get('first_name') || '';
        var middleName = this.get('middle_name') || '';
        var lastName = this.get('last_name') || '';
        
        var parts = [firstName];
        if (middleName) {
            parts.push(middleName);
        }
        parts.push(lastName);
        
        return parts.join(' ');
    },
    
    getDepartmentName: function() {
        var dept = this.get('department');
        return dept ? dept.name : '';
    },
    
    getPositionTitle: function() {
        var pos = this.get('position');
        return pos ? pos.title : '';
    },
    
    getStatusColor: function() {
        var status = this.get('employment_status');
        var colors = {
            'active': '#5cb85c',
            'inactive': '#f0ad4e',
            'terminated': '#d9534f',
            'on_leave': '#337ab7'
        };
        return colors[status] || '#999';
    },
    
    getRemainingVacationDays: function() {
        var total = this.get('vacation_days_per_year') || 0;
        var used = this.get('vacation_days_used') || 0;
        return Math.max(0, total - used);
    },
    
    getRemainingSickDays: function() {
        var total = this.get('sick_days_per_year') || 0;
        var used = this.get('sick_days_used') || 0;
        return Math.max(0, total - used);
    }
});