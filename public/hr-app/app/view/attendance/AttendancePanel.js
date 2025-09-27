/**
 * Attendance Management Panel
 * Provides clock-in/out functionality, attendance monitoring, and leave management
 */
Ext.define('HRApp.view.attendance.AttendancePanel', {
    extend: 'Ext.panel.Panel',
    xtype: 'attendancepanel',
    
    layout: 'border',
    
    title: '<i class="fa fa-clock-o"></i> Attendance Management',
    
    items: [{
        region: 'north',
        xtype: 'toolbar',
        height: 50,
        items: [{
            text: '<i class="fa fa-sign-in"></i> Clock In',
            cls: 'hr-btn-success',
            itemId: 'clockInBtn',
            handler: function() {
                this.up('attendancepanel').clockIn();
            }
        }, {
            text: '<i class="fa fa-sign-out"></i> Clock Out',
            cls: 'hr-btn-warning',
            itemId: 'clockOutBtn',
            disabled: true,
            handler: function() {
                this.up('attendancepanel').clockOut();
            }
        }, '-', {
            text: '<i class="fa fa-calendar-plus-o"></i> Request Leave',
            cls: 'hr-btn-info',
            handler: function() {
                Ext.create('HRApp.view.attendance.LeaveRequestForm').show();
            }
        }, '-', {
            text: '<i class="fa fa-list"></i> My Attendance',
            handler: function() {
                this.up('attendancepanel').showMyAttendance();
            }
        }, {
            text: '<i class="fa fa-users"></i> Team Attendance',
            handler: function() {
                this.up('attendancepanel').showTeamAttendance();
            }
        }, '->', {
            xtype: 'tbtext',
            text: '<i class="fa fa-clock-o"></i> Current Time: ',
            itemId: 'currentTime'
        }, {
            text: '<i class="fa fa-refresh"></i> Refresh',
            handler: function() {
                this.up('attendancepanel').refreshData();
            }
        }]
    }, {
        region: 'west',
        title: 'Today\'s Status',
        width: 300,
        collapsible: true,
        split: true,
        layout: 'fit',
        items: [{
            xtype: 'panel',
            itemId: 'todayStatus',
            bodyPadding: 15,
            autoScroll: true,
            tpl: new Ext.XTemplate(
                '<div class="attendance-status">',
                '  <h3><i class="fa fa-calendar"></i> {[new Date().toLocaleDateString()]}</h3>',
                '  ',
                '  <div class="status-card {status_class}">',
                '    <div class="status-header">',
                '      <i class="fa {status_icon}"></i>',
                '      <span class="status-text">{status_text}</span>',
                '    </div>',
                '    <div class="status-details">',
                '      <tpl if="check_in_time">',
                '        <p><strong>Check-in:</strong> {check_in_time}</p>',
                '      </tpl>',
                '      <tpl if="check_out_time">',
                '        <p><strong>Check-out:</strong> {check_out_time}</p>',
                '      </tpl>',
                '      <tpl if="total_hours">',
                '        <p><strong>Total Hours:</strong> {total_hours}</p>',
                '      </tpl>',
                '      <tpl if="break_duration">',
                '        <p><strong>Break Time:</strong> {break_duration}</p>',
                '      </tpl>',
                '    </div>',
                '  </div>',
                
                '  <div class="quick-stats">',
                '    <h4>This Week</h4>',
                '    <div class="stat-item">',
                '      <span class="stat-label">Days Worked:</span>',
                '      <span class="stat-value">{week_days_worked}</span>',
                '    </div>',
                '    <div class="stat-item">',
                '      <span class="stat-label">Total Hours:</span>',
                '      <span class="stat-value">{week_total_hours}</span>',
                '    </div>',
                '    <div class="stat-item">',
                '      <span class="stat-label">Overtime:</span>',
                '      <span class="stat-value">{week_overtime}</span>',
                '    </div>',
                '  </div>',
                
                '  <div class="quick-stats">',
                '    <h4>This Month</h4>',
                '    <div class="stat-item">',
                '      <span class="stat-label">Attendance Rate:</span>',
                '      <span class="stat-value">{month_attendance_rate}%</span>',
                '    </div>',
                '    <div class="stat-item">',
                '      <span class="stat-label">Leave Days:</span>',
                '      <span class="stat-value">{month_leave_days}</span>',
                '    </div>',
                '  </div>',
                '</div>'
            )
        }]
    }, {
        region: 'center',
        layout: 'fit',
        items: [{
            xtype: 'tabpanel',
            items: [{
                title: '<i class="fa fa-clock-o"></i> Live Attendance',
                itemId: 'liveTab',
                layout: 'fit',
                items: [{
                    xtype: 'grid',
                    itemId: 'liveGrid',
                    store: Ext.create('HRApp.store.LiveAttendanceStore'),
                    
                    columns: [{
                        text: 'Employee',
                        dataIndex: 'employee_name',
                        flex: 1,
                        renderer: function(value, metaData, record) {
                            var avatar = record.get('employee_avatar') || 'https://via.placeholder.com/32x32?text=?';
                            return '<div class="employee-cell">' +
                                   '<img src="' + avatar + '" class="employee-avatar">' +
                                   '<span class="employee-name">' + value + '</span>' +
                                   '</div>';
                        }
                    }, {
                        text: 'Department',
                        dataIndex: 'department',
                        width: 120
                    }, {
                        text: 'Status',
                        dataIndex: 'status',
                        width: 100,
                        renderer: function(value) {
                            var cls = value === 'checked_in' ? 'status-in' : 
                                     value === 'checked_out' ? 'status-out' : 
                                     value === 'on_break' ? 'status-break' : 'status-absent';
                            
                            var text = value === 'checked_in' ? 'Checked In' :
                                      value === 'checked_out' ? 'Checked Out' :
                                      value === 'on_break' ? 'On Break' : 'Absent';
                                      
                            return '<span class="attendance-status ' + cls + '">' + text + '</span>';
                        }
                    }, {
                        text: 'Check-in Time',
                        dataIndex: 'check_in_time',
                        width: 120,
                        renderer: function(value) {
                            return value ? Ext.Date.format(new Date(value), 'H:i') : '-';
                        }
                    }, {
                        text: 'Hours Worked',
                        dataIndex: 'hours_worked',
                        width: 100,
                        align: 'center',
                        renderer: function(value) {
                            return value ? value + 'h' : '-';
                        }
                    }, {
                        text: 'Location',
                        dataIndex: 'location',
                        width: 100
                    }, {
                        text: 'Actions',
                        xtype: 'actioncolumn',
                        width: 80,
                        items: [{
                            iconCls: 'fa fa-eye',
                            tooltip: 'View Details',
                            handler: function(view, rowIndex, colIndex, item, e, record) {
                                view.up('attendancepanel').showEmployeeDetails(record);
                            }
                        }]
                    }],
                    
                    bbar: {
                        xtype: 'pagingtoolbar',
                        displayInfo: true
                    },
                    
                    viewConfig: {
                        stripeRows: true,
                        emptyText: 'No attendance records found'
                    }
                }]
            }, {
                title: '<i class="fa fa-calendar"></i> Attendance History',
                itemId: 'historyTab',
                layout: 'border',
                items: [{
                    region: 'north',
                    xtype: 'toolbar',
                    items: [{
                        xtype: 'datefield',
                        fieldLabel: 'From',
                        labelWidth: 40,
                        width: 160,
                        value: Ext.Date.subtract(new Date(), Ext.Date.MONTH, 1),
                        listeners: {
                            change: function() {
                                this.up('panel').down('#historyGrid').getStore().reload();
                            }
                        }
                    }, {
                        xtype: 'datefield',
                        fieldLabel: 'To',
                        labelWidth: 20,
                        width: 140,
                        value: new Date(),
                        listeners: {
                            change: function() {
                                this.up('panel').down('#historyGrid').getStore().reload();
                            }
                        }
                    }, '-', {
                        xtype: 'combobox',
                        fieldLabel: 'Employee',
                        labelWidth: 60,
                        width: 200,
                        store: Ext.create('HRApp.store.EmployeeComboStore'),
                        displayField: 'name',
                        valueField: 'id',
                        emptyText: 'All employees',
                        listeners: {
                            change: function() {
                                this.up('panel').down('#historyGrid').getStore().reload();
                            }
                        }
                    }, {
                        xtype: 'combobox',
                        fieldLabel: 'Department',
                        labelWidth: 70,
                        width: 200,
                        store: Ext.create('HRApp.store.DepartmentComboStore'),
                        displayField: 'name',
                        valueField: 'id',
                        emptyText: 'All departments',
                        listeners: {
                            change: function() {
                                this.up('panel').down('#historyGrid').getStore().reload();
                            }
                        }
                    }, '->', {
                        text: '<i class="fa fa-download"></i> Export',
                        handler: function() {
                            this.up('attendancepanel').exportAttendance();
                        }
                    }]
                }, {
                    region: 'center',
                    xtype: 'grid',
                    itemId: 'historyGrid',
                    store: Ext.create('HRApp.store.AttendanceHistoryStore'),
                    
                    columns: [{
                        text: 'Date',
                        dataIndex: 'date',
                        width: 100,
                        renderer: function(value) {
                            return Ext.Date.format(new Date(value), 'M j, Y');
                        }
                    }, {
                        text: 'Employee',
                        dataIndex: 'employee_name',
                        flex: 1
                    }, {
                        text: 'Check-in',
                        dataIndex: 'check_in_time',
                        width: 100,
                        renderer: function(value) {
                            return value ? Ext.Date.format(new Date(value), 'H:i') : '-';
                        }
                    }, {
                        text: 'Check-out',
                        dataIndex: 'check_out_time',
                        width: 100,
                        renderer: function(value) {
                            return value ? Ext.Date.format(new Date(value), 'H:i') : '-';
                        }
                    }, {
                        text: 'Hours',
                        dataIndex: 'total_hours',
                        width: 80,
                        align: 'center',
                        renderer: function(value) {
                            return value ? value + 'h' : '-';
                        }
                    }, {
                        text: 'Break Time',
                        dataIndex: 'break_duration',
                        width: 80,
                        align: 'center',
                        renderer: function(value) {
                            return value ? value + 'm' : '-';
                        }
                    }, {
                        text: 'Overtime',
                        dataIndex: 'overtime_hours',
                        width: 80,
                        align: 'center',
                        renderer: function(value) {
                            if (!value || value <= 0) return '-';
                            return '<span style="color: #f0ad4e; font-weight: bold;">' + value + 'h</span>';
                        }
                    }, {
                        text: 'Status',
                        dataIndex: 'status',
                        width: 100,
                        renderer: function(value, metaData, record) {
                            var cls = 'attendance-status';
                            var text = value;
                            
                            switch(value) {
                                case 'present':
                                    cls += ' status-present';
                                    text = 'Present';
                                    break;
                                case 'late':
                                    cls += ' status-late';
                                    text = 'Late';
                                    break;
                                case 'absent':
                                    cls += ' status-absent';
                                    text = 'Absent';
                                    break;
                                case 'half_day':
                                    cls += ' status-half';
                                    text = 'Half Day';
                                    break;
                            }
                            
                            return '<span class="' + cls + '">' + text + '</span>';
                        }
                    }],
                    
                    bbar: {
                        xtype: 'pagingtoolbar',
                        displayInfo: true
                    }
                }]
            }, {
                title: '<i class="fa fa-calendar-times-o"></i> Leave Requests',
                itemId: 'leaveTab',
                layout: 'border',
                items: [{
                    region: 'north',
                    xtype: 'toolbar',
                    items: [{
                        text: '<i class="fa fa-plus"></i> New Leave Request',
                        cls: 'hr-btn-primary',
                        handler: function() {
                            Ext.create('HRApp.view.attendance.LeaveRequestForm').show();
                        }
                    }, '-', {
                        xtype: 'combobox',
                        fieldLabel: 'Status',
                        labelWidth: 50,
                        width: 150,
                        store: ['all', 'pending', 'approved', 'rejected'],
                        value: 'all',
                        listeners: {
                            change: function() {
                                this.up('panel').down('#leaveGrid').getStore().reload();
                            }
                        }
                    }]
                }, {
                    region: 'center',
                    xtype: 'grid',
                    itemId: 'leaveGrid',
                    store: Ext.create('HRApp.store.LeaveRequestStore'),
                    
                    columns: [{
                        text: 'Employee',
                        dataIndex: 'employee_name',
                        flex: 1
                    }, {
                        text: 'Leave Type',
                        dataIndex: 'leave_type',
                        width: 120,
                        renderer: function(value) {
                            var types = {
                                'vacation': 'Vacation',
                                'sick': 'Sick Leave',
                                'personal': 'Personal',
                                'emergency': 'Emergency',
                                'maternity': 'Maternity',
                                'paternity': 'Paternity'
                            };
                            return types[value] || value;
                        }
                    }, {
                        text: 'Start Date',
                        dataIndex: 'start_date',
                        width: 100,
                        renderer: function(value) {
                            return Ext.Date.format(new Date(value), 'M j, Y');
                        }
                    }, {
                        text: 'End Date',
                        dataIndex: 'end_date',
                        width: 100,
                        renderer: function(value) {
                            return Ext.Date.format(new Date(value), 'M j, Y');
                        }
                    }, {
                        text: 'Days',
                        dataIndex: 'total_days',
                        width: 60,
                        align: 'center'
                    }, {
                        text: 'Status',
                        dataIndex: 'status',
                        width: 100,
                        renderer: function(value) {
                            var cls = 'leave-status';
                            var text = value;
                            
                            switch(value) {
                                case 'pending':
                                    cls += ' status-pending';
                                    text = 'Pending';
                                    break;
                                case 'approved':
                                    cls += ' status-approved';
                                    text = 'Approved';
                                    break;
                                case 'rejected':
                                    cls += ' status-rejected';
                                    text = 'Rejected';
                                    break;
                            }
                            
                            return '<span class="' + cls + '">' + text + '</span>';
                        }
                    }, {
                        text: 'Actions',
                        xtype: 'actioncolumn',
                        width: 100,
                        items: [{
                            iconCls: 'fa fa-eye',
                            tooltip: 'View Details',
                            handler: function(view, rowIndex, colIndex, item, e, record) {
                                view.up('attendancepanel').showLeaveDetails(record);
                            }
                        }, {
                            iconCls: 'fa fa-check',
                            tooltip: 'Approve',
                            isDisabled: function(view, rowIndex, colIndex, item, record) {
                                return record.get('status') !== 'pending';
                            },
                            handler: function(view, rowIndex, colIndex, item, e, record) {
                                view.up('attendancepanel').approveLeave(record);
                            }
                        }, {
                            iconCls: 'fa fa-times',
                            tooltip: 'Reject',
                            isDisabled: function(view, rowIndex, colIndex, item, record) {
                                return record.get('status') !== 'pending';
                            },
                            handler: function(view, rowIndex, colIndex, item, e, record) {
                                view.up('attendancepanel').rejectLeave(record);
                            }
                        }]
                    }],
                    
                    bbar: {
                        xtype: 'pagingtoolbar',
                        displayInfo: true
                    }
                }]
            }]
        }]
    }],
    
    listeners: {
        afterrender: function() {
            this.initializeAttendance();
            this.startClock();
        }
    },
    
    // Methods
    initializeAttendance: function() {
        var me = this;
        
        // Load today's status
        me.loadTodayStatus();
        
        // Load live attendance
        me.down('#liveGrid').getStore().load();
        
        // Check current attendance status to enable/disable buttons
        me.checkCurrentStatus();
    },
    
    startClock: function() {
        var me = this,
            clockField = me.down('#currentTime');
        
        setInterval(function() {
            if (clockField && !clockField.destroyed) {
                clockField.setText('<i class="fa fa-clock-o"></i> Current Time: ' + 
                                 Ext.Date.format(new Date(), 'H:i:s'));
            }
        }, 1000);
    },
    
    loadTodayStatus: function() {
        var me = this,
            statusPanel = me.down('#todayStatus');
        
        Ext.Ajax.request({
            url: '/api/hr/attendance/today',
            method: 'GET',
            success: function(response) {
                var data = Ext.decode(response.responseText).data;
                statusPanel.update(data);
            },
            failure: function() {
                statusPanel.update({
                    status_text: 'Unable to load status',
                    status_class: 'status-error',
                    status_icon: 'fa-exclamation-triangle'
                });
            }
        });
    },
    
    checkCurrentStatus: function() {
        var me = this,
            clockInBtn = me.down('#clockInBtn'),
            clockOutBtn = me.down('#clockOutBtn');
        
        Ext.Ajax.request({
            url: '/api/hr/attendance/current-status',
            method: 'GET',
            success: function(response) {
                var result = Ext.decode(response.responseText);
                var isCheckedIn = result.data.is_checked_in;
                
                clockInBtn.setDisabled(isCheckedIn);
                clockOutBtn.setDisabled(!isCheckedIn);
            }
        });
    },
    
    clockIn: function() {
        var me = this;
        
        Ext.Msg.confirm('Clock In', 'Are you ready to start your work day?', function(btn) {
            if (btn === 'yes') {
                Ext.Ajax.request({
                    url: '/api/hr/attendance/check-in',
                    method: 'POST',
                    jsonData: {
                        location: 'Office', // Could be determined via geolocation
                        notes: ''
                    },
                    success: function(response) {
                        var result = Ext.decode(response.responseText);
                        
                        Ext.toast({
                            html: 'Successfully clocked in at ' + 
                                  Ext.Date.format(new Date(result.data.check_in_time), 'H:i'),
                            closable: false,
                            align: 't',
                            slideInDuration: 400,
                            minWidth: 400
                        });
                        
                        me.checkCurrentStatus();
                        me.loadTodayStatus();
                        me.refreshData();
                    },
                    failure: function(response) {
                        var error = Ext.decode(response.responseText);
                        Ext.Msg.alert('Error', error.message || 'Failed to clock in');
                    }
                });
            }
        });
    },
    
    clockOut: function() {
        var me = this;
        
        Ext.Msg.confirm('Clock Out', 'Are you ready to end your work day?', function(btn) {
            if (btn === 'yes') {
                Ext.Ajax.request({
                    url: '/api/hr/attendance/check-out',
                    method: 'POST',
                    jsonData: {
                        notes: ''
                    },
                    success: function(response) {
                        var result = Ext.decode(response.responseText);
                        
                        Ext.toast({
                            html: 'Successfully clocked out at ' + 
                                  Ext.Date.format(new Date(result.data.check_out_time), 'H:i') +
                                  '<br>Total hours worked: ' + result.data.total_hours + 'h',
                            closable: false,
                            align: 't',
                            slideInDuration: 400,
                            minWidth: 400
                        });
                        
                        me.checkCurrentStatus();
                        me.loadTodayStatus();
                        me.refreshData();
                    },
                    failure: function(response) {
                        var error = Ext.decode(response.responseText);
                        Ext.Msg.alert('Error', error.message || 'Failed to clock out');
                    }
                });
            }
        });
    },
    
    refreshData: function() {
        var me = this;
        
        // Refresh all stores
        me.down('#liveGrid').getStore().reload();
        me.down('#historyGrid').getStore().reload();
        me.down('#leaveGrid').getStore().reload();
        
        // Reload today's status
        me.loadTodayStatus();
    },
    
    showMyAttendance: function() {
        // Implementation for showing personal attendance
        console.log('Show my attendance');
    },
    
    showTeamAttendance: function() {
        // Implementation for showing team attendance
        console.log('Show team attendance');
    },
    
    showEmployeeDetails: function(record) {
        // Implementation for showing employee attendance details
        console.log('Show employee details:', record.get('employee_name'));
    },
    
    showLeaveDetails: function(record) {
        // Implementation for showing leave request details
        console.log('Show leave details:', record.get('id'));
    },
    
    approveLeave: function(record) {
        var me = this;
        
        Ext.Msg.confirm('Approve Leave', 
            'Are you sure you want to approve this leave request?', 
            function(btn) {
                if (btn === 'yes') {
                    Ext.Ajax.request({
                        url: '/api/hr/leave-requests/' + record.get('id') + '/approve',
                        method: 'POST',
                        success: function() {
                            Ext.toast({
                                html: 'Leave request approved successfully',
                                closable: false,
                                align: 't',
                                slideInDuration: 400
                            });
                            me.down('#leaveGrid').getStore().reload();
                        },
                        failure: function(response) {
                            var error = Ext.decode(response.responseText);
                            Ext.Msg.alert('Error', error.message || 'Failed to approve leave');
                        }
                    });
                }
            }
        );
    },
    
    rejectLeave: function(record) {
        var me = this;
        
        Ext.Msg.prompt('Reject Leave', 'Please provide a reason for rejection:', function(btn, text) {
            if (btn === 'ok' && text) {
                Ext.Ajax.request({
                    url: '/api/hr/leave-requests/' + record.get('id') + '/reject',
                    method: 'POST',
                    jsonData: {
                        reason: text
                    },
                    success: function() {
                        Ext.toast({
                            html: 'Leave request rejected successfully',
                            closable: false,
                            align: 't',
                            slideInDuration: 400
                        });
                        me.down('#leaveGrid').getStore().reload();
                    },
                    failure: function(response) {
                        var error = Ext.decode(response.responseText);
                        Ext.Msg.alert('Error', error.message || 'Failed to reject leave');
                    }
                });
            }
        });
    },
    
    exportAttendance: function() {
        // Implementation for exporting attendance data
        window.open('/api/hr/attendance/export?format=xlsx', '_blank');
    }
});