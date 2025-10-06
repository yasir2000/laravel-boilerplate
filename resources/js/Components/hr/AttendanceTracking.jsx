import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { 
    Calendar, 
    Clock, 
    Search, 
    Filter, 
    Download, 
    Upload, 
    CheckIn, 
    CheckOut, 
    UserCheck, 
    UserX, 
    CalendarDays,
    BarChart3,
    TrendingUp,
    TrendingDown,
    AlertCircle,
    MapPin,
    Timer,
    Coffee,
    Sun,
    Moon
} from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useToast } from '@/hooks/use-toast';
import { DatePicker } from '@/components/ui/date-picker';

const AttendanceTracking = () => {
    const { toast } = useToast();
    const [attendanceData, setAttendanceData] = useState([]);
    const [employees, setEmployees] = useState([]);
    const [departments, setDepartments] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedDate, setSelectedDate] = useState(new Date());
    const [selectedDepartment, setSelectedDepartment] = useState('all');
    const [selectedEmployee, setSelectedEmployee] = useState('all');
    const [viewMode, setViewMode] = useState('daily'); // daily, weekly, monthly
    
    // Statistics
    const [attendanceStats, setAttendanceStats] = useState({
        total_employees: 0,
        present: 0,
        absent: 0,
        late: 0,
        on_leave: 0,
        attendance_rate: 0
    });

    // Quick check-in/out
    const [showQuickCheckIn, setShowQuickCheckIn] = useState(false);
    const [currentLocation, setCurrentLocation] = useState(null);
    const [checkInEmployee, setCheckInEmployee] = useState('');

    // Filters
    const [filters, setFilters] = useState({
        status: 'all',
        date_range: '7_days'
    });

    useEffect(() => {
        fetchAttendanceData();
        fetchEmployees();
        fetchDepartments();
        fetchAttendanceStats();
        getCurrentLocation();
    }, [selectedDate, selectedDepartment, selectedEmployee, viewMode, filters]);

    const fetchAttendanceData = async () => {
        try {
            setLoading(true);
            const params = new URLSearchParams({
                date: selectedDate.toISOString().split('T')[0],
                department: selectedDepartment,
                employee: selectedEmployee,
                view_mode: viewMode,
                ...filters
            });

            const response = await fetch(`/api/hr/attendance?${params}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });

            if (response.ok) {
                const data = await response.json();
                setAttendanceData(data.data);
            } else {
                throw new Error('Failed to fetch attendance data');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to fetch attendance data",
                variant: "destructive",
            });
        } finally {
            setLoading(false);
        }
    };

    const fetchEmployees = async () => {
        try {
            const response = await fetch('/api/hr/employees', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });
            if (response.ok) {
                const data = await response.json();
                setEmployees(data.data);
            }
        } catch (error) {
            console.error('Failed to fetch employees:', error);
        }
    };

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

    const fetchAttendanceStats = async () => {
        try {
            const params = new URLSearchParams({
                date: selectedDate.toISOString().split('T')[0],
                department: selectedDepartment,
            });

            const response = await fetch(`/api/hr/attendance/stats?${params}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });

            if (response.ok) {
                const data = await response.json();
                setAttendanceStats(data.data);
            }
        } catch (error) {
            console.error('Failed to fetch attendance statistics:', error);
        }
    };

    const getCurrentLocation = () => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    setCurrentLocation({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    });
                },
                (error) => {
                    console.warn('Location access denied:', error);
                }
            );
        }
    };

    const handleQuickCheckIn = async (employeeId, action) => {
        try {
            const requestData = {
                employee_id: employeeId,
                action: action, // 'check_in', 'check_out', 'break_start', 'break_end'
                timestamp: new Date().toISOString(),
                location: currentLocation
            };

            const response = await fetch('/api/hr/attendance/quick-action', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            if (response.ok) {
                const data = await response.json();
                toast({
                    title: "Success",
                    description: `${action.replace('_', ' ')} recorded successfully`,
                });
                setShowQuickCheckIn(false);
                fetchAttendanceData();
                fetchAttendanceStats();
            } else {
                throw new Error('Failed to record attendance');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to record attendance",
                variant: "destructive",
            });
        }
    };

    const handleBulkAttendance = async (action) => {
        try {
            const response = await fetch('/api/hr/attendance/bulk-action', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action,
                    date: selectedDate.toISOString().split('T')[0],
                    department: selectedDepartment !== 'all' ? selectedDepartment : null
                })
            });

            if (response.ok) {
                toast({
                    title: "Success",
                    description: `Bulk ${action} completed successfully`,
                });
                fetchAttendanceData();
                fetchAttendanceStats();
            } else {
                throw new Error('Failed to perform bulk action');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to perform bulk action",
                variant: "destructive",
            });
        }
    };

    const exportAttendance = async () => {
        try {
            const params = new URLSearchParams({
                date: selectedDate.toISOString().split('T')[0],
                department: selectedDepartment,
                view_mode: viewMode,
                ...filters
            });

            const response = await fetch(`/api/hr/attendance/export?${params}`, {
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
                a.download = `attendance-${selectedDate.toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                throw new Error('Failed to export attendance');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to export attendance",
                variant: "destructive",
            });
        }
    };

    const getStatusBadge = (status) => {
        const variants = {
            present: 'default',
            absent: 'destructive',
            late: 'secondary',
            on_leave: 'outline',
            half_day: 'secondary'
        };
        
        const icons = {
            present: <UserCheck className="h-3 w-3 mr-1" />,
            absent: <UserX className="h-3 w-3 mr-1" />,
            late: <AlertCircle className="h-3 w-3 mr-1" />,
            on_leave: <Coffee className="h-3 w-3 mr-1" />,
            half_day: <Timer className="h-3 w-3 mr-1" />
        };

        return (
            <Badge variant={variants[status] || 'default'} className="text-xs">
                {icons[status]}
                {status.replace('_', ' ').toUpperCase()}
            </Badge>
        );
    };

    const formatTime = (timeString) => {
        if (!timeString) return 'N/A';
        return new Date(`2000-01-01T${timeString}`).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const calculateWorkingHours = (checkIn, checkOut) => {
        if (!checkIn || !checkOut) return 'N/A';
        
        const start = new Date(`2000-01-01T${checkIn}`);
        const end = new Date(`2000-01-01T${checkOut}`);
        const diff = (end - start) / (1000 * 60 * 60); // hours
        
        return `${diff.toFixed(1)}h`;
    };

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold tracking-tight">Attendance Tracking</h1>
                <div className="flex gap-2">
                    <Button onClick={() => setShowQuickCheckIn(true)} variant="outline">
                        <Clock className="h-4 w-4 mr-2" />
                        Quick Check-in
                    </Button>
                    <Button onClick={exportAttendance} variant="outline">
                        <Download className="h-4 w-4 mr-2" />
                        Export
                    </Button>
                </div>
            </div>

            {/* Statistics Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center space-x-2">
                            <div className="p-2 bg-blue-100 rounded-lg">
                                <UserCheck className="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <div className="text-sm text-gray-500">Present</div>
                                <div className="text-xl font-bold">{attendanceStats.present}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center space-x-2">
                            <div className="p-2 bg-red-100 rounded-lg">
                                <UserX className="h-5 w-5 text-red-600" />
                            </div>
                            <div>
                                <div className="text-sm text-gray-500">Absent</div>
                                <div className="text-xl font-bold">{attendanceStats.absent}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center space-x-2">
                            <div className="p-2 bg-yellow-100 rounded-lg">
                                <AlertCircle className="h-5 w-5 text-yellow-600" />
                            </div>
                            <div>
                                <div className="text-sm text-gray-500">Late</div>
                                <div className="text-xl font-bold">{attendanceStats.late}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center space-x-2">
                            <div className="p-2 bg-green-100 rounded-lg">
                                <Coffee className="h-5 w-5 text-green-600" />
                            </div>
                            <div>
                                <div className="text-sm text-gray-500">On Leave</div>
                                <div className="text-xl font-bold">{attendanceStats.on_leave}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center space-x-2">
                            <div className="p-2 bg-purple-100 rounded-lg">
                                <BarChart3 className="h-5 w-5 text-purple-600" />
                            </div>
                            <div>
                                <div className="text-sm text-gray-500">Total</div>
                                <div className="text-xl font-bold">{attendanceStats.total_employees}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center space-x-2">
                            <div className="p-2 bg-indigo-100 rounded-lg">
                                {attendanceStats.attendance_rate >= 90 ? 
                                    <TrendingUp className="h-5 w-5 text-indigo-600" /> :
                                    <TrendingDown className="h-5 w-5 text-indigo-600" />
                                }
                            </div>
                            <div>
                                <div className="text-sm text-gray-500">Rate</div>
                                <div className="text-xl font-bold">{attendanceStats.attendance_rate}%</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Filters and Controls */}
            <Card>
                <CardContent className="p-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label className="text-sm font-medium mb-1 block">Date</label>
                            <DatePicker
                                selected={selectedDate}
                                onSelect={setSelectedDate}
                            />
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium mb-1 block">View Mode</label>
                            <Select value={viewMode} onValueChange={setViewMode}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="daily">Daily</SelectItem>
                                    <SelectItem value="weekly">Weekly</SelectItem>
                                    <SelectItem value="monthly">Monthly</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label className="text-sm font-medium mb-1 block">Department</label>
                            <Select value={selectedDepartment} onValueChange={setSelectedDepartment}>
                                <SelectTrigger>
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

                        <div>
                            <label className="text-sm font-medium mb-1 block">Employee</label>
                            <Select value={selectedEmployee} onValueChange={setSelectedEmployee}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Employees</SelectItem>
                                    {employees.map((emp) => (
                                        <SelectItem key={emp.id} value={emp.id.toString()}>
                                            {emp.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label className="text-sm font-medium mb-1 block">Status Filter</label>
                            <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="present">Present</SelectItem>
                                    <SelectItem value="absent">Absent</SelectItem>
                                    <SelectItem value="late">Late</SelectItem>
                                    <SelectItem value="on_leave">On Leave</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="mt-4 flex gap-2">
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleBulkAttendance('mark_present')}
                        >
                            Mark All Present
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleBulkAttendance('mark_absent')}
                        >
                            Mark All Absent
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {/* Attendance Table */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        Attendance Records - {selectedDate.toLocaleDateString()}
                    </CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Employee</TableHead>
                                <TableHead>Department</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Check In</TableHead>
                                <TableHead>Check Out</TableHead>
                                <TableHead>Break Time</TableHead>
                                <TableHead>Working Hours</TableHead>
                                <TableHead>Location</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {loading ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center py-8">
                                        Loading attendance data...
                                    </TableCell>
                                </TableRow>
                            ) : attendanceData.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center py-8">
                                        No attendance records found
                                    </TableCell>
                                </TableRow>
                            ) : (
                                attendanceData.map((record) => (
                                    <TableRow key={record.id}>
                                        <TableCell>
                                            <div className="flex items-center space-x-3">
                                                <Avatar className="h-8 w-8">
                                                    <AvatarImage src={record.employee?.avatar_url} />
                                                    <AvatarFallback>
                                                        {record.employee?.name.split(' ').map(n => n[0]).join('').toUpperCase()}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <div className="font-medium">{record.employee?.name}</div>
                                                    <div className="text-sm text-gray-500">{record.employee?.employee_id}</div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>{record.employee?.department?.name || 'N/A'}</TableCell>
                                        <TableCell>{getStatusBadge(record.status)}</TableCell>
                                        <TableCell>
                                            <div className="flex items-center space-x-1">
                                                <Sun className="h-3 w-3 text-yellow-500" />
                                                <span>{formatTime(record.check_in_time)}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center space-x-1">
                                                <Moon className="h-3 w-3 text-blue-500" />
                                                <span>{formatTime(record.check_out_time)}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {record.break_time ? `${record.break_time} min` : 'N/A'}
                                        </TableCell>
                                        <TableCell>
                                            <div className="font-medium">
                                                {calculateWorkingHours(record.check_in_time, record.check_out_time)}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {record.location && (
                                                <div className="flex items-center space-x-1 text-xs text-gray-500">
                                                    <MapPin className="h-3 w-3" />
                                                    <span>GPS Verified</span>
                                                </div>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            {/* Quick Check-in Dialog */}
            <Dialog open={showQuickCheckIn} onOpenChange={setShowQuickCheckIn}>
                <DialogContent className="sm:max-w-[400px]">
                    <DialogHeader>
                        <DialogTitle>Quick Check-in/out</DialogTitle>
                        <DialogDescription>
                            Record attendance for employees quickly.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium">Select Employee</label>
                            <Select value={checkInEmployee} onValueChange={setCheckInEmployee}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Choose employee" />
                                </SelectTrigger>
                                <SelectContent>
                                    {employees.map((emp) => (
                                        <SelectItem key={emp.id} value={emp.id.toString()}>
                                            {emp.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {currentLocation && (
                            <div className="text-xs text-gray-500 flex items-center space-x-1">
                                <MapPin className="h-3 w-3" />
                                <span>Location detected (±{Math.round(currentLocation.accuracy)}m accuracy)</span>
                            </div>
                        )}

                        <div className="grid grid-cols-2 gap-2">
                            <Button
                                onClick={() => handleQuickCheckIn(checkInEmployee, 'check_in')}
                                disabled={!checkInEmployee}
                                className="w-full"
                            >
                                <CheckIn className="h-4 w-4 mr-2" />
                                Check In
                            </Button>
                            <Button
                                onClick={() => handleQuickCheckIn(checkInEmployee, 'check_out')}
                                disabled={!checkInEmployee}
                                variant="outline"
                                className="w-full"
                            >
                                <CheckOut className="h-4 w-4 mr-2" />
                                Check Out
                            </Button>
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            <Button
                                onClick={() => handleQuickCheckIn(checkInEmployee, 'break_start')}
                                disabled={!checkInEmployee}
                                variant="outline"
                                size="sm"
                                className="w-full"
                            >
                                <Coffee className="h-4 w-4 mr-2" />
                                Break Start
                            </Button>
                            <Button
                                onClick={() => handleQuickCheckIn(checkInEmployee, 'break_end')}
                                disabled={!checkInEmployee}
                                variant="outline"
                                size="sm"
                                className="w-full"
                            >
                                <Timer className="h-4 w-4 mr-2" />
                                Break End
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default AttendanceTracking;