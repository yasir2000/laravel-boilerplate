/**
 * HR Dashboard Panel
 * Executive dashboard with KPIs, real-time metrics, and interactive charts
 */
Ext.define('HRApp.view.dashboard.DashboardPanel', {
    extend: 'Ext.panel.Panel',
    xtype: 'hrdashboard',
    
    layout: 'border',
    
    title: '<i class="fa fa-dashboard"></i> HR Dashboard',
    
    requires: [
        'HRApp.store.DashboardKPIStore',
        'HRApp.store.AttendanceTrendStore',
        'HRApp.store.DepartmentDistributionStore'
    ],
    
    items: [{
        region: 'north',
        xtype: 'container',
        height: 150,
        layout: 'hbox',
        cls: 'hr-kpi-container',
        padding: '10 10 0 10',
        
        items: [{
            xtype: 'panel',
            flex: 1,
            margin: '0 5 0 0',
            cls: 'hr-kpi-card kpi-employees',
            layout: 'hbox',
            bodyPadding: 15,
            
            items: [{
                xtype: 'container',
                flex: 1,
                html: [
                    '<div class="kpi-content">',
                        '<div class="kpi-icon"><i class="fa fa-users"></i></div>',
                        '<div class="kpi-details">',
                            '<div class="kpi-title">Total Employees</div>',
                            '<div class="kpi-value" id="kpi-total-employees">--</div>',
                            '<div class="kpi-trend" id="kpi-employees-trend">--</div>',
                        '</div>',
                    '</div>'
                ].join('')
            }]
        }, {
            xtype: 'panel',
            flex: 1,
            margin: '0 5 0 0',
            cls: 'hr-kpi-card kpi-attendance',
            layout: 'hbox',
            bodyPadding: 15,
            
            items: [{
                xtype: 'container',
                flex: 1,
                html: [
                    '<div class="kpi-content">',
                        '<div class="kpi-icon"><i class="fa fa-clock-o"></i></div>',
                        '<div class="kpi-details">',
                            '<div class="kpi-title">Attendance Rate</div>',
                            '<div class="kpi-value" id="kpi-attendance-rate">--</div>',
                            '<div class="kpi-trend" id="kpi-attendance-trend">--</div>',
                        '</div>',
                    '</div>'
                ].join('')
            }]
        }, {
            xtype: 'panel',
            flex: 1,
            margin: '0 5 0 0',
            cls: 'hr-kpi-card kpi-leaves',
            layout: 'hbox',
            bodyPadding: 15,
            
            items: [{
                xtype: 'container',
                flex: 1,
                html: [
                    '<div class="kpi-content">',
                        '<div class="kpi-icon"><i class="fa fa-calendar-times-o"></i></div>',
                        '<div class="kpi-details">',
                            '<div class="kpi-title">Pending Leaves</div>',
                            '<div class="kpi-value" id="kpi-pending-leaves">--</div>',
                            '<div class="kpi-trend" id="kpi-leaves-trend">--</div>',
                        '</div>',
                    '</div>'
                ].join('')
            }]
        }, {
            xtype: 'panel',
            flex: 1,
            cls: 'hr-kpi-card kpi-payroll',
            layout: 'hbox',
            bodyPadding: 15,
            
            items: [{
                xtype: 'container',
                flex: 1,
                html: [
                    '<div class="kpi-content">',
                        '<div class="kpi-icon"><i class="fa fa-money"></i></div>',
                        '<div class="kpi-details">',
                            '<div class="kpi-title">Total Payroll</div>',
                            '<div class="kpi-value" id="kpi-total-payroll">--</div>',
                            '<div class="kpi-trend" id="kpi-payroll-trend">--</div>',
                        '</div>',
                    '</div>'
                ].join('')
            }]
        }]
    }, {
        region: 'center',
        layout: 'border',
        
        items: [{
            region: 'west',
            width: '50%',
            layout: 'border',
            
            items: [{
                region: 'north',
                height: '50%',
                xtype: 'panel',
                title: 'Department Distribution',
                layout: 'fit',
                tools: [{
                    type: 'refresh',
                    handler: function() {
                        this.up('panel').down('polar').getStore().load();
                    }
                }],
                
                items: [{
                    xtype: 'polar',
                    itemId: 'departmentPieChart',
                    store: Ext.create('HRApp.store.DepartmentDistributionStore'),
                    
                    series: [{
                        type: 'pie',
                        angleField: 'employee_count',
                        labelField: 'department_name',
                        donut: 40,
                        colors: ['#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6', '#1abc9c', '#34495e'],
                        
                        label: {
                            field: 'department_name',
                            calloutLine: {
                                length: 30,
                                width: 2
                            }
                        },
                        
                        tooltip: {
                            trackMouse: true,
                            renderer: function(tooltip, record, item) {
                                var percentage = ((record.get('employee_count') / record.store.sum('employee_count')) * 100).toFixed(1);
                                tooltip.setHtml(
                                    '<strong>' + record.get('department_name') + '</strong><br/>' +
                                    'Employees: ' + record.get('employee_count') + '<br/>' +
                                    'Percentage: ' + percentage + '%'
                                );
                            }
                        }
                    }]
                }]
            }, {
                region: 'center',
                xtype: 'panel',
                title: 'Monthly Attendance Trends',
                layout: 'fit',
                tools: [{
                    type: 'refresh',
                    handler: function() {
                        this.up('panel').down('cartesian').getStore().load();
                    }
                }],
                
                items: [{
                    xtype: 'cartesian',
                    itemId: 'attendanceTrendChart',
                    store: Ext.create('HRApp.store.AttendanceTrendStore'),
                    
                    axes: [{
                        type: 'numeric',
                        position: 'left',
                        title: 'Attendance Rate (%)',
                        minimum: 0,
                        maximum: 100,
                        grid: true
                    }, {
                        type: 'category',
                        position: 'bottom',
                        title: 'Month'
                    }],
                    
                    series: [{
                        type: 'line',
                        xField: 'month',
                        yField: 'attendance_rate',
                        smooth: true,
                        style: {
                            stroke: '#3498db',
                            lineWidth: 3
                        },
                        marker: {
                            type: 'circle',
                            stroke: '#3498db',
                            fill: '#3498db',
                            radius: 4
                        },
                        tooltip: {
                            trackMouse: true,
                            renderer: function(tooltip, record, item) {
                                tooltip.setHtml(record.get('month') + ': ' + record.get('attendance_rate') + '%');
                            }
                        }
                    }]
                }]
            }]
        }, {
            region: 'center',
            layout: 'border',
            
            items: [{
                region: 'north',
                height: '50%',
                xtype: 'panel',
                title: 'Employee Growth & Analytics',
                layout: 'fit',
                
                items: [{
                    xtype: 'container',
                    itemId: 'employeeGrowthChart',
                    padding: 10,
                    html: '<canvas id="employeeGrowthChartCanvas" style="width: 100%; height: 250px;"></canvas>'
                }]
            }, {
                region: 'center',
                xtype: 'tabpanel',
                
                items: [{
                    title: 'Quick Stats',
                    iconCls: 'fa fa-line-chart',
                    layout: 'fit',
                    
                    items: [{
                        xtype: 'container',
                        itemId: 'quickStatsContainer',
                        padding: 15,
                        scrollable: true,
                        tpl: [
                            '<div class="quick-stats-grid">',
                                '<tpl for="stats">',
                                    '<div class="stat-item">',
                                        '<div class="stat-icon"><i class="{icon}"></i></div>',
                                        '<div class="stat-details">',
                                            '<div class="stat-label">{label}</div>',
                                            '<div class="stat-value">{value}</div>',
                                        '</div>',
                                    '</div>',
                                '</tpl>',
                            '</div>'
                        ]
                    }]
                }, {
                    title: 'Recent Activity',
                    iconCls: 'fa fa-history',
                    layout: 'fit',
                    
                    items: [{
                        xtype: 'dataview',
                        itemId: 'recentActivityView',
                        store: Ext.create('Ext.data.Store', {
                            fields: ['type', 'description', 'timestamp', 'user', 'icon'],
                            data: []
                        }),
                        
                        tpl: [
                            '<div class="activity-timeline">',
                                '<tpl for=".">',
                                    '<div class="activity-item">',
                                        '<div class="activity-icon"><i class="{icon}"></i></div>',
                                        '<div class="activity-content">',
                                            '<div class="activity-description">{description}</div>',
                                            '<div class="activity-meta">',
                                                '<span class="activity-user">{user}</span>',
                                                '<span class="activity-time">{timestamp}</span>',
                                            '</div>',
                                        '</div>',
                                    '</div>',
                                '</tpl>',
                            '</div>'
                        ],
                        
                        emptyText: 'No recent activity'
                    }]
                }, {
                    title: 'Alerts & Notifications',
                    iconCls: 'fa fa-bell',
                    layout: 'fit',
                    
                    items: [{
                        xtype: 'container',
                        itemId: 'alertsContainer',
                        padding: 15,
                        html: '<div class="alerts-placeholder">Loading alerts...</div>'
                    }]
                }]
            }]
        }]
    }],
    
    initComponent: function() {
        var me = this;
        
        me.callParent();
        
        // Load initial data
        me.on('afterrender', function() {
            me.loadDashboardData();
            me.initializeCharts();
            
            // Set up auto-refresh
            me.autoRefreshTask = Ext.TaskManager.start({
                run: me.loadKPIData,
                scope: me,
                interval: 30000 // Refresh every 30 seconds
            });
        });
        
        me.on('destroy', function() {
            if (me.autoRefreshTask) {
                Ext.TaskManager.stop(me.autoRefreshTask);
            }
        });
    },
    
    loadDashboardData: function() {
        var me = this;
        
        // Load KPI data
        me.loadKPIData();
        
        // Load chart data
        me.loadChartData();
        
        // Load quick stats
        me.loadQuickStats();
        
        // Load recent activity
        me.loadRecentActivity();
        
        // Load alerts
        me.loadAlerts();
    },
    
    loadKPIData: function() {
        var me = this;
        
        Ext.Ajax.request({
            url: '/api/hr/dashboard',
            method: 'GET',
            
            success: function(response) {
                var result = Ext.decode(response.responseText);
                
                if (result.success && result.data) {
                    var data = result.data;
                    
                    // Update KPI values
                    me.updateKPI('kpi-total-employees', data.total_employees, data.employees_trend);
                    me.updateKPI('kpi-attendance-rate', data.attendance_rate + '%', data.attendance_trend);
                    me.updateKPI('kpi-pending-leaves', data.pending_leave_requests, data.leaves_trend);
                    me.updateKPI('kpi-total-payroll', '$' + me.formatNumber(data.total_payroll), data.payroll_trend);
                }
            },
            
            failure: function() {
                console.error('Failed to load dashboard KPI data');
            }
        });
    },
    
    loadChartData: function() {
        var me = this;
        
        // Load ExtJS chart data
        var departmentChart = me.down('#departmentPieChart');
        var attendanceChart = me.down('#attendanceTrendChart');
        
        if (departmentChart) {
            departmentChart.getStore().load();
        }
        
        if (attendanceChart) {
            attendanceChart.getStore().load();
        }
        
        // Load Chart.js data
        me.loadEmployeeGrowthChart();
    },
    
    initializeCharts: function() {
        var me = this;
        
        // Initialize Chart.js charts after a delay to ensure DOM is ready
        setTimeout(function() {
            me.loadEmployeeGrowthChart();
        }, 500);
    },
    
    loadEmployeeGrowthChart: function() {
        var me = this;
        
        // Load data for Chart.js chart
        Ext.Ajax.request({
            url: '/api/hr/reports/employee-growth',
            method: 'GET',
            
            success: function(response) {
                var result = Ext.decode(response.responseText);
                
                if (result.success && result.data) {
                    me.renderEmployeeGrowthChart(result.data);
                }
            },
            
            failure: function() {
                console.error('Failed to load employee growth data');
                me.renderEmployeeGrowthChart([]);
            }
        });
    },
    
    renderEmployeeGrowthChart: function(data) {
        var canvas = document.getElementById('employeeGrowthChartCanvas');
        
        if (!canvas || typeof Chart === 'undefined') {
            console.warn('Chart.js not available or canvas not found');
            return;
        }
        
        // Destroy existing chart if it exists
        if (this.employeeGrowthChart) {
            this.employeeGrowthChart.destroy();
        }
        
        var ctx = canvas.getContext('2d');
        
        this.employeeGrowthChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(function(item) { return item.month; }),
                datasets: [{
                    label: 'New Hires',
                    data: data.map(function(item) { return item.new_hires; }),
                    backgroundColor: 'rgba(52, 152, 219, 0.8)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1
                }, {
                    label: 'Terminations',
                    data: data.map(function(item) { return item.terminations; }),
                    backgroundColor: 'rgba(231, 76, 60, 0.8)',
                    borderColor: 'rgba(231, 76, 60, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Employee Growth & Turnover'
                    },
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Employees'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                }
            }
        });
    },
    
    loadQuickStats: function() {
        var me = this;
        
        var statsData = {
            stats: [
                { icon: 'fa fa-building', label: 'Departments', value: '--' },
                { icon: 'fa fa-briefcase', label: 'Open Positions', value: '--' },
                { icon: 'fa fa-graduation-cap', label: 'In Training', value: '--' },
                { icon: 'fa fa-calendar-check-o', label: 'Present Today', value: '--' },
                { icon: 'fa fa-clock-o', label: 'Average Hours/Week', value: '--' },
                { icon: 'fa fa-trophy', label: 'Top Performer', value: '--' }
            ]
        };
        
        var container = me.down('#quickStatsContainer');
        if (container) {
            container.setData(statsData);
        }
        
        // Load actual data
        Ext.Ajax.request({
            url: '/api/hr/dashboard/quick-stats',
            method: 'GET',
            
            success: function(response) {
                var result = Ext.decode(response.responseText);
                
                if (result.success && result.data) {
                    var data = result.data;
                    statsData.stats[0].value = data.total_departments;
                    statsData.stats[1].value = data.open_positions;
                    statsData.stats[2].value = data.in_training;
                    statsData.stats[3].value = data.present_today;
                    statsData.stats[4].value = data.average_hours + 'h';
                    statsData.stats[5].value = data.top_performer || 'N/A';
                    
                    container.setData(statsData);
                }
            }
        });
    },
    
    loadRecentActivity: function() {
        var me = this;
        
        Ext.Ajax.request({
            url: '/api/hr/dashboard/recent-activity',
            method: 'GET',
            
            success: function(response) {
                var result = Ext.decode(response.responseText);
                
                if (result.success && result.data) {
                    var activityView = me.down('#recentActivityView');
                    if (activityView) {
                        activityView.getStore().loadData(result.data);
                    }
                }
            }
        });
    },
    
    loadAlerts: function() {
        var me = this;
        
        Ext.Ajax.request({
            url: '/api/hr/dashboard/alerts',
            method: 'GET',
            
            success: function(response) {
                var result = Ext.decode(response.responseText);
                
                if (result.success && result.data) {
                    var alertsContainer = me.down('#alertsContainer');
                    if (alertsContainer) {
                        var html = result.data.map(function(alert) {
                            return [
                                '<div class="alert-item alert-' + alert.type + '">',
                                    '<div class="alert-icon"><i class="' + alert.icon + '"></i></div>',
                                    '<div class="alert-content">',
                                        '<div class="alert-title">' + alert.title + '</div>',
                                        '<div class="alert-message">' + alert.message + '</div>',
                                    '</div>',
                                '</div>'
                            ].join('');
                        }).join('');
                        
                        alertsContainer.update(html || '<div class="no-alerts">No alerts at this time</div>');
                    }
                }
            }
        });
    },
    
    updateKPI: function(elementId, value, trend) {
        var element = document.getElementById(elementId);
        var trendElement = document.getElementById(elementId.replace('kpi-', 'kpi-') + '-trend');
        
        if (element) {
            element.innerHTML = value;
        }
        
        if (trendElement && trend !== undefined) {
            var trendClass = trend > 0 ? 'trend-up' : trend < 0 ? 'trend-down' : 'trend-stable';
            var trendIcon = trend > 0 ? '↗' : trend < 0 ? '↘' : '→';
            trendElement.innerHTML = '<span class="' + trendClass + '">' + trendIcon + ' ' + Math.abs(trend) + '%</span>';
        }
    },
    
    formatNumber: function(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    },
    
    refreshAllCharts: function() {
        this.loadDashboardData();
    }
});