<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\Department;
use App\Models\HR\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request): JsonResponse
    {
        $query = Employee::with([
            'user',
            'department',
            'position',
            'supervisor.user'
        ]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                  ->orWhere('last_name', 'ILIKE', "%{$search}%")
                  ->orWhere('employee_id', 'ILIKE', "%{$search}%")
                  ->orWhere('personal_email', 'ILIKE', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('email', 'ILIKE', "%{$search}%");
                  });
            });
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by position
        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        // Filter by employment status
        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        } else {
            $query->where('employment_status', 'active'); // Default to active
        }

        // Filter by employment type
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        // Filter by supervisor
        if ($request->filled('supervisor_id')) {
            $query->where('supervisor_id', $request->supervisor_id);
        }

        // Date filters
        if ($request->filled('hire_date_from')) {
            $query->where('hire_date', '>=', $request->hire_date_from);
        }

        if ($request->filled('hire_date_to')) {
            $query->where('hire_date', '<=', $request->hire_date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if ($sortBy === 'name') {
            $query->orderBy('first_name', $sortOrder)
                  ->orderBy('last_name', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $employees = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $employees,
            'message' => 'Employees retrieved successfully'
        ]);
    }

    /**
     * Get employees in simple list format for combo boxes
     */
    public function list(Request $request): JsonResponse
    {
        $employees = Employee::with(['department', 'position'])
            ->where('employment_status', 'active')
            ->select('id', 'first_name', 'last_name', 'employee_id', 'department_id', 'position_id')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Format for ExtJS combo box
        $listData = $employees->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->first_name . ' ' . $emp->last_name,
                'employee_id' => $emp->employee_id,
                'position' => $emp->position ? $emp->position->title : 'No position',
                'department' => $emp->department ? $emp->department->name : 'No department',
                'display_name' => $emp->first_name . ' ' . $emp->last_name . ' (' . ($emp->employee_id ?: 'No ID') . ')'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $listData,
            'message' => 'Employee list retrieved successfully'
        ]);
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Employee basic info
            'employee_id' => 'nullable|string|max:20|unique:hr_employees,employee_id',
            'department_id' => 'required|exists:hr_departments,id',
            'position_id' => 'required|exists:hr_positions,id',
            'supervisor_id' => 'nullable|exists:hr_employees,id',
            
            // Personal information
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'nationality' => 'nullable|string|max:50',
            'national_id' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            
            // Contact information
            'personal_email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:50',
            
            // Employment information
            'hire_date' => 'required|date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'employment_status' => 'required|in:active,inactive,terminated,on_leave',
            'salary' => 'required|numeric|min:0',
            'salary_currency' => 'nullable|string|max:3',
            'salary_type' => 'required|in:monthly,yearly,hourly',
            'hourly_rate' => 'nullable|numeric|min:0',
            
            // Work information
            'work_hours_per_week' => 'nullable|integer|min:1|max:168',
            'work_location' => 'nullable|string|max:100',
            'remote_work_allowed' => 'boolean',
            
            // Leave information
            'vacation_days_per_year' => 'nullable|integer|min:0',
            'sick_days_per_year' => 'nullable|integer|min:0',
            
            // User account information
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            
            // Additional information
            'notes' => 'nullable|string',
            'skills' => 'nullable|array',
            'certifications' => 'nullable|array',
            'education' => 'nullable|array',
            'metadata' => 'nullable|array'
        ]);

        // Generate employee ID if not provided
        if (!$validated['employee_id']) {
            $validated['employee_id'] = $this->generateEmployeeId();
        }

        // Create user account first
        $user = User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now()
        ]);

        // Remove user-specific fields from employee data
        unset($validated['email'], $validated['password']);
        $validated['user_id'] = $user->id;

        // Create employee record
        $employee = Employee::create($validated);
        $employee->load(['user', 'department', 'position', 'supervisor.user']);

        return response()->json([
            'success' => true,
            'data' => $employee,
            'message' => 'Employee created successfully'
        ], 201);
    }

    /**
     * Display the specified employee
     */
    public function show(Employee $employee): JsonResponse
    {
        $employee->load([
            'user',
            'department.manager',
            'position',
            'supervisor.user',
            'subordinates.user',
            'leaveRequests' => function ($query) {
                $query->latest()->limit(5);
            },
            'evaluations' => function ($query) {
                $query->latest()->limit(3);
            }
        ]);

        // Add calculated attributes
        $employee->age = $employee->age;
        $employee->years_of_service = $employee->years_of_service;
        $employee->remaining_vacation_days = $employee->remaining_vacation_days;
        $employee->remaining_sick_days = $employee->remaining_sick_days;
        $employee->annual_salary = $employee->annual_salary;

        return response()->json([
            'success' => true,
            'data' => $employee,
            'message' => 'Employee retrieved successfully'
        ]);
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            // Employee basic info
            'employee_id' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('hr_employees', 'employee_id')->ignore($employee->id)
            ],
            'department_id' => 'required|exists:hr_departments,id',
            'position_id' => 'required|exists:hr_positions,id',
            'supervisor_id' => [
                'nullable',
                'exists:hr_employees,id',
                Rule::notIn([$employee->id]) // Cannot be supervisor of themselves
            ],
            
            // Personal information
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'nationality' => 'nullable|string|max:50',
            'national_id' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            
            // Contact information
            'personal_email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:50',
            
            // Employment information
            'hire_date' => 'required|date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'employment_status' => 'required|in:active,inactive,terminated,on_leave',
            'salary' => 'required|numeric|min:0',
            'salary_currency' => 'nullable|string|max:3',
            'salary_type' => 'required|in:monthly,yearly,hourly',
            'hourly_rate' => 'nullable|numeric|min:0',
            
            // Work information
            'work_hours_per_week' => 'nullable|integer|min:1|max:168',
            'work_location' => 'nullable|string|max:100',
            'remote_work_allowed' => 'boolean',
            
            // Leave information
            'vacation_days_per_year' => 'nullable|integer|min:0',
            'sick_days_per_year' => 'nullable|integer|min:0',
            'vacation_days_used' => 'nullable|integer|min:0',
            'sick_days_used' => 'nullable|integer|min:0',
            
            // User account information
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($employee->user_id)
            ],
            
            // Additional information
            'notes' => 'nullable|string',
            'skills' => 'nullable|array',
            'certifications' => 'nullable|array',
            'education' => 'nullable|array',
            'metadata' => 'nullable|array'
        ]);

        // Update user email if changed
        if (isset($validated['email']) && $validated['email'] !== $employee->user->email) {
            $employee->user->update(['email' => $validated['email']]);
        }

        // Update user name if name changed
        $newName = $validated['first_name'] . ' ' . $validated['last_name'];
        if ($newName !== $employee->user->name) {
            $employee->user->update(['name' => $newName]);
        }

        // Remove user-specific fields from employee data
        unset($validated['email']);

        // Update employee record
        $employee->update($validated);
        $employee->load(['user', 'department', 'position', 'supervisor.user']);

        return response()->json([
            'success' => true,
            'data' => $employee,
            'message' => 'Employee updated successfully'
        ]);
    }

    /**
     * Remove the specified employee
     */
    public function destroy(Employee $employee): JsonResponse
    {
        // Instead of hard delete, mark as terminated
        $employee->update([
            'employment_status' => 'terminated',
            'contract_end_date' => now()->toDateString()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee terminated successfully'
        ]);
    }

    /**
     * Get employee statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('employment_status', 'active')->count(),
            'new_hires_this_month' => Employee::whereMonth('hire_date', now()->month)
                                            ->whereYear('hire_date', now()->year)->count(),
            'employees_by_status' => Employee::selectRaw('employment_status, count(*) as count')
                                           ->groupBy('employment_status')->pluck('count', 'employment_status'),
            'employees_by_type' => Employee::selectRaw('employment_type, count(*) as count')
                                         ->groupBy('employment_type')->pluck('count', 'employment_type'),
            'employees_by_department' => Employee::join('hr_departments', 'hr_employees.department_id', '=', 'hr_departments.id')
                                               ->selectRaw('hr_departments.name as department, count(*) as count')
                                               ->where('hr_employees.employment_status', 'active')
                                               ->groupBy('hr_departments.name')
                                               ->pluck('count', 'department'),
            'average_salary' => Employee::where('employment_status', 'active')->avg('salary'),
            'total_payroll' => Employee::where('employment_status', 'active')->sum('salary')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Employee statistics retrieved successfully'
        ]);
    }

    /**
     * Get employee attendance summary
     */
    public function attendance(Employee $employee, Request $request): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $attendance = $employee->attendance()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get();

        $summary = [
            'total_days' => $attendance->count(),
            'present_days' => $attendance->where('status', 'present')->count(),
            'absent_days' => $attendance->where('status', 'absent')->count(),
            'late_days' => $attendance->where('status', 'late')->count(),
            'half_days' => $attendance->where('status', 'half_day')->count(),
            'total_work_hours' => round($attendance->sum('total_work_minutes') / 60, 2),
            'total_overtime_hours' => round($attendance->sum('overtime_minutes') / 60, 2),
            'attendance_records' => $attendance
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
            'message' => 'Employee attendance retrieved successfully'
        ]);
    }

    /**
     * Generate unique employee ID
     */
    private function generateEmployeeId(): string
    {
        $prefix = 'EMP';
        $year = now()->year;
        
        // Get next sequence number
        $lastEmployee = Employee::where('employee_id', 'LIKE', "{$prefix}{$year}%")
                               ->orderBy('employee_id', 'desc')
                               ->first();
        
        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_id, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . $year . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Restore terminated employee
     */
    public function restore(Employee $employee): JsonResponse
    {
        if ($employee->employment_status !== 'terminated') {
            return response()->json([
                'success' => false,
                'message' => 'Employee is not terminated'
            ], 422);
        }

        $employee->update([
            'employment_status' => 'active',
            'contract_end_date' => null
        ]);

        return response()->json([
            'success' => true,
            'data' => $employee,
            'message' => 'Employee restored successfully'
        ]);
    }
}