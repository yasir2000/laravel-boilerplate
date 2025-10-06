import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { 
    Plus, 
    Edit, 
    Eye, 
    Star, 
    Target, 
    Calendar, 
    User, 
    Award, 
    TrendingUp, 
    TrendingDown,
    CheckCircle,
    Clock,
    AlertCircle,
    BarChart3,
    FileText,
    Download
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
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Progress } from '@/components/ui/progress';
import { useToast } from '@/hooks/use-toast';
import { DatePicker } from '@/components/ui/date-picker';

const PerformanceEvaluation = () => {
    const { toast } = useToast();
    const [evaluations, setEvaluations] = useState([]);
    const [employees, setEmployees] = useState([]);
    const [departments, setDepartments] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedDepartment, setSelectedDepartment] = useState('all');
    const [selectedPeriod, setSelectedPeriod] = useState('current');
    const [selectedStatus, setSelectedStatus] = useState('all');

    // Evaluation form state
    const [showEvaluationForm, setShowEvaluationForm] = useState(false);
    const [editingEvaluation, setEditingEvaluation] = useState(null);
    const [viewingEvaluation, setViewingEvaluation] = useState(null);
    const [showEvaluationDetail, setShowEvaluationDetail] = useState(false);

    // Form data
    const [evaluationForm, setEvaluationForm] = useState({
        employee_id: '',
        evaluation_period: '',
        evaluation_date: new Date().toISOString().split('T')[0],
        reviewer_id: '',
        goals_achievements: '',
        strengths: '',
        areas_for_improvement: '',
        training_recommendations: '',
        overall_rating: 5,
        technical_skills: 5,
        communication_skills: 5,
        leadership_skills: 5,
        teamwork: 5,
        initiative: 5,
        problem_solving: 5,
        punctuality: 5,
        quality_of_work: 5,
        next_period_goals: '',
        employee_comments: '',
        reviewer_comments: '',
        status: 'draft'
    });

    // Evaluation criteria
    const evaluationCriteria = [
        { key: 'technical_skills', label: 'Technical Skills', weight: 20 },
        { key: 'communication_skills', label: 'Communication', weight: 15 },
        { key: 'leadership_skills', label: 'Leadership', weight: 15 },
        { key: 'teamwork', label: 'Teamwork', weight: 15 },
        { key: 'initiative', label: 'Initiative', weight: 10 },
        { key: 'problem_solving', label: 'Problem Solving', weight: 10 },
        { key: 'punctuality', label: 'Punctuality', weight: 5 },
        { key: 'quality_of_work', label: 'Quality of Work', weight: 10 }
    ];

    useEffect(() => {
        fetchEvaluations();
        fetchEmployees();
        fetchDepartments();
    }, [selectedDepartment, selectedPeriod, selectedStatus]);

    const fetchEvaluations = async () => {
        try {
            setLoading(true);
            const params = new URLSearchParams({
                department: selectedDepartment,
                period: selectedPeriod,
                status: selectedStatus
            });

            const response = await fetch(`/api/hr/evaluations?${params}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });

            if (response.ok) {
                const data = await response.json();
                setEvaluations(data.data);
            } else {
                throw new Error('Failed to fetch evaluations');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to fetch evaluations",
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

    const handleEvaluationSubmit = async (e) => {
        e.preventDefault();
        try {
            // Calculate overall score
            const totalScore = evaluationCriteria.reduce((sum, criteria) => {
                return sum + (evaluationForm[criteria.key] * criteria.weight / 100);
            }, 0);

            const evaluationData = {
                ...evaluationForm,
                overall_score: totalScore.toFixed(1)
            };

            const url = editingEvaluation 
                ? `/api/hr/evaluations/${editingEvaluation.id}` 
                : '/api/hr/evaluations';
            
            const method = editingEvaluation ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method,
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(evaluationData)
            });

            if (response.ok) {
                toast({
                    title: "Success",
                    description: `Evaluation ${editingEvaluation ? 'updated' : 'created'} successfully`,
                });
                setShowEvaluationForm(false);
                setEditingEvaluation(null);
                resetEvaluationForm();
                fetchEvaluations();
            } else {
                throw new Error('Failed to save evaluation');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to save evaluation",
                variant: "destructive",
            });
        }
    };

    const handleDeleteEvaluation = async (evaluationId) => {
        if (!confirm('Are you sure you want to delete this evaluation?')) return;

        try {
            const response = await fetch(`/api/hr/evaluations/${evaluationId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            });

            if (response.ok) {
                toast({
                    title: "Success",
                    description: "Evaluation deleted successfully",
                });
                fetchEvaluations();
            } else {
                throw new Error('Failed to delete evaluation');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to delete evaluation",
                variant: "destructive",
            });
        }
    };

    const handleExportEvaluations = async () => {
        try {
            const params = new URLSearchParams({
                department: selectedDepartment,
                period: selectedPeriod,
                status: selectedStatus
            });

            const response = await fetch(`/api/hr/evaluations/export?${params}`, {
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
                a.download = `evaluations-${new Date().toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                throw new Error('Failed to export evaluations');
            }
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to export evaluations",
                variant: "destructive",
            });
        }
    };

    const resetEvaluationForm = () => {
        setEvaluationForm({
            employee_id: '',
            evaluation_period: '',
            evaluation_date: new Date().toISOString().split('T')[0],
            reviewer_id: '',
            goals_achievements: '',
            strengths: '',
            areas_for_improvement: '',
            training_recommendations: '',
            overall_rating: 5,
            technical_skills: 5,
            communication_skills: 5,
            leadership_skills: 5,
            teamwork: 5,
            initiative: 5,
            problem_solving: 5,
            punctuality: 5,
            quality_of_work: 5,
            next_period_goals: '',
            employee_comments: '',
            reviewer_comments: '',
            status: 'draft'
        });
    };

    const openEditEvaluation = (evaluation) => {
        setEditingEvaluation(evaluation);
        setEvaluationForm({
            employee_id: evaluation.employee_id || '',
            evaluation_period: evaluation.evaluation_period || '',
            evaluation_date: evaluation.evaluation_date || '',
            reviewer_id: evaluation.reviewer_id || '',
            goals_achievements: evaluation.goals_achievements || '',
            strengths: evaluation.strengths || '',
            areas_for_improvement: evaluation.areas_for_improvement || '',
            training_recommendations: evaluation.training_recommendations || '',
            overall_rating: evaluation.overall_rating || 5,
            technical_skills: evaluation.technical_skills || 5,
            communication_skills: evaluation.communication_skills || 5,
            leadership_skills: evaluation.leadership_skills || 5,
            teamwork: evaluation.teamwork || 5,
            initiative: evaluation.initiative || 5,
            problem_solving: evaluation.problem_solving || 5,
            punctuality: evaluation.punctuality || 5,
            quality_of_work: evaluation.quality_of_work || 5,
            next_period_goals: evaluation.next_period_goals || '',
            employee_comments: evaluation.employee_comments || '',
            reviewer_comments: evaluation.reviewer_comments || '',
            status: evaluation.status || 'draft'
        });
        setShowEvaluationForm(true);
    };

    const openViewEvaluation = (evaluation) => {
        setViewingEvaluation(evaluation);
        setShowEvaluationDetail(true);
    };

    const getStatusBadge = (status) => {
        const variants = {
            draft: 'secondary',
            in_review: 'outline',
            completed: 'default',
            approved: 'default'
        };
        const colors = {
            draft: 'text-gray-600',
            in_review: 'text-yellow-600',
            completed: 'text-green-600',
            approved: 'text-blue-600'
        };
        return (
            <Badge variant={variants[status] || 'secondary'} className={colors[status]}>
                {status.replace('_', ' ').toUpperCase()}
            </Badge>
        );
    };

    const getScoreColor = (score) => {
        if (score >= 8) return 'text-green-600';
        if (score >= 6) return 'text-yellow-600';
        return 'text-red-600';
    };

    const getScoreIcon = (score) => {
        if (score >= 8) return <TrendingUp className="h-4 w-4" />;
        if (score >= 6) return <Target className="h-4 w-4" />;
        return <TrendingDown className="h-4 w-4" />;
    };

    const renderStarRating = (rating, onChange, disabled = false) => {
        return (
            <div className="flex space-x-1">
                {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map((star) => (
                    <Star
                        key={star}
                        className={`h-5 w-5 cursor-pointer ${
                            star <= rating ? 'text-yellow-400 fill-current' : 'text-gray-300'
                        } ${disabled ? 'cursor-not-allowed' : ''}`}
                        onClick={() => !disabled && onChange && onChange(star)}
                    />
                ))}
                <span className="ml-2 text-sm font-medium">{rating}/10</span>
            </div>
        );
    };

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold tracking-tight">Performance Evaluations</h1>
                <div className="flex gap-2">
                    <Button onClick={handleExportEvaluations} variant="outline">
                        <Download className="h-4 w-4 mr-2" />
                        Export
                    </Button>
                    <Button onClick={() => setShowEvaluationForm(true)}>
                        <Plus className="h-4 w-4 mr-2" />
                        New Evaluation
                    </Button>
                </div>
            </div>

            {/* Filters */}
            <Card>
                <CardContent className="p-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
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
                            <label className="text-sm font-medium mb-1 block">Period</label>
                            <Select value={selectedPeriod} onValueChange={setSelectedPeriod}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="current">Current Period</SelectItem>
                                    <SelectItem value="q1_2024">Q1 2024</SelectItem>
                                    <SelectItem value="q2_2024">Q2 2024</SelectItem>
                                    <SelectItem value="q3_2024">Q3 2024</SelectItem>
                                    <SelectItem value="q4_2024">Q4 2024</SelectItem>
                                    <SelectItem value="annual_2024">Annual 2024</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label className="text-sm font-medium mb-1 block">Status</label>
                            <Select value={selectedStatus} onValueChange={setSelectedStatus}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="in_review">In Review</SelectItem>
                                    <SelectItem value="completed">Completed</SelectItem>
                                    <SelectItem value="approved">Approved</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Evaluations Table */}
            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Employee</TableHead>
                                <TableHead>Period</TableHead>
                                <TableHead>Overall Score</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Reviewer</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {loading ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center py-8">
                                        Loading evaluations...
                                    </TableCell>
                                </TableRow>
                            ) : evaluations.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center py-8">
                                        No evaluations found
                                    </TableCell>
                                </TableRow>
                            ) : (
                                evaluations.map((evaluation) => (
                                    <TableRow key={evaluation.id}>
                                        <TableCell>
                                            <div className="flex items-center space-x-3">
                                                <Avatar className="h-8 w-8">
                                                    <AvatarImage src={evaluation.employee?.avatar_url} />
                                                    <AvatarFallback>
                                                        {evaluation.employee?.name.split(' ').map(n => n[0]).join('').toUpperCase()}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <div className="font-medium">{evaluation.employee?.name}</div>
                                                    <div className="text-sm text-gray-500">
                                                        {evaluation.employee?.department?.name}
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>{evaluation.evaluation_period}</TableCell>
                                        <TableCell>
                                            <div className={`flex items-center space-x-2 ${getScoreColor(evaluation.overall_score)}`}>
                                                {getScoreIcon(evaluation.overall_score)}
                                                <span className="font-semibold">{evaluation.overall_score}/10</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>{getStatusBadge(evaluation.status)}</TableCell>
                                        <TableCell>
                                            <div className="text-sm">
                                                {evaluation.reviewer?.name || 'Not assigned'}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {new Date(evaluation.evaluation_date).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center space-x-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => openViewEvaluation(evaluation)}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => openEditEvaluation(evaluation)}
                                                >
                                                    <Edit className="h-4 w-4" />
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

            {/* Evaluation Form Dialog */}
            <Dialog open={showEvaluationForm} onOpenChange={setShowEvaluationForm}>
                <DialogContent className="sm:max-w-[800px] max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {editingEvaluation ? 'Edit Evaluation' : 'Create New Evaluation'}
                        </DialogTitle>
                        <DialogDescription>
                            Complete the performance evaluation form below.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <form onSubmit={handleEvaluationSubmit} className="space-y-6">
                        {/* Basic Information */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="text-sm font-medium">Employee</label>
                                <Select 
                                    value={evaluationForm.employee_id} 
                                    onValueChange={(value) => setEvaluationForm({...evaluationForm, employee_id: value})}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select employee" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {employees.map((emp) => (
                                            <SelectItem key={emp.id} value={emp.id.toString()}>
                                                {emp.name} - {emp.department?.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="text-sm font-medium">Evaluation Period</label>
                                <Input
                                    value={evaluationForm.evaluation_period}
                                    onChange={(e) => setEvaluationForm({...evaluationForm, evaluation_period: e.target.value})}
                                    placeholder="e.g., Q1 2024"
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Evaluation Date</label>
                                <Input
                                    type="date"
                                    value={evaluationForm.evaluation_date}
                                    onChange={(e) => setEvaluationForm({...evaluationForm, evaluation_date: e.target.value})}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Status</label>
                                <Select 
                                    value={evaluationForm.status} 
                                    onValueChange={(value) => setEvaluationForm({...evaluationForm, status: value})}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="in_review">In Review</SelectItem>
                                        <SelectItem value="completed">Completed</SelectItem>
                                        <SelectItem value="approved">Approved</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {/* Performance Criteria */}
                        <div>
                            <h3 className="text-lg font-semibold mb-4">Performance Criteria</h3>
                            <div className="space-y-4">
                                {evaluationCriteria.map((criteria) => (
                                    <div key={criteria.key} className="border rounded-lg p-4">
                                        <div className="flex justify-between items-center mb-2">
                                            <label className="text-sm font-medium">
                                                {criteria.label} ({criteria.weight}%)
                                            </label>
                                        </div>
                                        {renderStarRating(
                                            evaluationForm[criteria.key],
                                            (rating) => setEvaluationForm({
                                                ...evaluationForm,
                                                [criteria.key]: rating
                                            })
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Text Fields */}
                        <div className="space-y-4">
                            <div>
                                <label className="text-sm font-medium">Goals & Achievements</label>
                                <textarea
                                    className="w-full px-3 py-2 text-sm border border-input rounded-md"
                                    rows={3}
                                    value={evaluationForm.goals_achievements}
                                    onChange={(e) => setEvaluationForm({...evaluationForm, goals_achievements: e.target.value})}
                                    placeholder="Describe goals met and key achievements..."
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Strengths</label>
                                <textarea
                                    className="w-full px-3 py-2 text-sm border border-input rounded-md"
                                    rows={3}
                                    value={evaluationForm.strengths}
                                    onChange={(e) => setEvaluationForm({...evaluationForm, strengths: e.target.value})}
                                    placeholder="Key strengths and positive attributes..."
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Areas for Improvement</label>
                                <textarea
                                    className="w-full px-3 py-2 text-sm border border-input rounded-md"
                                    rows={3}
                                    value={evaluationForm.areas_for_improvement}
                                    onChange={(e) => setEvaluationForm({...evaluationForm, areas_for_improvement: e.target.value})}
                                    placeholder="Areas that need development..."
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Training Recommendations</label>
                                <textarea
                                    className="w-full px-3 py-2 text-sm border border-input rounded-md"
                                    rows={2}
                                    value={evaluationForm.training_recommendations}
                                    onChange={(e) => setEvaluationForm({...evaluationForm, training_recommendations: e.target.value})}
                                    placeholder="Recommended training or development opportunities..."
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">Next Period Goals</label>
                                <textarea
                                    className="w-full px-3 py-2 text-sm border border-input rounded-md"
                                    rows={3}
                                    value={evaluationForm.next_period_goals}
                                    onChange={(e) => setEvaluationForm({...evaluationForm, next_period_goals: e.target.value})}
                                    placeholder="Goals and objectives for the next evaluation period..."
                                />
                            </div>
                        </div>

                        <div className="flex justify-end space-x-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setShowEvaluationForm(false);
                                    setEditingEvaluation(null);
                                    resetEvaluationForm();
                                }}
                            >
                                Cancel
                            </Button>
                            <Button type="submit">
                                {editingEvaluation ? 'Update Evaluation' : 'Create Evaluation'}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            {/* View Evaluation Dialog */}
            <Dialog open={showEvaluationDetail} onOpenChange={setShowEvaluationDetail}>
                <DialogContent className="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Evaluation Details</DialogTitle>
                        <DialogDescription>
                            Performance evaluation for {viewingEvaluation?.employee?.name}
                        </DialogDescription>
                    </DialogHeader>
                    
                    {viewingEvaluation && (
                        <div className="space-y-6">
                            {/* Header Info */}
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-3">
                                    <Avatar className="h-12 w-12">
                                        <AvatarImage src={viewingEvaluation.employee?.avatar_url} />
                                        <AvatarFallback>
                                            {viewingEvaluation.employee?.name.split(' ').map(n => n[0]).join('').toUpperCase()}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <h3 className="font-semibold">{viewingEvaluation.employee?.name}</h3>
                                        <p className="text-sm text-gray-500">{viewingEvaluation.employee?.department?.name}</p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <div className={`text-2xl font-bold ${getScoreColor(viewingEvaluation.overall_score)}`}>
                                        {viewingEvaluation.overall_score}/10
                                    </div>
                                    <div className="text-sm text-gray-500">Overall Score</div>
                                </div>
                            </div>

                            {/* Performance Breakdown */}
                            <div>
                                <h4 className="font-semibold mb-3">Performance Breakdown</h4>
                                <div className="space-y-3">
                                    {evaluationCriteria.map((criteria) => (
                                        <div key={criteria.key} className="flex justify-between items-center">
                                            <span className="text-sm">{criteria.label}</span>
                                            <div className="flex items-center space-x-2">
                                                <Progress 
                                                    value={(viewingEvaluation[criteria.key] || 0) * 10} 
                                                    className="w-24" 
                                                />
                                                <span className="text-sm font-medium w-8">
                                                    {viewingEvaluation[criteria.key]}/10
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Evaluation Details */}
                            <div className="space-y-4">
                                {viewingEvaluation.goals_achievements && (
                                    <div>
                                        <h4 className="font-semibold mb-2">Goals & Achievements</h4>
                                        <p className="text-sm text-gray-700">{viewingEvaluation.goals_achievements}</p>
                                    </div>
                                )}
                                {viewingEvaluation.strengths && (
                                    <div>
                                        <h4 className="font-semibold mb-2">Strengths</h4>
                                        <p className="text-sm text-gray-700">{viewingEvaluation.strengths}</p>
                                    </div>
                                )}
                                {viewingEvaluation.areas_for_improvement && (
                                    <div>
                                        <h4 className="font-semibold mb-2">Areas for Improvement</h4>
                                        <p className="text-sm text-gray-700">{viewingEvaluation.areas_for_improvement}</p>
                                    </div>
                                )}
                                {viewingEvaluation.next_period_goals && (
                                    <div>
                                        <h4 className="font-semibold mb-2">Next Period Goals</h4>
                                        <p className="text-sm text-gray-700">{viewingEvaluation.next_period_goals}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default PerformanceEvaluation;