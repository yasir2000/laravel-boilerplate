<?php

namespace App\Http\Controllers\API\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Models\HR\Attendance;
use App\Models\HR\LeaveRequest;
use App\Models\HR\Position;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get main dashboard KPIs
     */
    public function index(): JsonResponse
    {
        try {
            $today = Carbon::today();
            $lastMonth = Carbon::now()->subMonth();
            
            // Total employees
            $totalEmployees = Employee::count();
            $employeesLastMonth = Employee::where('created_at', '<=', $lastMonth)->count();
            $employeesTrend = $employeesLastMonth > 0 ? 
                round((($totalEmployees - $employeesLastMonth) / $employeesLastMonth) * 100, 1) : 0;
            
            // Attendance rate for current month
            $currentMonth = Carbon::now()->startOfMonth();
            $attendanceRate = $this->calculateAttendanceRate($currentMonth);
            $lastMonthRate = $this->calculateAttendanceRate($lastMonth->startOfMonth());
            $attendanceTrend = $lastMonthRate > 0 ? 
                round($attendanceRate - $lastMonthRate, 1) : 0;
            
            // Pending leave requests
            $pendingLeaves = LeaveRequest::where('status', 'pending')->count();
            $pendingLeavesLastMonth = LeaveRequest::where('status', 'pending')
                ->where('created_at', '<=', $lastMonth)
                ->count();
            $leavesTrend = $pendingLeavesLastMonth > 0 ? 
                round((($pendingLeaves - $pendingLeavesLastMonth) / $pendingLeavesLastMonth) * 100, 1) : 0;
            
            // Total payroll (estimated based on positions)
            $totalPayroll = Employee::join('positions', 'employees.position_id', '=', 'positions.id')
                ->sum('positions.salary_range_max');
            $payrollTrend = 5.2; // Mock trend - in real app, calculate from previous period
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_employees' => $totalEmployees,
                    'employees_trend' => $employeesTrend,
                    'attendance_rate' => round($attendanceRate, 1),
                    'attendance_trend' => $attendanceTrend,
                    'pending_leave_requests' => $pendingLeaves,
                    'leaves_trend' => $leavesTrend,
                    'total_payroll' => $totalPayroll,
                    'payroll_trend' => $payrollTrend
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Get quick stats data
     */
    public function quickStats(): JsonResponse
    {
        try {
            $today = Carbon::today();
            
            // Total departments
            $totalDepartments = Department::count();
            
            // Open positions
            $openPositions = Position::where('is_active', true)
                ->whereNotExists(function($query) {
                    $query->select(DB::raw(1))
                        ->from('employees')
                        ->whereColumn('employees.position_id', 'positions.id');
                })
                ->count();
            
            // Employees in training (mock data - in real app, track training status)
            $inTraining = Employee::where('status', 'active')
                ->inRandomOrder()
                ->limit(rand(5, 15))
                ->count();
            
            // Present today
            $presentToday = Attendance::where('date', $today)
                ->where('status', 'present')
                ->count();
            
            // Average hours per week (mock calculation)
            $averageHours = Attendance::where('date', '>=', Carbon::now()->subWeek())
                ->where('status', 'present')
                ->avg(DB::raw('EXTRACT(EPOCH FROM (check_out_time - check_in_time))/3600')) ?? 40;
            
            // Top performer (mock - in real app, use performance metrics)
            $topPerformer = Employee::inRandomOrder()->first();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_departments' => $totalDepartments,
                    'open_positions' => $openPositions,
                    'in_training' => $inTraining,
                    'present_today' => $presentToday,
                    'average_hours' => round($averageHours, 1),
                    'top_performer' => $topPerformer ? $topPerformer->first_name . ' ' . $topPerformer->last_name : null
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load quick stats',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Get recent activity data
     */
    public function recentActivity(): JsonResponse
    {
        try {
            $activities = [];
            
            // Recent employee additions
            $newEmployees = Employee::where('created_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($newEmployees as $employee) {
                $activities[] = [
                    'type' => 'new_employee',
                    'description' => 'New employee added: ' . $employee->first_name . ' ' . $employee->last_name,
                    'timestamp' => $employee->created_at->diffForHumans(),
                    'user' => 'HR System',
                    'icon' => 'fa fa-user-plus'
                ];
            }
            
            // Recent leave requests
            $recentLeaves = LeaveRequest::where('created_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->with('employee')
                ->get();
            
            foreach ($recentLeaves as $leave) {
                $activities[] = [
                    'type' => 'leave_request',
                    'description' => 'Leave request submitted by ' . $leave->employee->first_name . ' ' . $leave->employee->last_name,
                    'timestamp' => $leave->created_at->diffForHumans(),
                    'user' => $leave->employee->first_name . ' ' . $leave->employee->last_name,
                    'icon' => 'fa fa-calendar-times-o'
                ];
            }
            
            // Sort activities by timestamp
            usort($activities, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });
            
            // Limit to 10 most recent
            $activities = array_slice($activities, 0, 10);
            
            return response()->json([
                'success' => true,
                'data' => $activities
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load recent activity',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Get alerts and notifications
     */
    public function alerts(): JsonResponse
    {
        try {
            $alerts = [];
            
            // Check for overdue leave requests
            $overdueLeaves = LeaveRequest::where('status', 'pending')
                ->where('created_at', '<=', Carbon::now()->subDays(3))
                ->count();
            
            if ($overdueLeaves > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'fa fa-exclamation-triangle',
                    'title' => 'Overdue Leave Requests',
                    'message' => $overdueLeaves . ' leave requests pending for more than 3 days'
                ];
            }
            
            // Check for employees with low attendance
            $lowAttendanceCount = $this->getEmployeesWithLowAttendance();
            if ($lowAttendanceCount > 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'icon' => 'fa fa-user-times',
                    'title' => 'Low Attendance Alert',
                    'message' => $lowAttendanceCount . ' employees with attendance below 85%'
                ];
            }
            
            // Check for birthdays this week
            $birthdaysThisWeek = Employee::whereRaw("DATE_PART('doy', date_of_birth) BETWEEN DATE_PART('doy', CURRENT_DATE) AND DATE_PART('doy', CURRENT_DATE + INTERVAL '7 days')")
                ->count();
                
            if ($birthdaysThisWeek > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'icon' => 'fa fa-birthday-cake',
                    'title' => 'Upcoming Birthdays',
                    'message' => $birthdaysThisWeek . ' employees have birthdays this week'
                ];
            }
            
            // Check for probation endings
            $probationEndings = Employee::where('status', 'probation')
                ->whereRaw("hire_date + INTERVAL '90 days' <= CURRENT_DATE + INTERVAL '7 days'")
                ->count();
                
            if ($probationEndings > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'icon' => 'fa fa-clock-o',
                    'title' => 'Probation Periods Ending',
                    'message' => $probationEndings . ' employees\' probation periods ending soon'
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $alerts
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load alerts',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Get employee growth data for Chart.js
     */
    public function employeeGrowth(): JsonResponse
    {
        try {
            $months = [];
            $data = [];
            
            // Get data for last 12 months
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $monthName = $date->format('M Y');
                
                // New hires in this month
                $newHires = Employee::whereYear('hire_date', $date->year)
                    ->whereMonth('hire_date', $date->month)
                    ->count();
                
                // Terminations in this month (mock data - in real app, track termination dates)
                $terminations = rand(0, max(1, $newHires - 1));
                
                $months[] = $monthName;
                $data[] = [
                    'month' => $monthName,
                    'new_hires' => $newHires,
                    'terminations' => $terminations
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load employee growth data',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Calculate attendance rate for a given month
     */
    private function calculateAttendanceRate(Carbon $month): float
    {
        $totalWorkingDays = $this->getWorkingDaysInMonth($month);
        if ($totalWorkingDays == 0) return 0;
        
        $totalEmployees = Employee::count();
        if ($totalEmployees == 0) return 0;
        
        $totalPossibleAttendance = $totalEmployees * $totalWorkingDays;
        
        $actualAttendance = Attendance::whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->where('status', 'present')
            ->count();
        
        return $totalPossibleAttendance > 0 ? ($actualAttendance / $totalPossibleAttendance) * 100 : 0;
    }
    
    /**
     * Get number of working days in a month (excluding weekends)
     */
    private function getWorkingDaysInMonth(Carbon $month): int
    {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
        
        $workingDays = 0;
        $current = $startOfMonth->copy();
        
        while ($current->lte($endOfMonth)) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }
        
        return $workingDays;
    }
    
    /**
     * Get count of employees with low attendance (below 85%)
     */
    private function getEmployeesWithLowAttendance(): int
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $totalWorkingDays = $this->getWorkingDaysInMonth($currentMonth);
        
        if ($totalWorkingDays == 0) return 0;
        
        $threshold = $totalWorkingDays * 0.85; // 85% threshold
        
        $employees = Employee::all();
        $lowAttendanceCount = 0;
        
        foreach ($employees as $employee) {
            $attendanceCount = Attendance::where('employee_id', $employee->id)
                ->whereYear('date', $currentMonth->year)
                ->whereMonth('date', $currentMonth->month)
                ->where('status', 'present')
                ->count();
            
            if ($attendanceCount < $threshold) {
                $lowAttendanceCount++;
            }
        }
        
        return $lowAttendanceCount;
    }
}