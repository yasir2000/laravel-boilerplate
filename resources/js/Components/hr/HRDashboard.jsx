import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    Users, 
    Building, 
    Calendar, 
    BarChart3, 
    Award, 
    UserCheck,
    TrendingUp,
    AlertCircle,
    Clock,
    Target,
    ArrowRight,
    Activity
} from 'lucide-react';

// Import our HR components
import EmployeeManagement from './EmployeeManagement';
import DepartmentTreeView from './DepartmentTreeView';
import AttendanceTracking from './AttendanceTracking';
import PerformanceEvaluation from './PerformanceEvaluation';
import HRAnalyticsDashboard from './HRAnalyticsDashboard';

const HRDashboard = () => {
    const [activeTab, setActiveTab] = useState('overview');
    const [dashboardStats, setDashboardStats] = useState({
        total_employees: 0,
        active_employees: 0,
        departments: 0,
        attendance_rate: 0,
        pending_evaluations: 0,
        recent_activities: []
    });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchDashboardStats();
    }, []);

    const fetchDashboardStats = async () => {
        try {
            setLoading(true);
            const response = await fetch('/api/hr/dashboard/stats', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });

            if (response.ok) {
                const data = await response.json();
                setDashboardStats(data.data);
            }
        } catch (error) {
            console.error('Failed to fetch dashboard stats:', error);
        } finally {
            setLoading(false);
        }
    };

    const QuickActionCard = ({ title, description, icon: Icon, onClick, color = "blue" }) => (
        <Card className={`hover:shadow-md transition-shadow cursor-pointer border-l-4 border-l-${color}-500`} onClick={onClick}>
            <CardContent className="p-4">
                <div className="flex items-center space-x-3">
                    <div className={`p-2 bg-${color}-100 rounded-lg`}>
                        <Icon className={`h-5 w-5 text-${color}-600`} />
                    </div>
                    <div className="flex-1">
                        <h3 className="font-semibold text-sm">{title}</h3>
                        <p className="text-xs text-gray-500">{description}</p>
                    </div>
                    <ArrowRight className="h-4 w-4 text-gray-400" />
                </div>
            </CardContent>
        </Card>
    );

    const StatCard = ({ title, value, subtitle, icon: Icon, color = "blue", trend }) => (
        <Card>
            <CardContent className="p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-gray-600">{title}</p>
                        <div className="flex items-center space-x-2">
                            <p className="text-2xl font-bold">{value}</p>
                            {trend && (
                                <Badge variant={trend > 0 ? "default" : "destructive"} className="text-xs">
                                    <TrendingUp className="h-3 w-3 mr-1" />
                                    {Math.abs(trend)}%
                                </Badge>
                            )}
                        </div>
                        {subtitle && <p className="text-xs text-gray-500">{subtitle}</p>}
                    </div>
                    <div className={`p-3 bg-${color}-100 rounded-lg`}>
                        <Icon className={`h-6 w-6 text-${color}-600`} />
                    </div>
                </div>
            </CardContent>
        </Card>
    );

    const OverviewContent = () => (
        <div className="space-y-6">
            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    title="Total Employees"
                    value={dashboardStats.total_employees}
                    subtitle={`${dashboardStats.active_employees} active`}
                    icon={Users}
                    color="blue"
                    trend={5.2}
                />
                <StatCard
                    title="Departments"
                    value={dashboardStats.departments}
                    subtitle="Active departments"
                    icon={Building}
                    color="green"
                />
                <StatCard
                    title="Attendance Rate"
                    value={`${dashboardStats.attendance_rate}%`}
                    subtitle="This month"
                    icon={UserCheck}
                    color="purple"
                    trend={2.1}
                />
                <StatCard
                    title="Pending Evaluations"
                    value={dashboardStats.pending_evaluations}
                    subtitle="Due this month"
                    icon={Award}
                    color="yellow"
                />
            </div>

            {/* Quick Actions */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center">
                        <Activity className="h-5 w-5 mr-2" />
                        Quick Actions
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <QuickActionCard
                            title="Add Employee"
                            description="Register a new employee"
                            icon={Users}
                            onClick={() => setActiveTab('employees')}
                            color="blue"
                        />
                        <QuickActionCard
                            title="Track Attendance"
                            description="View today's attendance"
                            icon={Clock}
                            onClick={() => setActiveTab('attendance')}
                            color="green"
                        />
                        <QuickActionCard
                            title="Create Evaluation"
                            description="Start performance review"
                            icon={Target}
                            onClick={() => setActiveTab('evaluations')}
                            color="purple"
                        />
                        <QuickActionCard
                            title="View Analytics"
                            description="Check HR metrics"
                            icon={BarChart3}
                            onClick={() => setActiveTab('analytics')}
                            color="indigo"
                        />
                        <QuickActionCard
                            title="Manage Departments"
                            description="Organize company structure"
                            icon={Building}
                            onClick={() => setActiveTab('departments')}
                            color="teal"
                        />
                        <QuickActionCard
                            title="System Alerts"
                            description="View important notifications"
                            icon={AlertCircle}
                            onClick={() => setActiveTab('analytics')}
                            color="red"
                        />
                    </div>
                </CardContent>
            </Card>

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
                    ) : dashboardStats.recent_activities?.length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            No recent activities
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {(dashboardStats.recent_activities || []).slice(0, 5).map((activity, index) => (
                                <div key={index} className="flex items-center space-x-3 p-3 border rounded-lg">
                                    <div className={`p-2 rounded-lg ${
                                        activity.type === 'employee_added' ? 'bg-blue-100' :
                                        activity.type === 'attendance_marked' ? 'bg-green-100' :
                                        activity.type === 'evaluation_completed' ? 'bg-purple-100' :
                                        'bg-gray-100'
                                    }`}>
                                        <Activity className={`h-4 w-4 ${
                                            activity.type === 'employee_added' ? 'text-blue-600' :
                                            activity.type === 'attendance_marked' ? 'text-green-600' :
                                            activity.type === 'evaluation_completed' ? 'text-purple-600' :
                                            'text-gray-600'
                                        }`} />
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">{activity.title}</p>
                                        <p className="text-xs text-gray-500">{activity.description}</p>
                                    </div>
                                    <span className="text-xs text-gray-400">{activity.time}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );

    return (
        <div className="min-h-screen bg-gray-50">
            <div className="max-w-7xl mx-auto p-6">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">HR Management System</h1>
                    <p className="text-gray-600 mt-2">Comprehensive human resources management platform</p>
                </div>

                {/* Main Content */}
                <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
                    <TabsList className="grid grid-cols-6 w-full max-w-4xl">
                        <TabsTrigger value="overview" className="flex items-center space-x-2">
                            <BarChart3 className="h-4 w-4" />
                            <span>Overview</span>
                        </TabsTrigger>
                        <TabsTrigger value="employees" className="flex items-center space-x-2">
                            <Users className="h-4 w-4" />
                            <span>Employees</span>
                        </TabsTrigger>
                        <TabsTrigger value="departments" className="flex items-center space-x-2">
                            <Building className="h-4 w-4" />
                            <span>Departments</span>
                        </TabsTrigger>
                        <TabsTrigger value="attendance" className="flex items-center space-x-2">
                            <Calendar className="h-4 w-4" />
                            <span>Attendance</span>
                        </TabsTrigger>
                        <TabsTrigger value="evaluations" className="flex items-center space-x-2">
                            <Award className="h-4 w-4" />
                            <span>Evaluations</span>
                        </TabsTrigger>
                        <TabsTrigger value="analytics" className="flex items-center space-x-2">
                            <TrendingUp className="h-4 w-4" />
                            <span>Analytics</span>
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <OverviewContent />
                    </TabsContent>

                    <TabsContent value="employees">
                        <EmployeeManagement />
                    </TabsContent>

                    <TabsContent value="departments">
                        <DepartmentTreeView />
                    </TabsContent>

                    <TabsContent value="attendance">
                        <AttendanceTracking />
                    </TabsContent>

                    <TabsContent value="evaluations">
                        <PerformanceEvaluation />
                    </TabsContent>

                    <TabsContent value="analytics">
                        <HRAnalyticsDashboard />
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    );
};

export default HRDashboard;