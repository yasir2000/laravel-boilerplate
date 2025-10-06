<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ReportingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct(
        private ReportingService $reportingService
    ) {}

    public function employeeReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'nullable|in:active,inactive,terminated',
            'hire_date_from' => 'nullable|date',
            'hire_date_to' => 'nullable|date|after_or_equal:hire_date_from',
            'format' => 'nullable|in:json,pdf,excel'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filters = $request->only(['department_id', 'status', 'hire_date_from', 'hire_date_to']);
            $report = $this->reportingService->generateEmployeeReport($filters);

            $format = $request->get('format', 'json');
            
            return match ($format) {
                'pdf' => $this->generatePdfReport($report, 'employee'),
                'excel' => $this->generateExcelReport($report, 'employee'),
                default => response()->json([
                    'success' => true,
                    'data' => $report
                ])
            };
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate employee report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function attendanceReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:week,month,quarter,year',
            'employee_id' => 'nullable|exists:employees,id',
            'department_id' => 'nullable|exists:departments,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|in:json,pdf,excel'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $period = $request->get('period', 'month');
            $filters = $request->only(['employee_id', 'department_id', 'start_date', 'end_date']);
            
            $report = $this->reportingService->generateAttendanceReport($period, $filters);

            $format = $request->get('format', 'json');
            
            return match ($format) {
                'pdf' => $this->generatePdfReport($report, 'attendance'),
                'excel' => $this->generateExcelReport($report, 'attendance'),
                default => response()->json([
                    'success' => true,
                    'data' => $report
                ])
            };
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate attendance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function performanceReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'department_id' => 'nullable|exists:departments,id',
            'format' => 'nullable|in:json,pdf,excel'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filters = $request->only(['period_start', 'period_end', 'department_id']);
            $report = $this->reportingService->generatePerformanceReport($filters);

            $format = $request->get('format', 'json');
            
            return match ($format) {
                'pdf' => $this->generatePdfReport($report, 'performance'),
                'excel' => $this->generateExcelReport($report, 'performance'),
                default => response()->json([
                    'success' => true,
                    'data' => $report
                ])
            };
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate performance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function departmentReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'department_id' => 'nullable|exists:departments,id',
            'format' => 'nullable|in:json,pdf,excel'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $departmentId = $request->get('department_id');
            $report = $this->reportingService->generateDepartmentReport($departmentId);

            $format = $request->get('format', 'json');
            
            return match ($format) {
                'pdf' => $this->generatePdfReport($report, 'department'),
                'excel' => $this->generateExcelReport($report, 'department'),
                default => response()->json([
                    'success' => true,
                    'data' => $report
                ])
            };
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate department report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function customReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'metrics' => 'required|array',
            'metrics.*' => 'in:employees,attendance,performance,financial',
            'filters' => 'nullable|array',
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after_or_equal:date_range.start',
            'format' => 'nullable|in:json,pdf,excel'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $config = [
                'name' => $request->get('name'),
                'metrics' => $request->get('metrics'),
                'filters' => $request->get('filters', []),
                'date_range' => $request->get('date_range', []),
                'generated_by' => auth()->id()
            ];

            $report = $this->reportingService->generateCustomReport($config);

            $format = $request->get('format', 'json');
            
            return match ($format) {
                'pdf' => $this->generatePdfReport($report, 'custom'),
                'excel' => $this->generateExcelReport($report, 'custom'),
                default => response()->json([
                    'success' => true,
                    'data' => $report
                ])
            };
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate custom report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function dashboardMetrics(Request $request): JsonResponse
    {
        try {
            $metrics = [
                'employee_summary' => [
                    'total_employees' => \App\Models\Employee::count(),
                    'active_employees' => \App\Models\Employee::where('status', 'active')->count(),
                    'new_hires_this_month' => \App\Models\Employee::whereMonth('hire_date', now()->month)->count(),
                    'departments_count' => \App\Models\Department::count()
                ],
                'attendance_summary' => [
                    'today_attendance_rate' => $this->getTodayAttendanceRate(),
                    'this_month_average' => $this->getMonthlyAttendanceAverage(),
                    'punctuality_rate' => $this->getPunctualityRate(),
                    'absent_today' => $this->getAbsentToday()
                ],
                'performance_summary' => [
                    'average_performance_score' => $this->getAveragePerformanceScore(),
                    'evaluations_this_quarter' => $this->getEvaluationsThisQuarter(),
                    'top_performers_count' => $this->getTopPerformersCount(),
                    'improvement_needed_count' => $this->getImprovementNeededCount()
                ],
                'recent_activities' => $this->getRecentActivities()
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generatePdfReport(array $data, string $type): JsonResponse
    {
        // PDF generation implementation
        // You would use a library like DomPDF or Laravel Snappy
        return response()->json([
            'success' => true,
            'message' => 'PDF report generation not implemented yet',
            'download_url' => null
        ]);
    }

    private function generateExcelReport(array $data, string $type): JsonResponse
    {
        // Excel generation implementation
        // You would use Laravel Excel package
        return response()->json([
            'success' => true,
            'message' => 'Excel report generation not implemented yet',
            'download_url' => null
        ]);
    }

    private function getTodayAttendanceRate(): float
    {
        $totalEmployees = \App\Models\Employee::where('status', 'active')->count();
        $presentToday = \App\Models\Attendance::where('date', today())
            ->where('status', 'present')
            ->count();

        return $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100, 2) : 0;
    }

    private function getMonthlyAttendanceAverage(): float
    {
        $attendances = \App\Models\Attendance::whereMonth('date', now()->month)->get();
        
        if ($attendances->isEmpty()) {
            return 0;
        }

        $present = $attendances->where('status', 'present')->count();
        return round(($present / $attendances->count()) * 100, 2);
    }

    private function getPunctualityRate(): float
    {
        $presentToday = \App\Models\Attendance::where('date', today())
            ->where('status', 'present')
            ->get();

        if ($presentToday->isEmpty()) {
            return 0;
        }

        $onTime = $presentToday->filter(function ($attendance) {
            return $attendance->check_in_time <= '09:00:00';
        })->count();

        return round(($onTime / $presentToday->count()) * 100, 2);
    }

    private function getAbsentToday(): int
    {
        return \App\Models\Attendance::where('date', today())
            ->where('status', 'absent')
            ->count();
    }

    private function getAveragePerformanceScore(): float
    {
        return round(\App\Models\PerformanceEvaluation::avg('overall_score') ?? 0, 2);
    }

    private function getEvaluationsThisQuarter(): int
    {
        return \App\Models\PerformanceEvaluation::whereQuarter('evaluation_date', now()->quarter)
            ->count();
    }

    private function getTopPerformersCount(): int
    {
        return \App\Models\PerformanceEvaluation::where('overall_score', '>=', 90)
            ->distinct('employee_id')
            ->count();
    }

    private function getImprovementNeededCount(): int
    {
        return \App\Models\PerformanceEvaluation::where('overall_score', '<', 70)
            ->distinct('employee_id')
            ->count();
    }

    private function getRecentActivities(): array
    {
        // Get recent activities from various models
        $activities = [];

        // Recent hires
        $recentHires = \App\Models\Employee::with('user')
            ->where('hire_date', '>=', now()->subDays(7))
            ->latest('hire_date')
            ->take(5)
            ->get();

        foreach ($recentHires as $employee) {
            $activities[] = [
                'type' => 'hire',
                'message' => "New employee {$employee->user->name} joined",
                'date' => $employee->hire_date,
                'employee_id' => $employee->id
            ];
        }

        // Recent evaluations
        $recentEvaluations = \App\Models\PerformanceEvaluation::with('employee.user')
            ->where('evaluation_date', '>=', now()->subDays(7))
            ->latest('evaluation_date')
            ->take(5)
            ->get();

        foreach ($recentEvaluations as $evaluation) {
            $activities[] = [
                'type' => 'evaluation',
                'message' => "Performance evaluation completed for {$evaluation->employee->user->name}",
                'date' => $evaluation->evaluation_date,
                'employee_id' => $evaluation->employee_id
            ];
        }

        // Sort by date and return recent 10
        return collect($activities)
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->toArray();
    }
}