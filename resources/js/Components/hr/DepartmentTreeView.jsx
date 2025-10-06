import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { 
    Plus, 
    Edit, 
    Trash2, 
    ChevronDown, 
    ChevronRight, 
    Building, 
    Users, 
    Search,
    MoreHorizontal,
    User,
    Target,
    Calendar,
    DollarSign
} from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useToast } from '@/hooks/use-toast';

const DepartmentTreeView = () => {
    const { toast } = useToast();
    const [departments, setDepartments] = useState([]);
    const [expandedNodes, setExpandedNodes] = useState(new Set());
    const [selectedDepartment, setSelectedDepartment] = useState(null);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [showDepartmentForm, setShowDepartmentForm] = useState(false);
    const [editingDepartment, setEditingDepartment] = useState(null);

    // Department form state
    const [departmentForm, setDepartmentForm] = useState({
        name: '',
        description: '',
        parent_id: null,
        manager_id: '',
        budget: '',
        location: '',
        is_active: true
    });

    // Department employees
    const [departmentEmployees, setDepartmentEmployees] = useState([]);
    const [loadingEmployees, setLoadingEmployees] = useState(false);

    useEffect(() => {
        fetchDepartments();
    }, []);

    useEffect(() => {
        if (selectedDepartment) {
            fetchDepartmentEmployees(selectedDepartment.id);
        }
    }, [selectedDepartment]);

    const fetchDepartments = async () => {
        try {
            setLoading(true);
            const response = await fetch('/api/hr/departments/tree', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });

            if (response.ok) {
                const data = await response.json();
                setDepartments(data.data);
                // Auto-expand root nodes
                const rootNodes = data.data.filter(dept => !dept.parent_id);
                setExpandedNodes(new Set(rootNodes.map(dept => dept.id)));
            } else {
                throw new Error('Failed to fetch departments');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to fetch departments",
                variant: "destructive",
            });
        } finally {
            setLoading(false);
        }
    };

    const fetchDepartmentEmployees = async (departmentId) => {
        try {
            setLoadingEmployees(true);
            const response = await fetch(`/api/hr/departments/${departmentId}/employees`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });

            if (response.ok) {
                const data = await response.json();
                setDepartmentEmployees(data.data);
            } else {
                throw new Error('Failed to fetch department employees');
            }
        } catch (error) {
            console.error('Failed to fetch department employees:', error);
            setDepartmentEmployees([]);
        } finally {
            setLoadingEmployees(false);
        }
    };

    const buildDepartmentTree = (departments, parentId = null) => {
        return departments
            .filter(dept => dept.parent_id === parentId)
            .map(dept => ({
                ...dept,
                children: buildDepartmentTree(departments, dept.id)
            }));
    };

    const filterDepartments = (departments, searchTerm) => {
        if (!searchTerm) return departments;
        
        return departments.filter(dept => 
            dept.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            dept.description?.toLowerCase().includes(searchTerm.toLowerCase()) ||
            dept.children?.some(child => filterDepartments([child], searchTerm).length > 0)
        );
    };

    const toggleNode = (nodeId) => {
        const newExpanded = new Set(expandedNodes);
        if (newExpanded.has(nodeId)) {
            newExpanded.delete(nodeId);
        } else {
            newExpanded.add(nodeId);
        }
        setExpandedNodes(newExpanded);
    };

    const handleDepartmentSubmit = async (e) => {
        e.preventDefault();
        try {
            const url = editingDepartment 
                ? `/api/hr/departments/${editingDepartment.id}` 
                : '/api/hr/departments';
            
            const method = editingDepartment ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method,
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...departmentForm,
                    parent_id: departmentForm.parent_id || null
                })
            });

            if (response.ok) {
                toast({
                    title: "Success",
                    description: `Department ${editingDepartment ? 'updated' : 'created'} successfully`,
                });
                setShowDepartmentForm(false);
                setEditingDepartment(null);
                resetDepartmentForm();
                fetchDepartments();
            } else {
                throw new Error('Failed to save department');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to save department",
                variant: "destructive",
            });
        }
    };

    const handleDeleteDepartment = async (departmentId) => {
        if (!confirm('Are you sure you want to delete this department? This action cannot be undone.')) return;

        try {
            const response = await fetch(`/api/hr/departments/${departmentId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });

            if (response.ok) {
                toast({
                    title: "Success",
                    description: "Department deleted successfully",
                });
                if (selectedDepartment?.id === departmentId) {
                    setSelectedDepartment(null);
                }
                fetchDepartments();
            } else {
                throw new Error('Failed to delete department');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to delete department",
                variant: "destructive",
            });
        }
    };

    const resetDepartmentForm = () => {
        setDepartmentForm({
            name: '',
            description: '',
            parent_id: null,
            manager_id: '',
            budget: '',
            location: '',
            is_active: true
        });
    };

    const openEditDepartment = (department) => {
        setEditingDepartment(department);
        setDepartmentForm({
            name: department.name || '',
            description: department.description || '',
            parent_id: department.parent_id || null,
            manager_id: department.manager_id || '',
            budget: department.budget || '',
            location: department.location || '',
            is_active: department.is_active ?? true
        });
        setShowDepartmentForm(true);
    };

    const DepartmentNode = ({ department, level = 0 }) => {
        const hasChildren = department.children && department.children.length > 0;
        const isExpanded = expandedNodes.has(department.id);
        const isSelected = selectedDepartment?.id === department.id;

        return (
            <div className="w-full">
                <div
                    className={`flex items-center p-2 hover:bg-gray-50 cursor-pointer transition-colors ${
                        isSelected ? 'bg-blue-50 border-l-4 border-blue-500' : ''
                    }`}
                    style={{ paddingLeft: `${level * 20 + 8}px` }}
                    onClick={() => setSelectedDepartment(department)}
                >
                    <div className="flex items-center flex-1">
                        {hasChildren ? (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="p-0 h-6 w-6 mr-2"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    toggleNode(department.id);
                                }}
                            >
                                {isExpanded ? (
                                    <ChevronDown className="h-4 w-4" />
                                ) : (
                                    <ChevronRight className="h-4 w-4" />
                                )}
                            </Button>
                        ) : (
                            <div className="w-6 mr-2" />
                        )}
                        
                        <Building className="h-4 w-4 mr-2 text-gray-500" />
                        
                        <div className="flex-1">
                            <div className="font-medium text-sm">{department.name}</div>
                            {department.description && (
                                <div className="text-xs text-gray-500 truncate max-w-48">
                                    {department.description}
                                </div>
                            )}
                        </div>
                        
                        <div className="flex items-center space-x-2">
                            <Badge variant="outline" className="text-xs">
                                <Users className="h-3 w-3 mr-1" />
                                {department.employees_count || 0}
                            </Badge>
                            
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="p-0 h-6 w-6"
                                        onClick={(e) => e.stopPropagation()}
                                    >
                                        <MoreHorizontal className="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem onClick={() => openEditDepartment(department)}>
                                        <Edit className="h-4 w-4 mr-2" />
                                        Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem 
                                        onClick={() => {
                                            setDepartmentForm({
                                                ...departmentForm,
                                                parent_id: department.id
                                            });
                                            setShowDepartmentForm(true);
                                        }}
                                    >
                                        <Plus className="h-4 w-4 mr-2" />
                                        Add Subdepartment
                                    </DropdownMenuItem>
                                    <DropdownMenuItem 
                                        onClick={() => handleDeleteDepartment(department.id)}
                                        className="text-red-600"
                                    >
                                        <Trash2 className="h-4 w-4 mr-2" />
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>
                
                {hasChildren && isExpanded && (
                    <div>
                        {department.children.map((child) => (
                            <DepartmentNode
                                key={child.id}
                                department={child}
                                level={level + 1}
                            />
                        ))}
                    </div>
                )}
            </div>
        );
    };

    const departmentTree = buildDepartmentTree(departments);
    const filteredTree = filterDepartments(departmentTree, searchTerm);

    return (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[calc(100vh-12rem)]">
            {/* Department Tree */}
            <div className="lg:col-span-1">
                <Card className="h-full">
                    <CardHeader>
                        <div className="flex justify-between items-center">
                            <CardTitle className="text-lg">Department Structure</CardTitle>
                            <Button
                                size="sm"
                                onClick={() => setShowDepartmentForm(true)}
                            >
                                <Plus className="h-4 w-4 mr-2" />
                                Add Department
                            </Button>
                        </div>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                            <Input
                                placeholder="Search departments..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                    </CardHeader>
                    <CardContent className="p-0 overflow-y-auto flex-1">
                        {loading ? (
                            <div className="p-4 text-center text-gray-500">
                                Loading departments...
                            </div>
                        ) : filteredTree.length === 0 ? (
                            <div className="p-4 text-center text-gray-500">
                                No departments found
                            </div>
                        ) : (
                            <div>
                                {filteredTree.map((department) => (
                                    <DepartmentNode
                                        key={department.id}
                                        department={department}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Department Details */}
            <div className="lg:col-span-2">
                {selectedDepartment ? (
                    <div className="space-y-6">
                        {/* Department Info */}
                        <Card>
                            <CardHeader>
                                <div className="flex justify-between items-start">
                                    <div>
                                        <CardTitle className="text-2xl">{selectedDepartment.name}</CardTitle>
                                        {selectedDepartment.description && (
                                            <p className="text-gray-600 mt-1">{selectedDepartment.description}</p>
                                        )}
                                    </div>
                                    <Badge variant={selectedDepartment.is_active ? 'default' : 'secondary'}>
                                        {selectedDepartment.is_active ? 'Active' : 'Inactive'}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div className="flex items-center space-x-3">
                                        <div className="p-2 bg-blue-100 rounded-lg">
                                            <Users className="h-5 w-5 text-blue-600" />
                                        </div>
                                        <div>
                                            <div className="text-sm text-gray-500">Employees</div>
                                            <div className="font-semibold">{selectedDepartment.employees_count || 0}</div>
                                        </div>
                                    </div>
                                    
                                    {selectedDepartment.manager && (
                                        <div className="flex items-center space-x-3">
                                            <div className="p-2 bg-green-100 rounded-lg">
                                                <User className="h-5 w-5 text-green-600" />
                                            </div>
                                            <div>
                                                <div className="text-sm text-gray-500">Manager</div>
                                                <div className="font-semibold text-sm">{selectedDepartment.manager.name}</div>
                                            </div>
                                        </div>
                                    )}
                                    
                                    {selectedDepartment.budget && (
                                        <div className="flex items-center space-x-3">
                                            <div className="p-2 bg-yellow-100 rounded-lg">
                                                <DollarSign className="h-5 w-5 text-yellow-600" />
                                            </div>
                                            <div>
                                                <div className="text-sm text-gray-500">Budget</div>
                                                <div className="font-semibold">${parseInt(selectedDepartment.budget).toLocaleString()}</div>
                                            </div>
                                        </div>
                                    )}
                                    
                                    {selectedDepartment.location && (
                                        <div className="flex items-center space-x-3">
                                            <div className="p-2 bg-purple-100 rounded-lg">
                                                <Building className="h-5 w-5 text-purple-600" />
                                            </div>
                                            <div>
                                                <div className="text-sm text-gray-500">Location</div>
                                                <div className="font-semibold text-sm">{selectedDepartment.location}</div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Department Employees */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Department Employees</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {loadingEmployees ? (
                                    <div className="text-center py-8 text-gray-500">
                                        Loading employees...
                                    </div>
                                ) : departmentEmployees.length === 0 ? (
                                    <div className="text-center py-8 text-gray-500">
                                        No employees in this department
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        {departmentEmployees.map((employee) => (
                                            <div key={employee.id} className="flex items-center justify-between p-3 border rounded-lg">
                                                <div className="flex items-center space-x-3">
                                                    <Avatar className="h-10 w-10">
                                                        <AvatarImage src={employee.avatar_url} />
                                                        <AvatarFallback>
                                                            {employee.name.split(' ').map(n => n[0]).join('').toUpperCase()}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div>
                                                        <div className="font-medium">{employee.name}</div>
                                                        <div className="text-sm text-gray-500">{employee.position || 'No position'}</div>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <div className="text-sm text-gray-500">{employee.email}</div>
                                                    {employee.hire_date && (
                                                        <div className="text-xs text-gray-400">
                                                            Joined {new Date(employee.hire_date).toLocaleDateString()}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                ) : (
                    <Card className="h-full">
                        <CardContent className="flex items-center justify-center h-full">
                            <div className="text-center text-gray-500">
                                <Building className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                                <h3 className="text-lg font-medium mb-2">No Department Selected</h3>
                                <p>Select a department from the tree to view its details and employees.</p>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Department Form Dialog */}
            <Dialog open={showDepartmentForm} onOpenChange={setShowDepartmentForm}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>
                            {editingDepartment ? 'Edit Department' : 'Add New Department'}
                        </DialogTitle>
                        <DialogDescription>
                            Fill in the department information below.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <form onSubmit={handleDepartmentSubmit} className="space-y-4">
                        <div>
                            <label className="text-sm font-medium">Department Name</label>
                            <Input
                                value={departmentForm.name}
                                onChange={(e) => setDepartmentForm({...departmentForm, name: e.target.value})}
                                required
                            />
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium">Description</label>
                            <textarea
                                className="w-full px-3 py-2 text-sm border border-input rounded-md"
                                rows={3}
                                value={departmentForm.description}
                                onChange={(e) => setDepartmentForm({...departmentForm, description: e.target.value})}
                                placeholder="Department description..."
                            />
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium">Parent Department</label>
                            <Select 
                                value={departmentForm.parent_id?.toString() || ''} 
                                onValueChange={(value) => setDepartmentForm({...departmentForm, parent_id: value || null})}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select parent department (optional)" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">No Parent (Root Department)</SelectItem>
                                    {departments.map((dept) => (
                                        <SelectItem key={dept.id} value={dept.id.toString()}>
                                            {dept.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="text-sm font-medium">Budget</label>
                                <Input
                                    type="number"
                                    value={departmentForm.budget}
                                    onChange={(e) => setDepartmentForm({...departmentForm, budget: e.target.value})}
                                    placeholder="Annual budget"
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Location</label>
                                <Input
                                    value={departmentForm.location}
                                    onChange={(e) => setDepartmentForm({...departmentForm, location: e.target.value})}
                                    placeholder="Department location"
                                />
                            </div>
                        </div>

                        <div className="flex justify-end space-x-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setShowDepartmentForm(false);
                                    setEditingDepartment(null);
                                    resetDepartmentForm();
                                }}
                            >
                                Cancel
                            </Button>
                            <Button type="submit">
                                {editingDepartment ? 'Update Department' : 'Create Department'}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default DepartmentTreeView;