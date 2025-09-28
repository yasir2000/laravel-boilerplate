/**
 * Employee File Upload Panel
 * Comprehensive file management system for employee documents
 */
Ext.define('HRApp.view.files.FileUploadPanel', {
    extend: 'Ext.panel.Panel',
    xtype: 'fileuploadpanel',
    
    title: '<i class="fa fa-upload"></i> File Management',
    layout: 'border',
    
    requires: [
        'Ext.form.field.File',
        'Ext.form.field.ComboBox',
        'Ext.grid.Panel',
        'Ext.toolbar.Toolbar'
    ],
    
    initComponent: function() {
        var me = this;
        
        // Store for employee documents
        me.documentsStore = Ext.create('Ext.data.Store', {
            fields: [
                'id', 'title', 'description', 'document_type', 'file_name',
                'original_name', 'file_size', 'mime_type', 'uploaded_at',
                'uploaded_by_name', 'download_url', 'is_image'
            ],
            proxy: {
                type: 'rest',
                url: '/api/hr/files/employee',
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            }
        });
        
        me.items = [{
            region: 'west',
            width: '50%',
            title: 'Upload Documents',
            layout: 'fit',
            items: [{
                xtype: 'form',
                itemId: 'uploadForm',
                bodyPadding: 20,
                defaults: {
                    anchor: '100%',
                    labelAlign: 'top'
                },
                
                items: [{
                    xtype: 'combobox',
                    name: 'employee_id',
                    fieldLabel: 'Select Employee',
                    displayField: 'full_name',
                    valueField: 'id',
                    allowBlank: false,
                    editable: false,
                    
                    store: {
                        fields: ['id', 'full_name'],
                        proxy: {
                            type: 'rest',
                            url: '/api/hr/employees',
                            reader: {
                                type: 'json',
                                rootProperty: 'data',
                                transform: function(data) {
                                    return data.map(function(emp) {
                                        return {
                                            id: emp.id,
                                            full_name: emp.first_name + ' ' + emp.last_name
                                        };
                                    });
                                }
                            }
                        },
                        autoLoad: true
                    },
                    
                    listeners: {
                        select: function(combo, record) {
                            me.loadEmployeeDocuments(record.get('id'));
                        }
                    }
                }, {
                    xtype: 'combobox',
                    name: 'document_type',
                    fieldLabel: 'Document Type',
                    allowBlank: false,
                    editable: false,
                    
                    store: {
                        fields: ['value', 'text'],
                        data: [
                            {value: 'photo', text: 'Profile Photo'},
                            {value: 'contract', text: 'Employment Contract'},
                            {value: 'certificate', text: 'Certificate/Diploma'},
                            {value: 'id_document', text: 'ID Document'},
                            {value: 'resume', text: 'Resume/CV'},
                            {value: 'other', text: 'Other Document'}
                        ]
                    },
                    displayField: 'text',
                    valueField: 'value'
                }, {
                    xtype: 'textfield',
                    name: 'title',
                    fieldLabel: 'Document Title',
                    allowBlank: false,
                    maxLength: 255
                }, {
                    xtype: 'textarea',
                    name: 'description',
                    fieldLabel: 'Description (Optional)',
                    height: 80,
                    maxLength: 1000
                }, {
                    xtype: 'filefield',
                    name: 'file',
                    fieldLabel: 'Select File',
                    buttonText: 'Browse...',
                    allowBlank: false,
                    
                    listeners: {
                        change: function(field, value) {
                            me.updateFileInfo(field, value);
                        }
                    }
                }, {
                    xtype: 'container',
                    itemId: 'fileInfoContainer',
                    hidden: true,
                    style: {
                        background: '#f8f9fa',
                        border: '1px solid #dee2e6',
                        borderRadius: '4px',
                        padding: '10px',
                        marginTop: '10px'
                    }
                }, {
                    xtype: 'progressbar',
                    itemId: 'uploadProgress',
                    hidden: true,
                    margin: '10 0'
                }],
                
                buttons: [{
                    text: 'Upload Document',
                    iconCls: 'fa fa-upload',
                    formBind: true,
                    handler: function() {
                        me.uploadDocument();
                    }
                }, {
                    text: 'Clear Form',
                    iconCls: 'fa fa-refresh',
                    handler: function() {
                        this.up('form').getForm().reset();
                        me.down('#fileInfoContainer').hide();
                    }
                }]
            }]
        }, {
            region: 'center',
            title: 'Employee Documents',
            layout: 'fit',
            
            items: [{
                xtype: 'grid',
                itemId: 'documentsGrid',
                store: me.documentsStore,
                
                columns: [{
                    text: 'Type',
                    dataIndex: 'document_type',
                    width: 100,
                    renderer: function(value) {
                        var icons = {
                            'photo': '<i class="fa fa-image" style="color: #17a2b8;"></i> Photo',
                            'contract': '<i class="fa fa-file-text" style="color: #6c757d;"></i> Contract',
                            'certificate': '<i class="fa fa-certificate" style="color: #ffc107;"></i> Certificate',
                            'id_document': '<i class="fa fa-id-card" style="color: #28a745;"></i> ID Document',
                            'resume': '<i class="fa fa-file-pdf-o" style="color: #dc3545;"></i> Resume',
                            'other': '<i class="fa fa-file" style="color: #6c757d;"></i> Other'
                        };
                        return icons[value] || value;
                    }
                }, {
                    text: 'Title',
                    dataIndex: 'title',
                    flex: 1
                }, {
                    text: 'File Name',
                    dataIndex: 'original_name',
                    flex: 1
                }, {
                    text: 'Size',
                    dataIndex: 'file_size',
                    width: 80,
                    renderer: function(value) {
                        return me.formatFileSize(value);
                    }
                }, {
                    text: 'Uploaded',
                    dataIndex: 'uploaded_at',
                    width: 120,
                    renderer: Ext.util.Format.date
                }, {
                    text: 'Uploaded By',
                    dataIndex: 'uploaded_by_name',
                    width: 120
                }, {
                    text: 'Actions',
                    width: 120,
                    renderer: function(value, meta, record) {
                        return [
                            '<button class="btn-grid-action" onclick="HRApp.FileManager.downloadDocument(\'' + record.get('id') + '\')" title="Download">',
                                '<i class="fa fa-download"></i>',
                            '</button>',
                            '<button class="btn-grid-action" onclick="HRApp.FileManager.previewDocument(\'' + record.get('id') + '\', \'' + record.get('download_url') + '\', ' + record.get('is_image') + ')" title="Preview">',
                                '<i class="fa fa-eye"></i>',
                            '</button>',
                            '<button class="btn-grid-action btn-danger" onclick="HRApp.FileManager.deleteDocument(\'' + record.get('id') + '\')" title="Delete">',
                                '<i class="fa fa-trash"></i>',
                            '</button>'
                        ].join('');
                    }
                }],
                
                tbar: [{
                    text: 'Refresh',
                    iconCls: 'fa fa-refresh',
                    handler: function() {
                        var employeeCombo = me.down('combobox[name=employee_id]');
                        if (employeeCombo.getValue()) {
                            me.loadEmployeeDocuments(employeeCombo.getValue());
                        }
                    }
                }, '-', {
                    text: 'Upload Statistics',
                    iconCls: 'fa fa-bar-chart',
                    handler: function() {
                        me.showUploadStats();
                    }
                }]
            }]
        }];
        
        me.callParent();
        
        // Set up global file manager
        this.setupGlobalFileManager();
    },
    
    setupGlobalFileManager: function() {
        var me = this;
        
        window.HRApp = window.HRApp || {};
        window.HRApp.FileManager = {
            downloadDocument: function(documentId) {
                window.open('/api/hr/files/download/' + documentId, '_blank');
            },
            
            previewDocument: function(documentId, downloadUrl, isImage) {
                if (isImage) {
                    me.previewImage(downloadUrl);
                } else {
                    me.previewDocument(downloadUrl);
                }
            },
            
            deleteDocument: function(documentId) {
                me.deleteDocument(documentId);
            }
        };
    },
    
    loadEmployeeDocuments: function(employeeId) {
        var me = this;
        
        me.documentsStore.getProxy().setUrl('/api/hr/files/employee/' + employeeId + '/documents');
        me.documentsStore.load({
            callback: function(records, operation, success) {
                if (!success) {
                    Ext.Msg.alert('Error', 'Failed to load employee documents');
                }
            }
        });
    },
    
    updateFileInfo: function(field, value) {
        var me = this;
        var container = me.down('#fileInfoContainer');
        
        if (value) {
            var file = field.fileInputEl.dom.files[0];
            if (file) {
                container.setHtml([
                    '<div class="file-info">',
                        '<div><strong>File:</strong> ' + file.name + '</div>',
                        '<div><strong>Size:</strong> ' + me.formatFileSize(file.size) + '</div>',
                        '<div><strong>Type:</strong> ' + file.type + '</div>',
                    '</div>'
                ].join(''));
                container.show();
                
                // Validate file size (10MB limit)
                if (file.size > 10485760) {
                    Ext.Msg.alert('File Too Large', 'File size must be less than 10MB');
                    field.reset();
                    container.hide();
                }
            }
        } else {
            container.hide();
        }
    },
    
    uploadDocument: function() {
        var me = this;
        var form = me.down('#uploadForm');
        var formData = form.getForm();
        var progressBar = me.down('#uploadProgress');
        
        if (formData.isValid()) {
            progressBar.show();
            progressBar.wait({
                text: 'Uploading document...'
            });
            
            formData.submit({
                url: '/api/hr/files/upload/employee-document',
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                
                success: function(form, action) {
                    progressBar.hide();
                    Ext.Msg.alert('Success', 'Document uploaded successfully', function() {
                        form.reset();
                        me.down('#fileInfoContainer').hide();
                        
                        // Refresh documents grid
                        var employeeCombo = me.down('combobox[name=employee_id]');
                        if (employeeCombo.getValue()) {
                            me.loadEmployeeDocuments(employeeCombo.getValue());
                        }
                    });
                },
                
                failure: function(form, action) {
                    progressBar.hide();
                    var message = 'Upload failed';
                    
                    try {
                        if (action.result && action.result.message) {
                            message = action.result.message;
                        }
                    } catch (e) {
                        // Use default message
                    }
                    
                    Ext.Msg.alert('Upload Failed', message);
                }
            });
        }
    },
    
    deleteDocument: function(documentId) {
        var me = this;
        
        Ext.Msg.confirm('Delete Document', 'Are you sure you want to delete this document? This action cannot be undone.', function(btn) {
            if (btn === 'yes') {
                Ext.Ajax.request({
                    url: '/api/hr/files/delete/' + documentId,
                    method: 'DELETE',
                    
                    success: function(response) {
                        var result = Ext.decode(response.responseText);
                        if (result.success) {
                            Ext.Msg.alert('Success', 'Document deleted successfully');
                            me.documentsStore.load(); // Refresh grid
                        } else {
                            Ext.Msg.alert('Error', result.message || 'Failed to delete document');
                        }
                    },
                    
                    failure: function() {
                        Ext.Msg.alert('Error', 'Failed to delete document');
                    }
                });
            }
        });
    },
    
    previewImage: function(imageUrl) {
        Ext.create('Ext.window.Window', {
            title: 'Image Preview',
            width: 600,
            height: 500,
            layout: 'fit',
            modal: true,
            
            items: [{
                xtype: 'component',
                html: '<img src="' + imageUrl + '" style="max-width: 100%; max-height: 100%; object-fit: contain;" />'
            }],
            
            buttons: [{
                text: 'Close',
                handler: function() {
                    this.up('window').close();
                }
            }]
        }).show();
    },
    
    previewDocument: function(documentUrl) {
        window.open(documentUrl, '_blank');
    },
    
    showUploadStats: function() {
        var me = this;
        
        Ext.Ajax.request({
            url: '/api/hr/files/upload-stats',
            method: 'GET',
            
            success: function(response) {
                var result = Ext.decode(response.responseText);
                if (result.success) {
                    me.createStatsWindow(result.data);
                } else {
                    Ext.Msg.alert('Error', 'Failed to load statistics');
                }
            },
            
            failure: function() {
                Ext.Msg.alert('Error', 'Failed to load statistics');
            }
        });
    },
    
    createStatsWindow: function(data) {
        var me = this;
        
        Ext.create('Ext.window.Window', {
            title: 'Upload Statistics',
            width: 600,
            height: 400,
            layout: 'border',
            modal: true,
            
            items: [{
                region: 'north',
                height: 100,
                bodyPadding: 10,
                html: [
                    '<div class="stats-summary">',
                        '<div class="stat-item">',
                            '<span class="stat-label">Total Documents:</span>',
                            '<span class="stat-value">' + data.total_documents + '</span>',
                        '</div>',
                        '<div class="stat-item">',
                            '<span class="stat-label">Total Size:</span>',
                            '<span class="stat-value">' + me.formatFileSize(data.total_size) + '</span>',
                        '</div>',
                    '</div>'
                ].join('')
            }, {
                region: 'center',
                title: 'Recent Uploads',
                layout: 'fit',
                
                items: [{
                    xtype: 'grid',
                    store: {
                        fields: ['title', 'document_type', 'employee_name', 'uploaded_by', 'uploaded_at', 'file_size'],
                        data: data.recent_uploads || []
                    },
                    
                    columns: [{
                        text: 'Title',
                        dataIndex: 'title',
                        flex: 1
                    }, {
                        text: 'Type',
                        dataIndex: 'document_type',
                        width: 100
                    }, {
                        text: 'Employee',
                        dataIndex: 'employee_name',
                        width: 150
                    }, {
                        text: 'Uploaded By',
                        dataIndex: 'uploaded_by',
                        width: 120
                    }, {
                        text: 'Size',
                        dataIndex: 'file_size',
                        width: 80,
                        renderer: function(value) {
                            return me.formatFileSize(value);
                        }
                    }]
                }]
            }],
            
            buttons: [{
                text: 'Close',
                handler: function() {
                    this.up('window').close();
                }
            }]
        }).show();
    },
    
    formatFileSize: function(bytes) {
        if (!bytes) return '0 B';
        
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    }
});

// Add custom CSS for file upload panel
Ext.util.CSS.createStyleSheet(`
    .file-info {
        font-size: 12px;
        line-height: 1.5;
    }
    
    .file-info div {
        margin-bottom: 5px;
    }
    
    .btn-grid-action {
        background: none;
        border: none;
        color: #007bff;
        cursor: pointer;
        padding: 2px 5px;
        margin: 0 2px;
        border-radius: 3px;
        transition: all 0.2s;
    }
    
    .btn-grid-action:hover {
        background: #f8f9fa;
        color: #0056b3;
    }
    
    .btn-grid-action.btn-danger {
        color: #dc3545;
    }
    
    .btn-grid-action.btn-danger:hover {
        background: #f5c6cb;
        color: #721c24;
    }
    
    .stats-summary {
        display: flex;
        gap: 20px;
        align-items: center;
        justify-content: center;
        height: 100%;
    }
    
    .stat-item {
        text-align: center;
        padding: 10px 20px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background: #f8f9fa;
    }
    
    .stat-label {
        display: block;
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 5px;
    }
    
    .stat-value {
        display: block;
        font-size: 18px;
        font-weight: bold;
        color: #495057;
    }
`);