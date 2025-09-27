/**
 * Reports and Dashboard Panel
 * Comprehensive reporting system with interactive charts and HR analytics
 */
Ext.define('HRApp.view.reports.ReportsPanel', {
    extend: 'Ext.panel.Panel',
    xtype: 'reportspanel',
    
    layout: 'border',
    
    title: '<i class="fa fa-bar-chart"></i> Reports & Analytics',
    
    items: [{
        region: 'north',
        xtype: 'toolbar',
        height: 50,
        items: [{
            text: '<i class="fa fa-refresh"></i> Refresh All',
            cls: 'hr-btn-primary',
            handler: function() {
                this.up('reportspanel').refreshAllReports();
            }
        }, '-', {
            xtype: 'combobox',
            fieldLabel: 'Time Period',
            labelWidth: 70,
            width: 200,
            value: 'current_month',
            store: [
                ['current_week', 'Current Week'],
                ['current_month', 'Current Month'],
                ['current_quarter', 'Current Quarter'],
                ['current_year', 'Current Year'],
                ['last_30_days', 'Last 30 Days'],
                ['last_90_days', 'Last 90 Days'],
                ['custom', 'Custom Range']
            ],
            listeners: {
                change: function(combo, value) {
                    var panel = combo.up('reportspanel');
                    if (value === 'custom') {
                        panel.showCustomDateRange();
                    } else {
                        panel.updateTimePeriod(value);
                    }
                }
            }
        }, {
            xtype: 'combobox',
            fieldLabel: 'Department',
            labelWidth: 70,
            width: 220,
            store: Ext.create('HRApp.store.DepartmentComboStore'),
            displayField: 'name',
            valueField: 'id',
            emptyText: 'All Departments',
            listeners: {
                change: function() {
                    this.up('reportspanel').updateDepartmentFilter();
                }
            }
        }, '->', {
            text: '<i class="fa fa-download"></i> Export Reports',
            menu: [{
                text: 'Export to PDF',
                iconCls: 'fa fa-file-pdf-o',
                handler: function() {
                    this.up('reportspanel').exportToPDF();
                }
            }, {
                text: 'Export to Excel',
                iconCls: 'fa fa-file-excel-o',
                handler: function() {
                    this.up('reportspanel').exportToExcel();
                }
            }, {
                text: 'Export Charts as Images',
                iconCls: 'fa fa-image',
                handler: function() {
                    this.up('reportspanel').exportChartsAsImages();
                }
            }]
        }]
    }, {
        region: 'center',
        xtype: 'tabpanel',
        items: [{
            title: '<i class="fa fa-tachometer"></i> Executive Dashboard',
            itemId: 'dashboardTab',
            layout: 'border',
            items: [{
                region: 'north',
                height: 120,
                xtype: 'panel',
                layout: 'hbox',
                bodyPadding: 10,
                items: [{
                    xtype: 'panel',
                    flex: 1,
                    margin: '0 5 0 0',
                    cls: 'kpi-card kpi-employees',
                    itemId: 'totalEmployeesKPI',
                    tpl: new Ext.XTemplate(
                        '<div class="kpi-content">',
                        '  <div class="kpi-icon"><i class="fa fa-users"></i></div>',
                        '  <div class="kpi-details">',
                        '    <div class="kpi-value">{total_employees}</div>',
                        '    <div class="kpi-label">Total Employees</div>',
                        '    <div class="kpi-change {change_class}">',
                        '      <i class="fa {change_icon}"></i> {change_text}',
                        '    </div>',
                        '  </div>',
                        '</div>'
                    )
                }, {
                    xtype: 'panel',
                    flex: 1,
                    margin: '0 5 0 5',
                    cls: 'kpi-card kpi-attendance',
                    itemId: 'attendanceRateKPI',
                    tpl: new Ext.XTemplate(
                        '<div class="kpi-content">',
                        '  <div class="kpi-icon"><i class="fa fa-clock-o"></i></div>',
                        '  <div class="kpi-details">',
                        '    <div class="kpi-value">{attendance_rate}%</div>',
                        '    <div class="kpi-label">Attendance Rate</div>',
                        '    <div class="kpi-change {change_class}">',
                        '      <i class="fa {change_icon}"></i> {change_text}',
                        '    </div>',
                        '  </div>',
                        '</div>'
                    )
                }, {
                    xtype: 'panel',
                    flex: 1,
                    margin: '0 5 0 5',
                    cls: 'kpi-card kpi-leaves',
                    itemId: 'leaveRequestsKPI',
                    tpl: new Ext.XTemplate(
                        '<div class="kpi-content">',
                        '  <div class="kpi-icon"><i class="fa fa-calendar-times-o"></i></div>',
                        '  <div class="kpi-details">',
                        '    <div class="kpi-value">{pending_leaves}</div>',
                        '    <div class="kpi-label">Pending Leaves</div>',
                        '    <div class="kpi-change {change_class}">',
                        '      <i class="fa {change_icon}"></i> {change_text}',
                        '    </div>',
                        '  </div>',
                        '</div>'
                    )
                }, {
                    xtype: 'panel',
                    flex: 1,
                    margin: '0 0 0 5',
                    cls: 'kpi-card kpi-departments',
                    itemId: 'departmentsKPI',
                    tpl: new Ext.XTemplate(
                        '<div class="kpi-content">',
                        '  <div class="kpi-icon"><i class="fa fa-building"></i></div>',
                        '  <div class="kpi-details">',
                        '    <div class="kpi-value">{active_departments}</div>',
                        '    <div class="kpi-label">Active Departments</div>',
                        '    <div class="kpi-change {change_class}">',
                        '      <i class="fa {change_icon}"></i> {change_text}',
                        '    </div>',
                        '  </div>',
                        '</div>'
                    )
                }]
            }, {
                region: 'center',
                layout: 'hbox',
                items: [{
                    xtype: 'panel',
                    flex: 1,
                    layout: 'fit',
                    title: 'Attendance Trends',
                    margin: '5 5 5 5',
                    items: [{
                        xtype: 'cartesian',
                        itemId: 'attendanceTrendChart',
                        store: Ext.create('HRApp.store.AttendanceTrendStore'),
                        
                        axes: [{
                            type: 'numeric',
                            position: 'left',
                            title: 'Attendance Rate (%)',
                            minimum: 0,
                            maximum: 100
                        }, {
                            type: 'category',
                            position: 'bottom',
                            title: 'Date'
                        }],
                        
                        series: [{
                            type: 'line',
                            xField: 'date',
                            yField: 'attendance_rate',
                            smooth: true,
                            style: {
                                stroke: '#337ab7',
                                lineWidth: 3
                            },
                            marker: {
                                radius: 4,
                                fill: '#337ab7'
                            },
                            highlight: {
                                size: 7,
                                radius: 7
                            }
                        }]
                    }]
                }, {
                    xtype: 'panel',
                    flex: 1,
                    layout: 'fit',
                    title: 'Department Distribution',
                    margin: '5 5 5 0',
                    items: [{
                        xtype: 'polar',
                        itemId: 'departmentChart',
                        store: Ext.create('HRApp.store.DepartmentDistributionStore'),
                        
                        series: [{
                            type: 'pie',
                            angleField: 'employee_count',
                            labelField: 'department_name',
                            donut: 30,
                            colors: ['#337ab7', '#5cb85c', '#f0ad4e', '#d9534f', '#5bc0de', '#777'],
                            
                            label: {
                                field: 'department_name',
                                calloutLine: {
                                    length: 60,
                                    width: 3
                                }
                            },
                            
                            tooltip: {
                                trackMouse: true,
                                renderer: function(tooltip, record, item) {
                                    tooltip.setHtml(record.get('department_name') + ': ' + 
                                                  record.get('employee_count') + ' employees (' + 
                                                  record.get('percentage') + '%)');
                                }
                            }
                        }]
                    }]
                }]
            }]
        }, {
            title: '<i class="fa fa-users"></i> Employee Analytics',
            itemId: 'employeeTab',
            layout: 'border',
            items: [{
                region: 'west',
                width: 300,
                title: 'Employee Statistics',
                layout: 'accordion',
                items: [{
                    title: 'By Employment Type',
                    itemId: 'employmentTypeStats',
                    layout: 'fit',
                    items: [{
                        xtype: 'polar',
                        store: Ext.create('HRApp.store.EmploymentTypeStore'),
                        series: [{
                            type: 'pie',
                            angleField: 'count',
                            labelField: 'type',
                            donut: 40,
                            colors: ['#5cb85c', '#f0ad4e', '#337ab7', '#d9534f']
                        }]
                    }]
                }, {
                    title: 'By Department',
                    itemId: 'departmentStats',
                    layout: 'fit',
                    items: [{
                        xtype: 'cartesian',
                        store: Ext.create('HRApp.store.EmployeeByDepartmentStore'),
                        
                        axes: [{
                            type: 'numeric',
                            position: 'bottom',
                            title: 'Number of Employees'
                        }, {
                            type: 'category',
                            position: 'left',
                            title: 'Department'
                        }],
                        
                        series: [{
                            type: 'bar',
                            xField: 'employee_count',
                            yField: 'department_name',
                            style: {
                                fill: '#337ab7'
                            }
                        }]
                    }]
                }, {
                    title: 'Age Distribution',
                    itemId: 'ageStats',
                    layout: 'fit',
                    items: [{
                        xtype: 'cartesian',
                        store: Ext.create('HRApp.store.AgeDistributionStore'),
                        
                        axes: [{
                            type: 'numeric',
                            position: 'left',
                            title: 'Number of Employees'
                        }, {
                            type: 'category',
                            position: 'bottom',
                            title: 'Age Range'
                        }],
                        
                        series: [{
                            type: 'column',
                            xField: 'age_range',
                            yField: 'count',
                            style: {
                                fill: '#5cb85c'
                            }
                        }]
                    }]
                }]
            }, {
                region: 'center',
                layout: 'fit',
                items: [{
                    xtype: 'grid',
                    title: 'Employee Performance Summary',
                    itemId: 'performanceGrid',
                    store: Ext.create('HRApp.store.EmployeePerformanceStore'),
                    
                    columns: [{
                        text: 'Employee',
                        dataIndex: 'employee_name',
                        flex: 1,
                        renderer: function(value, metaData, record) {
                            return '<div class="employee-cell">' +
                                   '<strong>' + value + '</strong><br>' +
                                   '<small>' + (record.get('department') || 'No Department') + '</small>' +
                                   '</div>';
                        }
                    }, {
                        text: 'Attendance Rate',
                        dataIndex: 'attendance_rate',
                        width: 120,
                        align: 'center',
                        renderer: function(value) {
                            var cls = value >= 95 ? 'performance-excellent' :
                                     value >= 90 ? 'performance-good' :
                                     value >= 80 ? 'performance-average' : 'performance-poor';
                            
                            return '<span class="performance-badge ' + cls + '">' + value + '%</span>';
                        }
                    }, {
                        text: 'Avg Hours/Day',
                        dataIndex: 'avg_daily_hours',
                        width: 100,
                        align: 'center',
                        renderer: function(value) {
                            return value ? value + 'h' : '-';
                        }
                    }, {
                        text: 'Overtime',
                        dataIndex: 'total_overtime',
                        width: 80,
                        align: 'center',
                        renderer: function(value) {
                            if (!value || value <= 0) return '-';
                            return '<span style="color: #f0ad4e; font-weight: bold;">' + value + 'h</span>';
                        }
                    }, {
                        text: 'Leave Days',
                        dataIndex: 'leave_days_used',
                        width: 80,
                        align: 'center'
                    }, {
                        text: 'Performance Score',
                        dataIndex: 'performance_score',
                        width: 120,
                        align: 'center',
                        renderer: function(value) {
                            if (!value) return '-';
                            
                            var cls = value >= 90 ? 'performance-excellent' :
                                     value >= 80 ? 'performance-good' :
                                     value >= 70 ? 'performance-average' : 'performance-poor';
                            
                            return '<span class="performance-badge ' + cls + '">' + value + '</span>';
                        }
                    }],
                    
                    bbar: {
                        xtype: 'pagingtoolbar',
                        displayInfo: true
                    }
                }]
            }]
        }, {
            title: '<i class="fa fa-clock-o"></i> Attendance Reports',
            itemId: 'attendanceReportsTab',
            layout: 'border',
            items: [{
                region: 'north',
                height: 250,
                layout: 'hbox',
                items: [{
                    xtype: 'panel',
                    flex: 1,
                    layout: 'fit',
                    title: 'Daily Attendance Pattern',
                    margin: '5 5 5 5',
                    items: [{
                        xtype: 'cartesian',
                        itemId: 'dailyAttendanceChart',
                        store: Ext.create('HRApp.store.DailyAttendanceStore'),
                        
                        axes: [{
                            type: 'numeric',
                            position: 'left',
                            title: 'Employees'
                        }, {
                            type: 'category',
                            position: 'bottom',
                            title: 'Hour'
                        }],
                        
                        series: [{
                            type: 'area',
                            xField: 'hour',
                            yField: 'check_ins',
                            title: 'Check-ins',
                            style: {
                                fill: 'rgba(51, 122, 183, 0.3)',
                                stroke: '#337ab7'
                            }
                        }, {
                            type: 'area',
                            xField: 'hour',
                            yField: 'check_outs',
                            title: 'Check-outs',
                            style: {
                                fill: 'rgba(240, 173, 78, 0.3)',
                                stroke: '#f0ad4e'
                            }
                        }]
                    }]
                }, {
                    xtype: 'panel',
                    flex: 1,
                    layout: 'fit',
                    title: 'Monthly Attendance Summary',
                    margin: '5 5 5 0',
                    items: [{
                        xtype: 'cartesian',
                        itemId: 'monthlyAttendanceChart',
                        store: Ext.create('HRApp.store.MonthlyAttendanceStore'),
                        
                        axes: [{
                            type: 'numeric',
                            position: 'left',
                            title: 'Days'
                        }, {
                            type: 'category',
                            position: 'bottom',
                            title: 'Month'
                        }],
                        
                        series: [{
                            type: 'column',
                            xField: 'month',
                            yField: 'present_days',
                            title: 'Present',
                            stacked: true,
                            style: {
                                fill: '#5cb85c'
                            }
                        }, {
                            type: 'column',
                            xField: 'month',
                            yField: 'absent_days',
                            title: 'Absent',
                            stacked: true,
                            style: {
                                fill: '#d9534f'
                            }
                        }, {
                            type: 'column',
                            xField: 'month',
                            yField: 'leave_days',
                            title: 'On Leave',
                            stacked: true,
                            style: {
                                fill: '#f0ad4e'
                            }
                        }]
                    }]
                }]
            }, {
                region: 'center',
                xtype: 'grid',
                title: 'Detailed Attendance Report',
                itemId: 'detailedAttendanceGrid',
                store: Ext.create('HRApp.store.DetailedAttendanceStore'),
                
                columns: [{
                    text: 'Employee',
                    dataIndex: 'employee_name',
                    flex: 1
                }, {
                    text: 'Department',
                    dataIndex: 'department',
                    width: 120
                }, {
                    text: 'Present Days',
                    dataIndex: 'present_days',
                    width: 100,
                    align: 'center'
                }, {
                    text: 'Absent Days',
                    dataIndex: 'absent_days',
                    width: 100,
                    align: 'center'
                }, {
                    text: 'Late Arrivals',
                    dataIndex: 'late_arrivals',
                    width: 100,
                    align: 'center'
                }, {
                    text: 'Total Hours',
                    dataIndex: 'total_hours',
                    width: 100,
                    align: 'center',
                    renderer: function(value) {
                        return value ? value + 'h' : '-';
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
                    text: 'Attendance Rate',
                    dataIndex: 'attendance_rate',
                    width: 120,
                    align: 'center',
                    renderer: function(value) {
                        var cls = value >= 95 ? 'performance-excellent' :
                                 value >= 90 ? 'performance-good' :
                                 value >= 80 ? 'performance-average' : 'performance-poor';
                        
                        return '<span class="performance-badge ' + cls + '">' + value + '%</span>';
                    }
                }],
                
                bbar: {
                    xtype: 'pagingtoolbar',
                    displayInfo: true
                }
            }]
        }, {
            title: '<i class="fa fa-calendar-times-o"></i> Leave Analysis',
            itemId: 'leaveAnalysisTab',
            layout: 'border',
            items: [{
                region: 'west',
                width: 350,
                layout: 'vbox',
                items: [{
                    xtype: 'panel',
                    flex: 1,
                    layout: 'fit',
                    title: 'Leave Types Distribution',
                    margin: '5 5 2.5 5',
                    items: [{
                        xtype: 'polar',
                        itemId: 'leaveTypesChart',
                        store: Ext.create('HRApp.store.LeaveTypesStore'),
                        
                        series: [{
                            type: 'pie',
                            angleField: 'count',
                            labelField: 'leave_type',
                            donut: 40,
                            colors: ['#337ab7', '#5cb85c', '#f0ad4e', '#d9534f', '#5bc0de', '#777']
                        }]
                    }]
                }, {
                    xtype: 'panel',
                    flex: 1,
                    layout: 'fit',
                    title: 'Leave Trends',
                    margin: '2.5 5 5 5',
                    items: [{
                        xtype: 'cartesian',
                        itemId: 'leaveTrendsChart',
                        store: Ext.create('HRApp.store.LeaveTrendsStore'),
                        
                        axes: [{
                            type: 'numeric',
                            position: 'left',
                            title: 'Leave Requests'
                        }, {
                            type: 'category',
                            position: 'bottom',
                            title: 'Month'
                        }],
                        
                        series: [{
                            type: 'line',
                            xField: 'month',
                            yField: 'approved_leaves',
                            title: 'Approved',
                            style: {
                                stroke: '#5cb85c',
                                lineWidth: 3
                            }
                        }, {
                            type: 'line',
                            xField: 'month',
                            yField: 'rejected_leaves',
                            title: 'Rejected',
                            style: {
                                stroke: '#d9534f',
                                lineWidth: 3
                            }
                        }]
                    }]
                }]
            }, {
                region: 'center',
                xtype: 'grid',
                title: 'Leave Summary by Department',
                itemId: 'leaveSummaryGrid',
                store: Ext.create('HRApp.store.LeaveSummaryStore'),
                
                columns: [{
                    text: 'Department',
                    dataIndex: 'department_name',
                    flex: 1
                }, {
                    text: 'Total Requests',
                    dataIndex: 'total_requests',
                    width: 120,
                    align: 'center'
                }, {
                    text: 'Approved',
                    dataIndex: 'approved_requests',
                    width: 100,
                    align: 'center',
                    renderer: function(value) {
                        return '<span style="color: #5cb85c; font-weight: bold;">' + value + '</span>';
                    }
                }, {
                    text: 'Pending',
                    dataIndex: 'pending_requests',
                    width: 100,
                    align: 'center',
                    renderer: function(value) {
                        return '<span style="color: #f0ad4e; font-weight: bold;">' + value + '</span>';
                    }
                }, {
                    text: 'Rejected',
                    dataIndex: 'rejected_requests',
                    width: 100,
                    align: 'center',
                    renderer: function(value) {
                        return '<span style="color: #d9534f; font-weight: bold;">' + value + '</span>';
                    }
                }, {
                    text: 'Approval Rate',
                    dataIndex: 'approval_rate',
                    width: 120,
                    align: 'center',
                    renderer: function(value) {
                        var cls = value >= 90 ? 'performance-excellent' :
                                 value >= 80 ? 'performance-good' :
                                 value >= 70 ? 'performance-average' : 'performance-poor';
                        
                        return '<span class="performance-badge ' + cls + '">' + value + '%</span>';
                    }
                }, {
                    text: 'Avg Days/Request',
                    dataIndex: 'avg_days_per_request',
                    width: 120,
                    align: 'center',
                    renderer: function(value) {
                        return value ? value.toFixed(1) + ' days' : '-';
                    }
                }],
                
                bbar: {
                    xtype: 'pagingtoolbar',
                    displayInfo: true
                }
            }]
        }]
    }],
    
    listeners: {
        afterrender: function() {
            this.loadDashboardData();
            this.startAutoRefresh();
        }
    },
    
    // Methods
    loadDashboardData: function() {
        var me = this;
        
        // Load KPI data
        me.loadKPIData();
        
        // Load all chart stores
        me.loadChartData();
    },
    
    loadKPIData: function() {
        var me = this;
        
        Ext.Ajax.request({
            url: '/api/hr/dashboard/kpis',
            method: 'GET',
            success: function(response) {
                var data = Ext.decode(response.responseText).data;
                
                // Update KPI cards
                me.down('#totalEmployeesKPI').update(data.employees);
                me.down('#attendanceRateKPI').update(data.attendance);
                me.down('#leaveRequestsKPI').update(data.leaves);
                me.down('#departmentsKPI').update(data.departments);
            },
            failure: function() {
                console.error('Failed to load KPI data');
            }
        });
    },
    
    loadChartData: function() {
        var me = this;
        
        // Load all chart stores
        var stores = [
            me.down('#attendanceTrendChart').getStore(),
            me.down('#departmentChart').getStore(),
            me.down('#dailyAttendanceChart').getStore(),
            me.down('#monthlyAttendanceChart').getStore(),
            me.down('#leaveTypesChart').getStore(),
            me.down('#leaveTrendsChart').getStore()
        ];
        
        stores.forEach(function(store) {
            if (store) {
                store.load();
            }
        });
        
        // Load grid stores
        var grids = [
            me.down('#performanceGrid'),
            me.down('#detailedAttendanceGrid'),
            me.down('#leaveSummaryGrid')
        ];
        
        grids.forEach(function(grid) {
            if (grid && grid.getStore()) {
                grid.getStore().load();
            }
        });
    },
    
    startAutoRefresh: function() {
        var me = this;
        
        // Refresh data every 5 minutes
        setInterval(function() {
            me.loadKPIData();
        }, 300000); // 5 minutes
    },
    
    refreshAllReports: function() {
        var me = this;
        
        me.loadDashboardData();
        
        Ext.toast({
            html: 'All reports refreshed successfully',
            closable: false,
            align: 't',
            slideInDuration: 400
        });
    },
    
    updateTimePeriod: function(period) {
        var me = this;
        
        // Update all stores with new time period
        me.currentTimePeriod = period;
        me.loadChartData();
    },
    
    updateDepartmentFilter: function() {
        var me = this;
        
        // Update charts and grids with department filter
        me.loadChartData();
    },
    
    showCustomDateRange: function() {
        var me = this;
        
        // Show custom date range picker
        Ext.create('Ext.window.Window', {
            title: 'Custom Date Range',
            modal: true,
            width: 400,
            height: 200,
            layout: 'fit',
            items: [{
                xtype: 'form',
                bodyPadding: 20,
                items: [{
                    xtype: 'datefield',
                    name: 'start_date',
                    fieldLabel: 'Start Date',
                    allowBlank: false,
                    value: Ext.Date.subtract(new Date(), Ext.Date.MONTH, 1)
                }, {
                    xtype: 'datefield',
                    name: 'end_date',
                    fieldLabel: 'End Date',
                    allowBlank: false,
                    value: new Date()
                }],
                buttons: [{
                    text: 'Apply',
                    formBind: true,
                    handler: function() {
                        var form = this.up('form');
                        var values = form.getValues();
                        
                        me.customDateRange = values;
                        me.loadChartData();
                        
                        this.up('window').close();
                    }
                }, {
                    text: 'Cancel',
                    handler: function() {
                        this.up('window').close();
                    }
                }]
            }]
        }).show();
    },
    
    exportToPDF: function() {
        // Implementation for PDF export
        window.open('/api/hr/reports/export/pdf', '_blank');
    },
    
    exportToExcel: function() {
        // Implementation for Excel export
        window.open('/api/hr/reports/export/excel', '_blank');
    },
    
    exportChartsAsImages: function() {
        // Implementation for chart image export
        Ext.Msg.alert('Export Charts', 'Chart image export functionality would be implemented here');
    }
});