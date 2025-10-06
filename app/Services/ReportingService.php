<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Attendance;
use App\Models\PerformanceEvaluation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ReportingService
{
    public function generateEmployeeReport(array $filters = []): array
    {
        $query = Employee::with(['department', 'user']);

        // Apply filters
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['hire_date_from'])) {
            $query->where('hire_date', '>=', $filters['hire_date_from']);
        }

        if (!empty($filters['hire_date_to'])) {
            $query->where('hire_date', '<=', $filters['hire_date_to']);
        }

        $employees = $query->get();

        return [
            'total_employees' => $employees->count(),
            'by_department' => $employees->groupBy('department.name')->map->count(),
            'by_status' => $employees->groupBy('status')->map->count(),
            'average_salary' => $employees->avg('salary'),
            'salary_ranges' => $this->getSalaryRanges($employees),
            'employees' => $employees,
            'generated_at' => now(),
            'filters_applied' => $filters
        ];
    }

    public function generateAttendanceReport(string $period = 'month', array $filters = []): array
    {
        $startDate = $this->getStartDate($period);
        $endDate = now();

        $query = Attendance::whereBetween('date', [$startDate, $endDate])
            ->with(['employee.user', 'employee.department']);

        // Apply filters
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        $attendances = $query->get();

        return [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_records' => $attendances->count(),
            'attendance_rate' => $this->calculateAttendanceRate($attendances),
            'punctuality_rate' => $this->calculatePunctualityRate($attendances),
            'overtime_hours' => $this->calculateOvertimeHours($attendances),
            'by_employee' => $this->groupAttendanceByEmployee($attendances),
            'by_department' => $this->groupAttendanceByDepartment($attendances),
            'daily_trends' => $this->getDailyAttendanceTrends($attendances),
            'generated_at' => now(),
            'filters_applied' => $filters
        ];
    }

    public function generatePerformanceReport(array $filters = []): array
    {
        $query = PerformanceEvaluation::with(['employee.user', 'employee.department', 'evaluator']);

        // Apply filters
        if (!empty($filters['period_start'])) {
            $query->where('evaluation_date', '>=', $filters['period_start']);
        }

        if (!empty($filters['period_end'])) {
            $query->where('evaluation_date', '<=', $filters['period_end']);
        }

        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        $evaluations = $query->get();

        return [
            'total_evaluations' => $evaluations->count(),
            'average_score' => $evaluations->avg('overall_score'),
            'score_distribution' => $this->getScoreDistribution($evaluations),
            'by_department' => $this->groupPerformanceByDepartment($evaluations),
            'top_performers' => $this->getTopPerformers($evaluations),
            'improvement_needed' => $this->getImprovementNeeded($evaluations),
            'performance_trends' => $this->getPerformanceTrends($evaluations),
            'generated_at' => now(),
            'filters_applied' => $filters
        ];
    }

    public function generateDepartmentReport(int $departmentId = null): array
    {
        $query = Department::with(['employees.user', 'employees.attendances', 'employees.evaluations']);

        if ($departmentId) {
            $query->where('id', $departmentId);
        }

        $departments = $query->get();

        return [
            'departments' => $departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'employee_count' => $department->employees->count(),
                    'average_salary' => $department->employees->avg('salary'),
                    'attendance_rate' => $this->calculateDepartmentAttendanceRate($department),
                    'performance_average' => $this->calculateDepartmentPerformanceAverage($department),
                    'budget_utilization' => $this->calculateBudgetUtilization($department),
                ];
            }),
            'generated_at' => now()
        ];
    }

    public function generateCustomReport(array $config): array
    {
        $data = [];

        // Employee metrics
        if (in_array('employees', $config['metrics'])) {
            $data['employees'] = $this->getEmployeeMetrics($config);
        }

        // Attendance metrics
        if (in_array('attendance', $config['metrics'])) {
            $data['attendance'] = $this->getAttendanceMetrics($config);
        }

        // Performance metrics
        if (in_array('performance', $config['metrics'])) {
            $data['performance'] = $this->getPerformanceMetrics($config);
        }

        // Financial metrics
        if (in_array('financial', $config['metrics'])) {
            $data['financial'] = $this->getFinancialMetrics($config);
        }

        return [
            'report_config' => $config,
            'data' => $data,
            'generated_at' => now()
        ];
    }

    private function getSalaryRanges(Collection $employees): array
    {
        $ranges = [
            '0-30k' => 0,
            '30k-50k' => 0,
            '50k-75k' => 0,
            '75k-100k' => 0,
            '100k+' => 0
        ];

        foreach ($employees as $employee) {
            $salary = $employee->salary;
            if ($salary < 30000) {
                $ranges['0-30k']++;
            } elseif ($salary < 50000) {
                $ranges['30k-50k']++;
            } elseif ($salary < 75000) {
                $ranges['50k-75k']++;
            } elseif ($salary < 100000) {
                $ranges['75k-100k']++;
            } else {
                $ranges['100k+']++;
            }
        }

        return $ranges;
    }

    private function getStartDate(string $period): Carbon
    {
        return match ($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth()
        };
    }

    private function calculateAttendanceRate(Collection $attendances): float
    {
        if ($attendances->isEmpty()) {
            return 0;
        }

        $present = $attendances->where('status', 'present')->count();
        return round(($present / $attendances->count()) * 100, 2);
    }

    private function calculatePunctualityRate(Collection $attendances): float
    {
        if ($attendances->isEmpty()) {
            return 0;
        }

        $onTime = $attendances->where('status', 'present')
            ->filter(function ($attendance) {
                return $attendance->check_in_time <= '09:00:00';
            })->count();

        $present = $attendances->where('status', 'present')->count();
        
        return $present > 0 ? round(($onTime / $present) * 100, 2) : 0;
    }

    private function calculateOvertimeHours(Collection $attendances): float
    {
        return $attendances->sum('overtime_hours') ?? 0;
    }

    private function groupAttendanceByEmployee(Collection $attendances): array
    {
        return $attendances->groupBy('employee_id')->map(function ($employeeAttendances) {
            $employee = $employeeAttendances->first()->employee;
            return [
                'employee_name' => $employee->user->name,
                'total_days' => $employeeAttendances->count(),
                'present_days' => $employeeAttendances->where('status', 'present')->count(),
                'absent_days' => $employeeAttendances->where('status', 'absent')->count(),
                'late_days' => $employeeAttendances->where('status', 'late')->count(),
                'attendance_rate' => $this->calculateAttendanceRate($employeeAttendances)
            ];
        })->toArray();
    }

    private function groupAttendanceByDepartment(Collection $attendances): array
    {
        return $attendances->groupBy('employee.department.name')->map(function ($deptAttendances) {
            return [
                'total_records' => $deptAttendances->count(),
                'attendance_rate' => $this->calculateAttendanceRate($deptAttendances),
                'punctuality_rate' => $this->calculatePunctualityRate($deptAttendances)
            ];
        })->toArray();
    }

    private function getDailyAttendanceTrends(Collection $attendances): array
    {
        return $attendances->groupBy('date')->map(function ($dailyAttendances, $date) {
            return [
                'date' => $date,
                'total' => $dailyAttendances->count(),
                'present' => $dailyAttendances->where('status', 'present')->count(),
                'absent' => $dailyAttendances->where('status', 'absent')->count(),
                'late' => $dailyAttendances->where('status', 'late')->count(),
                'rate' => $this->calculateAttendanceRate($dailyAttendances)
            ];
        })->sortBy('date')->values()->toArray();
    }

    private function getScoreDistribution(Collection $evaluations): array
    {
        $distribution = [
            'Excellent (90-100)' => 0,
            'Good (80-89)' => 0,
            'Average (70-79)' => 0,
            'Below Average (60-69)' => 0,
            'Poor (0-59)' => 0
        ];

        foreach ($evaluations as $evaluation) {
            $score = $evaluation->overall_score;
            if ($score >= 90) {
                $distribution['Excellent (90-100)']++;
            } elseif ($score >= 80) {
                $distribution['Good (80-89)']++;
            } elseif ($score >= 70) {
                $distribution['Average (70-79)']++;
            } elseif ($score >= 60) {
                $distribution['Below Average (60-69)']++;
            } else {
                $distribution['Poor (0-59)']++;
            }
        }

        return $distribution;
    }

    private function groupPerformanceByDepartment(Collection $evaluations): array
    {
        return $evaluations->groupBy('employee.department.name')->map(function ($deptEvaluations) {
            return [
                'count' => $deptEvaluations->count(),
                'average_score' => round($deptEvaluations->avg('overall_score'), 2),
                'highest_score' => $deptEvaluations->max('overall_score'),
                'lowest_score' => $deptEvaluations->min('overall_score')
            ];
        })->toArray();
    }

    private function getTopPerformers(Collection $evaluations, int $limit = 10): array
    {
        return $evaluations->sortByDesc('overall_score')
            ->take($limit)
            ->map(function ($evaluation) {
                return [
                    'employee_name' => $evaluation->employee->user->name,
                    'department' => $evaluation->employee->department->name,
                    'score' => $evaluation->overall_score,
                    'evaluation_date' => $evaluation->evaluation_date
                ];
            })->values()->toArray();
    }

    private function getImprovementNeeded(Collection $evaluations, float $threshold = 70): array
    {
        return $evaluations->where('overall_score', '<', $threshold)
            ->map(function ($evaluation) {
                return [
                    'employee_name' => $evaluation->employee->user->name,
                    'department' => $evaluation->employee->department->name,
                    'score' => $evaluation->overall_score,
                    'areas_for_improvement' => json_decode($evaluation->feedback, true)['improvement_areas'] ?? []
                ];
            })->values()->toArray();
    }

    private function getPerformanceTrends(Collection $evaluations): array
    {
        return $evaluations->groupBy(function ($evaluation) {
            return Carbon::parse($evaluation->evaluation_date)->format('Y-m');
        })->map(function ($monthlyEvaluations, $month) {
            return [
                'month' => $month,
                'count' => $monthlyEvaluations->count(),
                'average_score' => round($monthlyEvaluations->avg('overall_score'), 2)
            ];
        })->sortBy('month')->values()->toArray();
    }

    private function calculateDepartmentAttendanceRate(Department $department): float
    {
        $attendances = $department->employees->flatMap->attendances;
        return $this->calculateAttendanceRate($attendances);
    }

    private function calculateDepartmentPerformanceAverage(Department $department): float
    {
        $evaluations = $department->employees->flatMap->evaluations;
        return round($evaluations->avg('overall_score') ?? 0, 2);
    }

    private function calculateBudgetUtilization(Department $department): float
    {
        $totalSalary = $department->employees->sum('salary');
        $budgetLimit = $department->budget ?? 0;
        
        return $budgetLimit > 0 ? round(($totalSalary / $budgetLimit) * 100, 2) : 0;
    }

    private function getEmployeeMetrics(array $config): array
    {
        // Implementation for employee metrics based on config
        return [];
    }

    private function getAttendanceMetrics(array $config): array
    {
        // Implementation for attendance metrics based on config
        return [];
    }

    private function getPerformanceMetrics(array $config): array
    {
        // Implementation for performance metrics based on config
        return [];
    }

    private function getFinancialMetrics(array $config): array
    {
        // Implementation for financial metrics based on config
        return [];
    }
}