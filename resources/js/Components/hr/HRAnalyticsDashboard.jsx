import React, { useState, useEffect, useCallback } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    BarChart3, 
    TrendingUp, 
    TrendingDown, 
    Users, 
    Calendar, 
    Target, 
    DollarSign,
    AlertCircle,
    RefreshCw,
    Download,
    Filter,
    Eye,
    Activity,
    Clock,
    Award,
    Building,
    UserCheck,
    Zap
} from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    LineChart,
    Line,
    AreaChart,
    Area,
    BarChart,
    Bar,
    PieChart,
    Pie,
    Cell,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer
} from 'recharts';
import { useToast } from '@/hooks/use-toast';

const HRAnalyticsDashboard = () => {
    const { toast } = useToast();
    const [loading, setLoading] = useState(true);
    const [lastUpdated, setLastUpdated] = useState(new Date());
    const [autoRefresh, setAutoRefresh] = useState(true);
    const [refreshInterval, setRefreshInterval] = useState(30); // seconds

    // Dashboard data
    const [dashboardData, setDashboardData] = useState({
        kpi_metrics: {},
        attendance_trends: [],
        department_performance: [],
        employee_distribution: [],
        performance_metrics: [],
        recent_activities: [],
        alerts: []
    });

    // Filters
    const [timeRange, setTimeRange] = useState('30_days');
    const [selectedDepartment, setSelectedDepartment] = useState('all');
    const [departments, setDepartments] = useState([]);

    // Chart colors
    const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];

    useEffect(() => {
        fetchDashboardData();
        fetchDepartments();
    }, [timeRange, selectedDepartment]);

    useEffect(() => {
        let interval;
        if (autoRefresh) {
            interval = setInterval(() => {
                fetchDashboardData();
            }, refreshInterval * 1000);
        }
        return () => clearInterval(interval);
    }, [autoRefresh, refreshInterval, timeRange, selectedDepartment]);

    const fetchDashboardData = useCallback(async () => {
        try {
            setLoading(true);
            const params = new URLSearchParams({
                time_range: timeRange,
                department: selectedDepartment
            });

            const response = await fetch(`/api/hr/analytics/dashboard?${params}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });

            if (response.ok) {
                const data = await response.json();
                setDashboardData(data.data);
                setLastUpdated(new Date());
            } else {
                throw new Error('Failed to fetch dashboard data');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to fetch dashboard data",
                variant: "destructive",
            });
        } finally {
            setLoading(false);
        }
    }, [timeRange, selectedDepartment, toast]);

    const fetchDepartments = async () => {
        try {
            const response = await fetch('/api/hr/departments', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });
            if (response.ok) {
                const data = await response.json();
                setDepartments(data.data);
            }
        } catch (error) {
            console.error('Failed to fetch departments:', error);
        }
    };

    const exportDashboard = async () => {
        try {
            const params = new URLSearchParams({
                time_range: timeRange,
                department: selectedDepartment
            });

            const response = await fetch(`/api/hr/analytics/export?${params}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `hr-analytics-${new Date().toISOString().split('T')[0]}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                throw new Error('Failed to export dashboard');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to export dashboard",
                variant: "destructive",
            });
        }
    };

    const formatNumber = (num) => {
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
        return num.toString();
    };

    const getMetricIcon = (metric) => {
        const icons = {
            total_employees: Users,
            attendance_rate: UserCheck,
            performance_avg: Award,
            turnover_rate: TrendingDown,
            satisfaction_score: Target,
            productivity_index: Zap
        };
        return icons[metric] || Activity;
    };

    const getMetricColor = (metric, value, target) => {
        if (metric === 'turnover_rate') {
            return value <= target ? 'text-green-600' : 'text-red-600';
        }
        return value >= target ? 'text-green-600' : 'text-red-600';
    };

    const renderCustomTooltip = ({ active, payload, label }) => {
        if (active && payload && payload.length) {
            return (
                <div className="bg-white p-3 border rounded-lg shadow-lg">
                    <p className="text-sm font-medium">{label}</p>
                    {payload.map((pld, index) => (
                        <p key={index} style={{ color: pld.color }} className="text-sm">
                            {pld.name}: {pld.value}
                        </p>
                    ))}
                </div>
            );
        }
        return null;
    };

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">HR Analytics Dashboard</h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Last updated: {lastUpdated.toLocaleTimeString()} 
                        {autoRefresh && <span className="ml-2">• Auto-refresh enabled</span>}
                    </p>
                </div>
                <div className="flex gap-2">
                    <Button
                        onClick={() => setAutoRefresh(!autoRefresh)}
                        variant={autoRefresh ? "default" : "outline"}
                        size="sm"
                    >
                        <Activity className={`h-4 w-4 mr-2 ${autoRefresh ? 'animate-pulse' : ''}`} />
                        Real-time
                    </Button>
                    <Button onClick={fetchDashboardData} variant="outline" size="sm">
                        <RefreshCw className="h-4 w-4 mr-2" />
                        Refresh
                    </Button>
                    <Button onClick={exportDashboard} variant="outline" size="sm">
                        <Download className="h-4 w-4 mr-2" />
                        Export
                    </Button>
                </div>
            </div>

            {/* Filters */}
            <Card>
                <CardContent className="p-4">
                    <div className="flex flex-wrap gap-4 items-center">
                        <div>
                            <label className="text-sm font-medium">Time Range</label>
                            <Select value={timeRange} onValueChange={setTimeRange}>
                                <SelectTrigger className="w-40">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="7_days">Last 7 Days</SelectItem>
                                    <SelectItem value="30_days">Last 30 Days</SelectItem>
                                    <SelectItem value="90_days">Last 3 Months</SelectItem>
                                    <SelectItem value="180_days">Last 6 Months</SelectItem>
                                    <SelectItem value="365_days">Last Year</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium">Department</label>
                            <Select value={selectedDepartment} onValueChange={setSelectedDepartment}>
                                <SelectTrigger className="w-48">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Departments</SelectItem>
                                    {departments.map((dept) => (
                                        <SelectItem key={dept.id} value={dept.id.toString()}>
                                            {dept.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {autoRefresh && (
                            <div>
                                <label className="text-sm font-medium">Refresh Rate</label>
                                <Select value={refreshInterval.toString()} onValueChange={(val) => setRefreshInterval(parseInt(val))}>
                                    <SelectTrigger className="w-32">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="15">15s</SelectItem>
                                        <SelectItem value="30">30s</SelectItem>
                                        <SelectItem value="60">1m</SelectItem>
                                        <SelectItem value="300">5m</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>

            {/* Alerts */}
            {dashboardData.alerts && dashboardData.alerts.length > 0 && (
                <Card className="border-yellow-200 bg-yellow-50">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-yellow-800 flex items-center">
                            <AlertCircle className="h-5 w-5 mr-2" />
                            System Alerts
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-0">
                        <div className="space-y-2">
                            {dashboardData.alerts.map((alert, index) => (
                                <div key={index} className="flex items-center justify-between p-2 bg-white rounded border">
                                    <div className="flex items-center space-x-2">
                                        <Badge variant={alert.severity === 'high' ? 'destructive' : 'secondary'}>
                                            {alert.severity}
                                        </Badge>
                                        <span className="text-sm">{alert.message}</span>
                                    </div>
                                    <span className="text-xs text-gray-500">{alert.time}</span>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* KPI Metrics */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                {Object.entries(dashboardData.kpi_metrics || {}).map(([key, metric]) => {
                    const IconComponent = getMetricIcon(key);
                    return (
                        <Card key={key}>
                            <CardContent className="p-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center space-x-2">
                                        <div className="p-2 bg-blue-100 rounded-lg">
                                            <IconComponent className="h-5 w-5 text-blue-600" />
                                        </div>
                                        <div>
                                            <div className="text-sm text-gray-500">{metric.label}</div>
                                            <div className={`text-xl font-bold ${getMetricColor(key, metric.value, metric.target)}`}>
                                                {formatNumber(metric.value)}{metric.unit}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className={`text-sm flex items-center ${metric.change >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                            {metric.change >= 0 ? <TrendingUp className="h-3 w-3 mr-1" /> : <TrendingDown className="h-3 w-3 mr-1" />}
                                            {Math.abs(metric.change)}%
                                        </div>
                                        <div className="text-xs text-gray-400">vs last period</div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            {/* Charts Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Attendance Trends */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center">
                            <Calendar className="h-5 w-5 mr-2" />
                            Attendance Trends
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={dashboardData.attendance_trends || []}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis />
                                <Tooltip content={renderCustomTooltip} />
                                <Legend />
                                <Line 
                                    type="monotone" 
                                    dataKey="present" 
                                    stroke="#0088FE" 
                                    strokeWidth={2}
                                    dot={{ fill: '#0088FE' }}
                                />
                                <Line 
                                    type="monotone" 
                                    dataKey="absent" 
                                    stroke="#FF8042" 
                                    strokeWidth={2}
                                    dot={{ fill: '#FF8042' }}
                                />
                                <Line 
                                    type="monotone" 
                                    dataKey="late" 
                                    stroke="#FFBB28" 
                                    strokeWidth={2}
                                    dot={{ fill: '#FFBB28' }}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>

                {/* Department Performance */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center">
                            <Building className="h-5 w-5 mr-2" />
                            Department Performance
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <BarChart data={dashboardData.department_performance || []}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="department" />
                                <YAxis />
                                <Tooltip content={renderCustomTooltip} />
                                <Legend />
                                <Bar dataKey="attendance_rate" fill="#0088FE" name="Attendance %" />
                                <Bar dataKey="performance_score" fill="#00C49F" name="Performance Score" />
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>

                {/* Employee Distribution */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center">
                            <Users className="h-5 w-5 mr-2" />
                            Employee Distribution
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <PieChart>
                                <Pie
                                    data={dashboardData.employee_distribution || []}
                                    cx="50%"
                                    cy="50%"
                                    labelLine={false}
                                    label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                    outerRadius={80}
                                    fill="#8884d8"
                                    dataKey="value"
                                >
                                    {(dashboardData.employee_distribution || []).map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                    ))}
                                </Pie>
                                <Tooltip />
                            </PieChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>

                {/* Performance Metrics */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center">
                            <Award className="h-5 w-5 mr-2" />
                            Performance Metrics
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <AreaChart data={dashboardData.performance_metrics || []}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="month" />
                                <YAxis />
                                <Tooltip content={renderCustomTooltip} />
                                <Legend />
                                <Area 
                                    type="monotone" 
                                    dataKey="average_score" 
                                    stackId="1"
                                    stroke="#8884d8" 
                                    fill="#8884d8" 
                                    fillOpacity={0.3}
                                />
                                <Area 
                                    type="monotone" 
                                    dataKey="improvement_rate" 
                                    stackId="2"
                                    stroke="#82ca9d" 
                                    fill="#82ca9d" 
                                    fillOpacity={0.3}
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
            </div>

            {/* Recent Activities */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center">
                        <Clock className="h-5 w-5 mr-2" />
                        Recent Activities
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {loading ? (
                        <div className="text-center py-8 text-gray-500">
                            Loading activities...
                        </div>
                    ) : (dashboardData.recent_activities || []).length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            No recent activities
                        </div>
                    ) : (
                        <div className="space-y-3 max-h-80 overflow-y-auto">
                            {(dashboardData.recent_activities || []).map((activity, index) => (
                                <div key={index} className="flex items-start space-x-3 p-3 border rounded-lg">
                                    <div className={`p-2 rounded-lg ${
                                        activity.type === 'check_in' ? 'bg-green-100' :
                                        activity.type === 'check_out' ? 'bg-blue-100' :
                                        activity.type === 'leave_request' ? 'bg-yellow-100' :
                                        activity.type === 'performance_review' ? 'bg-purple-100' :
                                        'bg-gray-100'
                                    }`}>
                                        <Activity className={`h-4 w-4 ${
                                            activity.type === 'check_in' ? 'text-green-600' :
                                            activity.type === 'check_out' ? 'text-blue-600' :
                                            activity.type === 'leave_request' ? 'text-yellow-600' :
                                            activity.type === 'performance_review' ? 'text-purple-600' :
                                            'text-gray-600'
                                        }`} />
                                    </div>
                                    <div className="flex-1">
                                        <div className="text-sm font-medium">{activity.title}</div>
                                        <div className="text-xs text-gray-500">{activity.description}</div>
                                        <div className="text-xs text-gray-400 mt-1">{activity.time}</div>
                                    </div>
                                    <Badge variant="outline" className="text-xs">
                                        {activity.type.replace('_', ' ')}
                                    </Badge>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
};

export default HRAnalyticsDashboard;