<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\Department;
use App\Models\HR\Attendance;
use App\Models\HR\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get KPIs for dashboard
     */
    public function dashboardKpis(Request $request)
    {
        try {
            $kpis = [
                [
                    'title' => 'Total Employees',
                    'value' => Employee::where('employment_status', 'active')->count(),
                    'icon' => 'fa-users',
                    'trend' => '+12%',
                    'color' => '#667eea'
                ],
                [
                    'title' => 'Present Today',
                    'value' => Attendance::whereDate('date', today())
                        ->where('status', 'present')->count(),
                    'icon' => 'fa-check-circle',
                    'trend' => '+5%',
                    'color' => '#28a745'
                ],
                [
                    'title' => 'On Leave',
                    'value' => LeaveRequest::where('status', 'approved')
                        ->where('start_date', '<=', today())
                        ->where('end_date', '>=', today())
                        ->count(),
                    'icon' => 'fa-calendar-times-o',
                    'trend' => '-8%',
                    'color' => '#dc3545'
                ],
                [
                    'title' => 'Departments',
                    'value' => Department::where('is_active', true)->count(),
                    'icon' => 'fa-building',
                    'trend' => '+2%',
                    'color' => '#6f42c1'
                ],
                [
                    'title' => 'Pending Requests',
                    'value' => LeaveRequest::where('status', 'pending')->count(),
                    'icon' => 'fa-clock-o',
                    'trend' => '+15%',
                    'color' => '#ffc107'
                ],
                [
                    'title' => 'New Hires MTD',
                    'value' => Employee::whereMonth('hire_date', now()->month)
                        ->whereYear('hire_date', now()->year)->count(),
                    'icon' => 'fa-user-plus',
                    'trend' => '+25%',
                    'color' => '#17a2b8'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $kpis,
                'message' => 'Dashboard KPIs retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard KPIs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance trends data
     */
    public function attendanceTrends(Request $request)
    {
        try {
            $days = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($days);

            $trends = DB::table('attendance')
                ->select(
                    DB::raw('DATE(date) as date'),
                    DB::raw('COUNT(CASE WHEN status = "present" THEN 1 END) as present'),
                    DB::raw('COUNT(CASE WHEN status = "absent" THEN 1 END) as absent'),
                    DB::raw('COUNT(CASE WHEN status = "late" THEN 1 END) as late'),
                    DB::raw('COUNT(*) as total')
                )
                ->where('date', '>=', $startDate)
                ->groupBy(DB::raw('DATE(date)'))
                ->orderBy('date')
                ->get()
                ->map(function($item) {
                    return [
                        'date' => Carbon::parse($item->date)->format('M d'),
                        'present' => $item->present,
                        'absent' => $item->absent,
                        'late' => $item->late,
                        'total' => $item->total,
                        'attendance_rate' => $item->total > 0 ? round(($item->present / $item->total) * 100, 2) : 0
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $trends,
                'message' => 'Attendance trends retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attendance trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get department distribution data
     */
    public function departmentDistribution(Request $request)
    {
        try {
            $distribution = Department::with('employees')
                ->where('is_active', true)
                ->get()
                ->map(function($department) {
                    return [
                        'name' => $department->name,
                        'code' => $department->code,
                        'total_employees' => $department->employees->count(),
                        'active_employees' => $department->employees->where('employment_status', 'active')->count(),
                        'percentage' => 0 // Will calculate below
                    ];
                });

            $totalEmployees = $distribution->sum('active_employees');
            
            $distribution = $distribution->map(function($item) use ($totalEmployees) {
                $item['percentage'] = $totalEmployees > 0 ? round(($item['active_employees'] / $totalEmployees) * 100, 2) : 0;
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $distribution,
                'message' => 'Department distribution retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve department distribution: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee performance data
     */
    public function employeePerformance(Request $request)
    {
        try {
            $limit = $request->get('limit', 50);
            
            $performance = Employee::with(['department', 'position'])
                ->where('employment_status', 'active')
                ->withCount(['attendance as attendance_rate' => function($query) {
                    $query->where('status', 'present')
                          ->where('date', '>=', Carbon::now()->subDays(30));
                }])
                ->get()
                ->map(function($employee) {
                    $totalWorkDays = 22; // Average work days per month
                    $attendanceRate = $employee->attendance_rate ? 
                        round(($employee->attendance_rate / $totalWorkDays) * 100, 2) : 0;
                    
                    return [
                        'id' => $employee->id,
                        'employee_id' => $employee->employee_id,
                        'full_name' => $employee->first_name . ' ' . $employee->last_name,
                        'email' => $employee->email,
                        'department' => $employee->department->name ?? 'N/A',
                        'position' => $employee->position->title ?? 'N/A',
                        'hire_date' => $employee->hire_date ? Carbon::parse($employee->hire_date)->format('M d, Y') : 'N/A',
                        'employment_status' => ucfirst($employee->employment_status),
                        'attendance_rate' => $attendanceRate,
                        'performance_score' => rand(70, 100), // Mock score
                        'satisfaction_score' => rand(3, 5) // Mock score (1-5)
                    ];
                })
                ->sortByDesc('attendance_rate')
                ->take($limit)
                ->values();

            return response()->json([
                'success' => true,
                'data' => $performance,
                'message' => 'Employee performance data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve employee performance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leave analysis data
     */
    public function leaveAnalysis(Request $request)
    {
        try {
            $year = $request->get('year', now()->year);
            
            // Leave requests by status
            $statusData = LeaveRequest::whereYear('created_at', $year)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function($item) {
                    return [ucfirst($item->status) => $item->count];
                });

            // Leave requests by type
            $typeData = LeaveRequest::whereYear('created_at', $year)
                ->select('leave_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(days_requested) as total_days'))
                ->groupBy('leave_type')
                ->get()
                ->map(function($item) {
                    return [
                        'type' => ucfirst(str_replace('_', ' ', $item->leave_type)),
                        'requests' => $item->count,
                        'total_days' => $item->total_days ?? 0
                    ];
                });

            // Leave trends by month
            $monthlyTrends = LeaveRequest::whereYear('created_at', $year)
                ->select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('COUNT(*) as requests'),
                    DB::raw('SUM(days_requested) as days')
                )
                ->groupBy(DB::raw('MONTH(created_at)'))
                ->orderBy('month')
                ->get()
                ->map(function($item) {
                    return [
                        'month' => Carbon::create(null, $item->month)->format('M'),
                        'requests' => $item->requests,
                        'days' => $item->days ?? 0
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'status_distribution' => $statusData,
                    'type_analysis' => $typeData,
                    'monthly_trends' => $monthlyTrends
                ],
                'message' => 'Leave analysis data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve leave analysis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance summary
     */
    public function attendanceSummary(Request $request)
    {
        try {
            $startDate = Carbon::parse($request->get('start_date', Carbon::now()->startOfMonth()));
            $endDate = Carbon::parse($request->get('end_date', Carbon::now()->endOfMonth()));

            $summary = Attendance::whereBetween('date', [$startDate, $endDate])
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function($item) {
                    return [ucfirst($item->status) => $item->count];
                });

            // Daily patterns
            $dailyPattern = Attendance::whereBetween('date', [$startDate, $endDate])
                ->select(
                    DB::raw('DAYNAME(date) as day_name'),
                    DB::raw('AVG(TIME_TO_SEC(TIMEDIFF(check_out_time, check_in_time))/3600) as avg_hours'),
                    DB::raw('COUNT(*) as total_records')
                )
                ->whereNotNull('check_in_time')
                ->whereNotNull('check_out_time')
                ->groupBy(DB::raw('DAYNAME(date)'))
                ->orderBy(DB::raw('DAYOFWEEK(date)'))
                ->get()
                ->map(function($item) {
                    return [
                        'day' => $item->day_name,
                        'avg_hours' => round($item->avg_hours ?? 0, 2),
                        'records' => $item->total_records
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'daily_pattern' => $dailyPattern,
                    'period' => [
                        'start' => $startDate->format('M d, Y'),
                        'end' => $endDate->format('M d, Y')
                    ]
                ],
                'message' => 'Attendance summary retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attendance summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee demographics
     */
    public function employeeDemographics(Request $request)
    {
        try {
            $demographics = [
                'age_distribution' => Employee::where('employment_status', 'active')
                    ->whereNotNull('date_of_birth')
                    ->get()
                    ->groupBy(function($employee) {
                        $age = Carbon::parse($employee->date_of_birth)->age;
                        if ($age < 25) return '< 25';
                        if ($age < 35) return '25-34';
                        if ($age < 45) return '35-44';
                        if ($age < 55) return '45-54';
                        return '55+';
                    })
                    ->map(function($group) {
                        return $group->count();
                    }),

                'department_breakdown' => Employee::with('department')
                    ->where('employment_status', 'active')
                    ->get()
                    ->groupBy('department.name')
                    ->map(function($group) {
                        return $group->count();
                    }),

                'tenure_analysis' => Employee::where('employment_status', 'active')
                    ->whereNotNull('hire_date')
                    ->get()
                    ->groupBy(function($employee) {
                        $years = Carbon::parse($employee->hire_date)->diffInYears(now());
                        if ($years < 1) return '< 1 year';
                        if ($years < 3) return '1-2 years';
                        if ($years < 5) return '3-4 years';
                        if ($years < 10) return '5-9 years';
                        return '10+ years';
                    })
                    ->map(function($group) {
                        return $group->count();
                    })
            ];

            return response()->json([
                'success' => true,
                'data' => $demographics,
                'message' => 'Employee demographics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve employee demographics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leave types statistics
     */
    public function leaveTypes(Request $request)
    {
        try {
            $leaveTypes = [
                'annual' => 'Annual Leave',
                'sick' => 'Sick Leave', 
                'maternity' => 'Maternity Leave',
                'paternity' => 'Paternity Leave',
                'emergency' => 'Emergency Leave',
                'unpaid' => 'Unpaid Leave'
            ];

            $data = collect($leaveTypes)->map(function($label, $type) {
                return [
                    'type' => $type,
                    'label' => $label,
                    'total_requests' => LeaveRequest::where('leave_type', $type)->count(),
                    'approved' => LeaveRequest::where('leave_type', $type)->where('status', 'approved')->count(),
                    'pending' => LeaveRequest::where('leave_type', $type)->where('status', 'pending')->count(),
                    'rejected' => LeaveRequest::where('leave_type', $type)->where('status', 'rejected')->count(),
                    'total_days' => LeaveRequest::where('leave_type', $type)->where('status', 'approved')->sum('days_requested') ?? 0
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Leave types data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve leave types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report data
     */
    public function export(Request $request, $type)
    {
        try {
            $data = [];
            $filename = '';

            switch($type) {
                case 'employees':
                    $data = Employee::with(['department', 'position'])
                        ->where('employment_status', 'active')
                        ->get()
                        ->map(function($employee) {
                            return [
                                'Employee ID' => $employee->employee_id,
                                'Name' => $employee->first_name . ' ' . $employee->last_name,
                                'Email' => $employee->email,
                                'Department' => $employee->department->name ?? 'N/A',
                                'Position' => $employee->position->title ?? 'N/A',
                                'Hire Date' => $employee->hire_date,
                                'Status' => ucfirst($employee->employment_status)
                            ];
                        });
                    $filename = 'employees_export_' . now()->format('Y-m-d');
                    break;

                case 'attendance':
                    $data = Attendance::with('employee')
                        ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
                        ->get()
                        ->map(function($attendance) {
                            return [
                                'Date' => $attendance->date,
                                'Employee' => $attendance->employee->first_name . ' ' . $attendance->employee->last_name,
                                'Check In' => $attendance->check_in_time,
                                'Check Out' => $attendance->check_out_time,
                                'Status' => ucfirst($attendance->status),
                                'Hours' => $attendance->hours_worked ?? 0
                            ];
                        });
                    $filename = 'attendance_export_' . now()->format('Y-m-d');
                    break;

                case 'leave_requests':
                    $data = LeaveRequest::with('employee')
                        ->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])
                        ->get()
                        ->map(function($request) {
                            return [
                                'Employee' => $request->employee->first_name . ' ' . $request->employee->last_name,
                                'Leave Type' => ucfirst(str_replace('_', ' ', $request->leave_type)),
                                'Start Date' => $request->start_date,
                                'End Date' => $request->end_date,
                                'Days' => $request->days_requested,
                                'Status' => ucfirst($request->status),
                                'Applied Date' => $request->created_at->format('Y-m-d')
                            ];
                        });
                    $filename = 'leave_requests_export_' . now()->format('Y-m-d');
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid export type'
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'filename' => $filename,
                'message' => 'Export data prepared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data: ' . $e->getMessage()
            ], 500);
        }
    }
}