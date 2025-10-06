import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { 
    Search, 
    Plus, 
    Filter, 
    Download, 
    Upload, 
    Eye, 
    Edit, 
    Trash2, 
    Mail, 
    Phone,
    MapPin,
    Calendar,
    Building,
    User,
    ChevronLeft,
    ChevronRight
} from 'lucide-react';
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
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Checkbox } from '@/components/ui/checkbox';
import { useToast } from '@/hooks/use-toast';

const EmployeeManagement = () => {
    const { toast } = useToast();
    const [employees, setEmployees] = useState([]);
    const [departments, setDepartments] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedDepartment, setSelectedDepartment] = useState('all');
    const [selectedEmployees, setSelectedEmployees] = useState([]);
    const [showFilters, setShowFilters] = useState(false);
    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
    });

    // Filters
    const [filters, setFilters] = useState({
        status: 'all',
        employment_type: 'all',
        date_range: 'all',
        salary_range: 'all'
    });

    // Employee form state
    const [showEmployeeForm, setShowEmployeeForm] = useState(false);
    const [editingEmployee, setEditingEmployee] = useState(null);
    const [employeeForm, setEmployeeForm] = useState({
        name: '',
        email: '',
        phone: '',
        department_id: '',
        position: '',
        hire_date: '',
        salary: '',
        employment_type: 'full-time',
        status: 'active',
        address: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        skills: [],
        notes: ''
    });

    useEffect(() => {
        fetchEmployees();
        fetchDepartments();
    }, [pagination.current_page, searchTerm, selectedDepartment, filters]);

    const fetchEmployees = async () => {
        try {
            setLoading(true);
            const params = new URLSearchParams({
                page: pagination.current_page,
                per_page: pagination.per_page,
                search: searchTerm,
                department: selectedDepartment,
                ...filters
            });

            const response = await fetch(`/api/hr/employees?${params}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json',
                }
            });

            if (response.ok) {
                const data = await response.json();
                setEmployees(data.data);
                setPagination(data.meta);
            } else {
                throw new Error('Failed to fetch employees');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to fetch employees",
                variant: "destructive",
            });
        } finally {
            setLoading(false);
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

    const handleEmployeeSubmit = async (e) => {
        e.preventDefault();
        try {
            const url = editingEmployee 
                ? `/api/hr/employees/${editingEmployee.id}` 
                : '/api/hr/employees';
            
            const method = editingEmployee ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method,
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(employeeForm)
            });

            if (response.ok) {
                toast({
                    title: "Success",
                    description: `Employee ${editingEmployee ? 'updated' : 'created'} successfully`,
                });
                setShowEmployeeForm(false);
                setEditingEmployee(null);
                resetEmployeeForm();
                fetchEmployees();
            } else {
                throw new Error('Failed to save employee');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to save employee",
                variant: "destructive",
            });
        }
    };

    const handleDeleteEmployee = async (employeeId) => {
        if (!confirm('Are you sure you want to delete this employee?')) return;

        try {
            const response = await fetch(`/api/hr/employees/${employeeId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });

            if (response.ok) {
                toast({
                    title: "Success",
                    description: "Employee deleted successfully",
                });
                fetchEmployees();
            } else {
                throw new Error('Failed to delete employee');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to delete employee",
                variant: "destructive",
            });
        }
    };

    const handleBulkAction = async (action) => {
        if (selectedEmployees.length === 0) {
            toast({
                title: "Error",
                description: "Please select employees first",
                variant: "destructive",
            });
            return;
        }

        try {
            const response = await fetch('/api/hr/employees/bulk-action', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action,
                    employee_ids: selectedEmployees
                })
            });

            if (response.ok) {
                toast({
                    title: "Success",
                    description: `Bulk action ${action} completed successfully`,
                });
                setSelectedEmployees([]);
                fetchEmployees();
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

    const handleExport = async () => {
        try {
            const response = await fetch('/api/hr/employees/export', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    filters: { ...filters, search: searchTerm, department: selectedDepartment }
                })
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `employees-${new Date().toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                throw new Error('Failed to export employees');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to export employees",
                variant: "destructive",
            });
        }
    };

    const resetEmployeeForm = () => {
        setEmployeeForm({
            name: '',
            email: '',
            phone: '',
            department_id: '',
            position: '',
            hire_date: '',
            salary: '',
            employment_type: 'full-time',
            status: 'active',
            address: '',
            emergency_contact_name: '',
            emergency_contact_phone: '',
            skills: [],
            notes: ''
        });
    };

    const openEditEmployee = (employee) => {
        setEditingEmployee(employee);
        setEmployeeForm({
            name: employee.name || '',
            email: employee.email || '',
            phone: employee.phone || '',
            department_id: employee.department_id || '',
            position: employee.position || '',
            hire_date: employee.hire_date || '',
            salary: employee.salary || '',
            employment_type: employee.employment_type || 'full-time',
            status: employee.status || 'active',
            address: employee.address || '',
            emergency_contact_name: employee.emergency_contact_name || '',
            emergency_contact_phone: employee.emergency_contact_phone || '',
            skills: employee.skills || [],
            notes: employee.notes || ''
        });
        setShowEmployeeForm(true);
    };

    const getStatusBadge = (status) => {
        const variants = {
            active: 'default',
            inactive: 'secondary',
            terminated: 'destructive',
            on_leave: 'outline'
        };
        return (
            <Badge variant={variants[status] || 'default'}>
                {status.replace('_', ' ').toUpperCase()}
            </Badge>
        );
    };

    const getEmploymentTypeBadge = (type) => {
        const variants = {
            'full-time': 'default',
            'part-time': 'secondary',
            'contract': 'outline',
            'intern': 'outline'
        };
        return (
            <Badge variant={variants[type] || 'default'}>
                {type.replace('-', ' ').toUpperCase()}
            </Badge>
        );
    };

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold tracking-tight">Employee Management</h1>
                <div className="flex gap-2">
                    <Button onClick={handleExport} variant="outline">
                        <Download className="h-4 w-4 mr-2" />
                        Export
                    </Button>
                    <Button onClick={() => setShowEmployeeForm(true)}>
                        <Plus className="h-4 w-4 mr-2" />
                        Add Employee
                    </Button>
                </div>
            </div>

            {/* Search and Filters */}
            <Card>
                <CardContent className="p-6">
                    <div className="flex flex-col sm:flex-row gap-4">
                        <div className="flex-1">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <Input
                                    placeholder="Search employees..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <Select value={selectedDepartment} onValueChange={setSelectedDepartment}>
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Select Department" />
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
                        <Button
                            variant="outline"
                            onClick={() => setShowFilters(!showFilters)}
                        >
                            <Filter className="h-4 w-4 mr-2" />
                            Filters
                        </Button>
                    </div>

                    {/* Advanced Filters */}
                    {showFilters && (
                        <div className="mt-4 pt-4 border-t grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                    <SelectItem value="terminated">Terminated</SelectItem>
                                    <SelectItem value="on_leave">On Leave</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select value={filters.employment_type} onValueChange={(value) => setFilters({...filters, employment_type: value})}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Employment Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    <SelectItem value="full-time">Full Time</SelectItem>
                                    <SelectItem value="part-time">Part Time</SelectItem>
                                    <SelectItem value="contract">Contract</SelectItem>
                                    <SelectItem value="intern">Intern</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select value={filters.date_range} onValueChange={(value) => setFilters({...filters, date_range: value})}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Hire Date" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Time</SelectItem>
                                    <SelectItem value="last_month">Last Month</SelectItem>
                                    <SelectItem value="last_3_months">Last 3 Months</SelectItem>
                                    <SelectItem value="last_6_months">Last 6 Months</SelectItem>
                                    <SelectItem value="last_year">Last Year</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select value={filters.salary_range} onValueChange={(value) => setFilters({...filters, salary_range: value})}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Salary Range" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Ranges</SelectItem>
                                    <SelectItem value="0-30000">$0 - $30,000</SelectItem>
                                    <SelectItem value="30000-50000">$30,000 - $50,000</SelectItem>
                                    <SelectItem value="50000-75000">$50,000 - $75,000</SelectItem>
                                    <SelectItem value="75000+">$75,000+</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Bulk Actions */}
            {selectedEmployees.length > 0 && (
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-muted-foreground">
                                {selectedEmployees.length} employee(s) selected
                            </span>
                            <div className="flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => handleBulkAction('activate')}
                                >
                                    Activate
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => handleBulkAction('deactivate')}
                                >
                                    Deactivate
                                </Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    onClick={() => handleBulkAction('delete')}
                                >
                                    Delete
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Employee Table */}
            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">
                                    <Checkbox
                                        checked={selectedEmployees.length === employees.length && employees.length > 0}
                                        onCheckedChange={(checked) => {
                                            if (checked) {
                                                setSelectedEmployees(employees.map(emp => emp.id));
                                            } else {
                                                setSelectedEmployees([]);
                                            }
                                        }}
                                    />
                                </TableHead>
                                <TableHead>Employee</TableHead>
                                <TableHead>Department</TableHead>
                                <TableHead>Position</TableHead>
                                <TableHead>Employment Type</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Hire Date</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {loading ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center py-8">
                                        Loading employees...
                                    </TableCell>
                                </TableRow>
                            ) : employees.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center py-8">
                                        No employees found
                                    </TableCell>
                                </TableRow>
                            ) : (
                                employees.map((employee) => (
                                    <TableRow key={employee.id}>
                                        <TableCell>
                                            <Checkbox
                                                checked={selectedEmployees.includes(employee.id)}
                                                onCheckedChange={(checked) => {
                                                    if (checked) {
                                                        setSelectedEmployees([...selectedEmployees, employee.id]);
                                                    } else {
                                                        setSelectedEmployees(selectedEmployees.filter(id => id !== employee.id));
                                                    }
                                                }}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center space-x-3">
                                                <Avatar className="h-8 w-8">
                                                    <AvatarImage src={employee.avatar_url} />
                                                    <AvatarFallback>
                                                        {employee.name.split(' ').map(n => n[0]).join('').toUpperCase()}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <div className="font-medium">{employee.name}</div>
                                                    <div className="text-sm text-muted-foreground">{employee.email}</div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center">
                                                <Building className="h-4 w-4 mr-2 text-muted-foreground" />
                                                {employee.department?.name || 'Unassigned'}
                                            </div>
                                        </TableCell>
                                        <TableCell>{employee.position || 'N/A'}</TableCell>
                                        <TableCell>{getEmploymentTypeBadge(employee.employment_type)}</TableCell>
                                        <TableCell>{getStatusBadge(employee.status)}</TableCell>
                                        <TableCell>
                                            {employee.hire_date ? new Date(employee.hire_date).toLocaleDateString() : 'N/A'}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center space-x-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => openEditEmployee(employee)}
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => handleDeleteEmployee(employee.id)}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            {/* Pagination */}
            {pagination.last_page > 1 && (
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between">
                            <div className="text-sm text-muted-foreground">
                                Showing {((pagination.current_page - 1) * pagination.per_page) + 1} to{' '}
                                {Math.min(pagination.current_page * pagination.per_page, pagination.total)} of{' '}
                                {pagination.total} employees
                            </div>
                            <div className="flex items-center space-x-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setPagination({...pagination, current_page: pagination.current_page - 1})}
                                    disabled={pagination.current_page === 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    Previous
                                </Button>
                                <div className="text-sm">
                                    Page {pagination.current_page} of {pagination.last_page}
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setPagination({...pagination, current_page: pagination.current_page + 1})}
                                    disabled={pagination.current_page === pagination.last_page}
                                >
                                    Next
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Employee Form Dialog */}
            <Dialog open={showEmployeeForm} onOpenChange={setShowEmployeeForm}>
                <DialogContent className="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {editingEmployee ? 'Edit Employee' : 'Add New Employee'}
                        </DialogTitle>
                        <DialogDescription>
                            Fill in the employee information below.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <form onSubmit={handleEmployeeSubmit} className="space-y-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="text-sm font-medium">Full Name</label>
                                <Input
                                    value={employeeForm.name}
                                    onChange={(e) => setEmployeeForm({...employeeForm, name: e.target.value})}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Email</label>
                                <Input
                                    type="email"
                                    value={employeeForm.email}
                                    onChange={(e) => setEmployeeForm({...employeeForm, email: e.target.value})}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Phone</label>
                                <Input
                                    value={employeeForm.phone}
                                    onChange={(e) => setEmployeeForm({...employeeForm, phone: e.target.value})}
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Department</label>
                                <Select 
                                    value={employeeForm.department_id} 
                                    onValueChange={(value) => setEmployeeForm({...employeeForm, department_id: value})}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Department" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {departments.map((dept) => (
                                            <SelectItem key={dept.id} value={dept.id.toString()}>
                                                {dept.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="text-sm font-medium">Position</label>
                                <Input
                                    value={employeeForm.position}
                                    onChange={(e) => setEmployeeForm({...employeeForm, position: e.target.value})}
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Hire Date</label>
                                <Input
                                    type="date"
                                    value={employeeForm.hire_date}
                                    onChange={(e) => setEmployeeForm({...employeeForm, hire_date: e.target.value})}
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Salary</label>
                                <Input
                                    type="number"
                                    value={employeeForm.salary}
                                    onChange={(e) => setEmployeeForm({...employeeForm, salary: e.target.value})}
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Employment Type</label>
                                <Select 
                                    value={employeeForm.employment_type} 
                                    onValueChange={(value) => setEmployeeForm({...employeeForm, employment_type: value})}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="full-time">Full Time</SelectItem>
                                        <SelectItem value="part-time">Part Time</SelectItem>
                                        <SelectItem value="contract">Contract</SelectItem>
                                        <SelectItem value="intern">Intern</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="text-sm font-medium">Status</label>
                                <Select 
                                    value={employeeForm.status} 
                                    onValueChange={(value) => setEmployeeForm({...employeeForm, status: value})}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                        <SelectItem value="on_leave">On Leave</SelectItem>
                                        <SelectItem value="terminated">Terminated</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div>
                            <label className="text-sm font-medium">Address</label>
                            <Input
                                value={employeeForm.address}
                                onChange={(e) => setEmployeeForm({...employeeForm, address: e.target.value})}
                            />
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="text-sm font-medium">Emergency Contact Name</label>
                                <Input
                                    value={employeeForm.emergency_contact_name}
                                    onChange={(e) => setEmployeeForm({...employeeForm, emergency_contact_name: e.target.value})}
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Emergency Contact Phone</label>
                                <Input
                                    value={employeeForm.emergency_contact_phone}
                                    onChange={(e) => setEmployeeForm({...employeeForm, emergency_contact_phone: e.target.value})}
                                />
                            </div>
                        </div>

                        <div>
                            <label className="text-sm font-medium">Notes</label>
                            <textarea
                                className="w-full px-3 py-2 text-sm border border-input rounded-md"
                                rows={3}
                                value={employeeForm.notes}
                                onChange={(e) => setEmployeeForm({...employeeForm, notes: e.target.value})}
                                placeholder="Additional notes about the employee..."
                            />
                        </div>

                        <div className="flex justify-end space-x-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setShowEmployeeForm(false);
                                    setEditingEmployee(null);
                                    resetEmployeeForm();
                                }}
                            >
                                Cancel
                            </Button>
                            <Button type="submit">
                                {editingEmployee ? 'Update Employee' : 'Create Employee'}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default EmployeeManagement;