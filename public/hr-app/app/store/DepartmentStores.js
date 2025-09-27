/**
 * Department Tree Store
 * Hierarchical store for department tree view
 */
Ext.define('HRApp.store.DepartmentTreeStore', {
    extend: 'Ext.data.TreeStore',
    alias: 'store.departmenttree',
    
    model: 'HRApp.model.Department',
    
    proxy: {
        type: 'rest',
        url: '/api/hr/departments/tree',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    root: {
        text: 'Departments',
        expanded: true
    },
    
    autoLoad: true,
    
    listeners: {
        beforeload: function() {
            // Add loading mask to tree
            var tree = Ext.ComponentQuery.query('treepanel[itemId=departmentTree]')[0];
            if (tree) {
                tree.setLoading('Loading departments...');
            }
        },
        
        load: function() {
            // Remove loading mask
            var tree = Ext.ComponentQuery.query('treepanel[itemId=departmentTree]')[0];
            if (tree) {
                tree.setLoading(false);
                tree.expandAll(); // Auto-expand all nodes
            }
        }
    }
});

/**
 * Department Grid Store
 * Flat store for grid view of departments
 */
Ext.define('HRApp.store.DepartmentGridStore', {
    extend: 'Ext.data.Store',
    alias: 'store.departmentgrid',
    
    model: 'HRApp.model.Department',
    
    proxy: {
        type: 'rest',
        url: '/api/hr/departments',
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
 * Department Combo Store
 * Store for department selection dropdowns
 */
Ext.define('HRApp.store.DepartmentComboStore', {
    extend: 'Ext.data.Store',
    alias: 'store.departmentcombo',
    
    model: 'HRApp.model.Department',
    
    proxy: {
        type: 'rest',
        url: '/api/hr/departments/list',
        reader: {
            type: 'json',
            rootProperty: 'data'
        }
    },
    
    autoLoad: true,
    sorters: ['name']
});