<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of attendance records
     */
    public function index(Request $request): JsonResponse
    {
        $query = Attendance::with(['employee.user', 'approver']);

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        } else {
            // Default to current month if no date range specified
            $query->whereMonth('date', now()->month)
                  ->whereYear('date', now()->year);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by attendance type
        if ($request->filled('attendance_type')) {
            $query->where('attendance_type', $request->attendance_type);
        }

        // Filter pending approval
        if ($request->boolean('pending_approval')) {
            $query->where('requires_approval', true)
                  ->where('is_approved', false);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                  ->orWhere('last_name', 'ILIKE', "%{$search}%")
                  ->orWhere('employee_id', 'ILIKE', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $attendance = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => 'Attendance records retrieved successfully'
        ]);
    }

    /**
     * Store a newly created attendance record
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'date' => 'required|date',
            'check_in_time' => 'nullable|date',
            'check_out_time' => 'nullable|date|after:check_in_time',
            'expected_check_in' => 'nullable|date',
            'expected_check_out' => 'nullable|date|after:expected_check_in',
            'break_start' => 'nullable|date',
            'break_end' => 'nullable|date|after:break_start',
            'break_duration_minutes' => 'nullable|integer|min:0',
            'status' => 'required|in:present,absent,late,half_day,on_leave,holiday',
            'attendance_type' => 'required|in:regular,overtime,weekend,holiday',
            'check_in_location' => 'nullable|string',
            'check_out_location' => 'nullable|string',
            'check_in_device' => 'nullable|string',
            'check_out_device' => 'nullable|string',
            'check_in_latitude' => 'nullable|numeric',
            'check_in_longitude' => 'nullable|numeric',
            'check_out_latitude' => 'nullable|numeric',
            'check_out_longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'admin_notes' => 'nullable|string',
            'requires_approval' => 'boolean',
            'metadata' => 'nullable|array'
        ]);

        // Check if attendance record already exists for this employee and date
        $existingAttendance = Attendance::where('employee_id', $validated['employee_id'])
                                      ->whereDate('date', $validated['date'])
                                      ->first();

        if ($existingAttendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record already exists for this date',
                'errors' => ['date' => ['Attendance record already exists for this date']]
            ], 422);
        }

        $attendance = Attendance::create($validated);

        // Calculate time fields automatically
        $attendance->calculateAllTimes();
        $attendance->save();

        $attendance->load(['employee.user', 'approver']);

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => 'Attendance record created successfully'
        ], 201);
    }

    /**
     * Check in an employee
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'location' => 'nullable|string',
            'device' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string'
        ]);

        $today = now()->toDateString();
        $checkInTime = now();

        // Check if employee is already checked in today
        $existingAttendance = Attendance::where('employee_id', $validated['employee_id'])
                                      ->whereDate('date', $today)
                                      ->first();

        if ($existingAttendance && $existingAttendance->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'Employee is already checked in today',
                'data' => $existingAttendance
            ], 422);
        }

        // Create or update attendance record
        $attendance = $existingAttendance ?: new Attendance([
            'employee_id' => $validated['employee_id'],
            'date' => $today,
            'status' => 'present'
        ]);

        $attendance->check_in_time = $checkInTime;
        $attendance->check_in_location = $validated['location'] ?? null;
        $attendance->check_in_device = $validated['device'] ?? null;
        $attendance->check_in_latitude = $validated['latitude'] ?? null;
        $attendance->check_in_longitude = $validated['longitude'] ?? null;
        $attendance->notes = $validated['notes'] ?? null;

        // Set expected times (assuming standard 9-5 schedule)
        if (!$attendance->expected_check_in) {
            $attendance->expected_check_in = Carbon::parse($today . ' 09:00:00');
        }
        if (!$attendance->expected_check_out) {
            $attendance->expected_check_out = Carbon::parse($today . ' 17:00:00');
        }

        $attendance->calculateLateMinutes();
        $attendance->save();

        $attendance->load(['employee.user']);

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => 'Employee checked in successfully'
        ]);
    }

    /**
     * Check out an employee
     */
    public function checkOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'location' => 'nullable|string',
            'device' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string'
        ]);

        $today = now()->toDateString();
        $checkOutTime = now();

        // Find today's attendance record
        $attendance = Attendance::where('employee_id', $validated['employee_id'])
                                ->whereDate('date', $today)
                                ->first();

        if (!$attendance || !$attendance->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'Employee must check in first',
                'errors' => ['check_in' => ['Employee must check in first']]
            ], 422);
        }

        if ($attendance->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'Employee is already checked out today',
                'data' => $attendance
            ], 422);
        }

        // Update attendance record with check out information
        $attendance->check_out_time = $checkOutTime;
        $attendance->check_out_location = $validated['location'] ?? null;
        $attendance->check_out_device = $validated['device'] ?? null;
        $attendance->check_out_latitude = $validated['latitude'] ?? null;
        $attendance->check_out_longitude = $validated['longitude'] ?? null;

        if ($validated['notes'] ?? false) {
            $attendance->notes = ($attendance->notes ? $attendance->notes . "\n" : '') . $validated['notes'];
        }

        // Calculate all time fields
        $attendance->calculateAllTimes();
        $attendance->save();

        $attendance->load(['employee.user']);

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => 'Employee checked out successfully'
        ]);
    }

    /**
     * Get today's attendance records
     */
    public function today(Request $request): JsonResponse
    {
        $query = Attendance::with(['employee.user', 'employee.department'])
                          ->whereDate('date', today());

        // Filter by department
        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendance = $query->orderBy('check_in_time')->get();

        $summary = [
            'total_employees' => Employee::where('employment_status', 'active')->count(),
            'checked_in' => $attendance->whereNotNull('check_in_time')->count(),
            'present' => $attendance->where('status', 'present')->count(),
            'late' => $attendance->where('status', 'late')->count(),
            'absent' => Employee::where('employment_status', 'active')->count() - $attendance->count(),
            'on_leave' => $attendance->where('status', 'on_leave')->count(),
            'records' => $attendance
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
            'message' => "Today's attendance retrieved successfully"
        ]);
    }

    /**
     * Display the specified attendance record
     */
    public function show(Attendance $attendance): JsonResponse
    {
        $attendance->load(['employee.user', 'employee.department', 'employee.position', 'approver']);

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => 'Attendance record retrieved successfully'
        ]);
    }

    /**
     * Update the specified attendance record
     */
    public function update(Request $request, Attendance $attendance): JsonResponse
    {
        $validated = $request->validate([
            'check_in_time' => 'nullable|date',
            'check_out_time' => 'nullable|date|after:check_in_time',
            'expected_check_in' => 'nullable|date',
            'expected_check_out' => 'nullable|date|after:expected_check_in',
            'break_start' => 'nullable|date',
            'break_end' => 'nullable|date|after:break_start',
            'break_duration_minutes' => 'nullable|integer|min:0',
            'status' => 'required|in:present,absent,late,half_day,on_leave,holiday',
            'attendance_type' => 'required|in:regular,overtime,weekend,holiday',
            'notes' => 'nullable|string',
            'admin_notes' => 'nullable|string',
            'requires_approval' => 'boolean',
            'metadata' => 'nullable|array'
        ]);

        $attendance->update($validated);

        // Recalculate time fields
        $attendance->calculateAllTimes();
        $attendance->save();

        $attendance->load(['employee.user', 'approver']);

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => 'Attendance record updated successfully'
        ]);
    }

    /**
     * Remove the specified attendance record
     */
    public function destroy(Attendance $attendance): JsonResponse
    {
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance record deleted successfully'
        ]);
    }

    /**
     * Approve attendance record
     */
    public function approve(Request $request, Attendance $attendance): JsonResponse
    {
        if (!$attendance->requires_approval) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record does not require approval'
            ], 422);
        }

        $validated = $request->validate([
            'admin_notes' => 'nullable|string'
        ]);

        $attendance->update([
            'is_approved' => true,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'admin_notes' => $validated['admin_notes'] ?? null
        ]);

        $attendance->load(['employee.user', 'approver']);

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => 'Attendance record approved successfully'
        ]);
    }

    /**
     * Get attendance statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $query = Attendance::whereBetween('date', [$startDate, $endDate]);

        $stats = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'total_records' => $query->count(),
            'status_breakdown' => $query->selectRaw('status, count(*) as count')
                                      ->groupBy('status')
                                      ->pluck('count', 'status'),
            'attendance_type_breakdown' => $query->selectRaw('attendance_type, count(*) as count')
                                                ->groupBy('attendance_type')
                                                ->pluck('count', 'attendance_type'),
            'average_work_hours' => round($query->avg('total_work_minutes') / 60, 2),
            'total_overtime_hours' => round($query->sum('overtime_minutes') / 60, 2),
            'late_arrivals' => $query->where('late_minutes', '>', 0)->count(),
            'early_departures' => $query->where('early_departure_minutes', '>', 0)->count(),
            'pending_approvals' => Attendance::where('requires_approval', true)
                                            ->where('is_approved', false)
                                            ->count(),
            'daily_attendance' => $query->selectRaw('DATE(date) as date, count(*) as count')
                                      ->groupBy('date')
                                      ->orderBy('date')
                                      ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Attendance statistics retrieved successfully'
        ]);
    }

    /**
     * Get live attendance data for real-time monitoring
     */
    public function live(Request $request): JsonResponse
    {
        $query = Attendance::with(['employee.user', 'employee.department'])
            ->whereDate('date', now()->toDateString())
            ->whereNotNull('check_in_time');

        // Add employee information and current status
        $attendances = $query->get()->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'employee_id' => $attendance->employee_id,
                'employee_name' => $attendance->employee->user->name ?? $attendance->employee->first_name . ' ' . $attendance->employee->last_name,
                'employee_avatar' => $attendance->employee->avatar_url ?? null,
                'department' => $attendance->employee->department->name ?? 'No Department',
                'position' => $attendance->employee->position->title ?? 'No Position',
                'status' => $attendance->check_out_time ? 'checked_out' : 'checked_in',
                'check_in_time' => $attendance->check_in_time,
                'check_out_time' => $attendance->check_out_time,
                'hours_worked' => $attendance->total_hours,
                'location' => $attendance->location ?? 'Office',
                'last_activity' => $attendance->updated_at
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $attendances,
            'total' => $attendances->count(),
            'message' => 'Live attendance data retrieved successfully'
        ]);
    }

    /**
     * Get attendance history with filters
     */
    public function history(Request $request): JsonResponse
    {
        $query = Attendance::with(['employee.user', 'employee.department']);

        // Apply filters
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Default to last 30 days
        if (!$request->filled('date_from') && !$request->filled('date_to')) {
            $query->whereDate('date', '>=', now()->subDays(30));
        }

        $perPage = $request->get('per_page', 25);
        $attendances = $query->orderBy('date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $attendances,
            'message' => 'Attendance history retrieved successfully'
        ]);
    }

    /**
     * Get current user's attendance status
     */
    public function currentStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found'
            ], 404);
        }

        $today = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $isCheckedIn = $today && $today->check_in_time && !$today->check_out_time;

        return response()->json([
            'success' => true,
            'data' => [
                'is_checked_in' => $isCheckedIn,
                'attendance_record' => $today
            ],
            'message' => 'Current status retrieved successfully'
        ]);
    }

    /**
     * Get attendance summary for reporting
     */
    public function summary(Request $request): JsonResponse
    {
        $query = Attendance::with(['employee.user', 'employee.department']);

        // Apply date filters
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Default to current month
        if (!$request->filled('date_from') && !$request->filled('date_to')) {
            $query->whereMonth('date', now()->month)
                  ->whereYear('date', now()->year);
        }

        $attendances = $query->get();

        // Group by employee and calculate summary
        $summary = $attendances->groupBy('employee_id')->map(function ($empAttendances) {
            $employee = $empAttendances->first()->employee;
            $totalDays = $empAttendances->count();
            $presentDays = $empAttendances->where('status', 'present')->count();
            $absentDays = $empAttendances->where('status', 'absent')->count();
            $totalHours = $empAttendances->sum('total_hours');
            $overtime = $empAttendances->sum('overtime_hours');

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->user->name ?? $employee->first_name . ' ' . $employee->last_name,
                'department' => $employee->department->name ?? 'No Department',
                'total_days_present' => $presentDays,
                'total_days_absent' => $absentDays,
                'total_hours_worked' => round($totalHours, 2),
                'average_daily_hours' => $totalDays > 0 ? round($totalHours / $totalDays, 2) : 0,
                'total_overtime' => round($overtime, 2),
                'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $summary,
            'total' => $summary->count(),
            'message' => 'Attendance summary retrieved successfully'
        ]);
    }

    /**
     * Export attendance data
     */
    public function export(Request $request): JsonResponse
    {
        // This would typically generate and return a file
        // For now, return a message indicating the feature
        return response()->json([
            'success' => true,
            'message' => 'Export functionality would be implemented here',
            'download_url' => '/exports/attendance_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        ]);
    }
}