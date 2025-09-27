/**
 * Department Model
 * Defines the structure for department data
 */
Ext.define('HRApp.model.Department', {
    extend: 'Ext.data.Model',
    
    fields: [
        { name: 'id', type: 'int' },
        { name: 'name', type: 'string' },
        { name: 'code', type: 'string' },
        { name: 'description', type: 'string' },
        { name: 'parent_id', type: 'int' },
        { name: 'manager_id', type: 'int' },
        { name: 'location', type: 'string' },
        { name: 'budget', type: 'float' },
        { name: 'max_employees', type: 'int' },
        { name: 'is_active', type: 'boolean' },
        { name: 'metadata', type: 'auto' },
        
        // Relationships
        { name: 'parent', type: 'auto' },
        { name: 'manager', type: 'auto' },
        { name: 'children', type: 'auto' },
        { name: 'employees_count', type: 'int' },
        
        // Timestamps
        { name: 'created_at', type: 'date' },
        { name: 'updated_at', type: 'date' }
    ]
});