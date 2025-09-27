<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\WorkflowController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum');
    
    // Email verification
    Route::post('email/verification-notification', [AuthController::class, 'sendEmailVerification'])->middleware('auth:sanctum');
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
    
    // Phone verification
    Route::post('phone/verification-notification', [AuthController::class, 'sendPhoneVerification'])->middleware('auth:sanctum');
    Route::post('phone/verify', [AuthController::class, 'verifyPhone'])->middleware('auth:sanctum');
    
    // Two-factor authentication
    Route::post('two-factor/enable', [AuthController::class, 'enableTwoFactor'])->middleware('auth:sanctum');
    Route::post('two-factor/disable', [AuthController::class, 'disableTwoFactor'])->middleware('auth:sanctum');
    Route::post('two-factor/confirm', [AuthController::class, 'confirmTwoFactor'])->middleware('auth:sanctum');
    Route::post('two-factor/recovery-codes', [AuthController::class, 'generateRecoveryCodes'])->middleware('auth:sanctum');
    
    // Password reset
    Route::post('password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('password/reset', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'version' => '1.0.0',
    ]);
});

// Protected API routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // User profile
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::put('profile', [UserController::class, 'updateProfile']);
        Route::post('avatar', [UserController::class, 'uploadAvatar']);
        Route::delete('avatar', [UserController::class, 'deleteAvatar']);
        Route::put('locale', [UserController::class, 'updateLocale']);
        Route::put('timezone', [UserController::class, 'updateTimezone']);
    });
    
    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('{notification}', [NotificationController::class, 'destroy']);
    });
    
    // Workflows
    Route::prefix('workflows')->group(function () {
        Route::get('definitions', [WorkflowController::class, 'definitions']);
        Route::post('start', [WorkflowController::class, 'start']);
        Route::get('instances', [WorkflowController::class, 'instances']);
        Route::get('instances/{instance}', [WorkflowController::class, 'show']);
        Route::post('instances/{instance}/cancel', [WorkflowController::class, 'cancel']);
        
        Route::prefix('steps')->group(function () {
            Route::get('pending', [WorkflowController::class, 'pendingSteps']);
            Route::get('{step}', [WorkflowController::class, 'showStep']);
            Route::post('{step}/assign', [WorkflowController::class, 'assignStep']);
            Route::post('{step}/action', [WorkflowController::class, 'recordAction']);
        });
        
        Route::get('stats', [WorkflowController::class, 'stats']);
    });
    
    // File management
    Route::prefix('files')->group(function () {
        Route::post('upload', [FileController::class, 'upload']);
        Route::post('upload-multiple', [FileController::class, 'uploadMultiple']);
        Route::post('upload-image', [FileController::class, 'uploadImage']);
        Route::delete('{file}', [FileController::class, 'delete']);
        Route::get('{file}/download', [FileController::class, 'download']);
    });
    
    // Company routes
    Route::apiResource('companies', CompanyController::class);
    Route::get('companies/{company}/statistics', [CompanyController::class, 'statistics']);
    
    // Project routes
    Route::apiResource('projects', ProjectController::class);
    Route::get('projects/{project}/dashboard', [ProjectController::class, 'dashboard']);
    Route::patch('projects/{project}/status', [ProjectController::class, 'updateStatus']);
    
    // Task routes
    Route::apiResource('tasks', TaskController::class);
    Route::patch('tasks/{task}/complete', [TaskController::class, 'complete']);
    Route::patch('tasks/{task}/assign', [TaskController::class, 'assign']);
    Route::get('my-tasks', [TaskController::class, 'myTasks']);
    
    // Dashboard routes
    Route::get('dashboard/overview', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'user' => $user->load(['company', 'roles']),
            'stats' => [
                'my_tasks' => $user->assignedTasks()->pending()->count(),
                'overdue_tasks' => $user->assignedTasks()->overdue()->count(),
                'completed_today' => $user->assignedTasks()
                    ->whereDate('completed_at', today())
                    ->count(),
                'my_projects' => $user->projects()->active()->count(),
            ],
            'recent_tasks' => $user->assignedTasks()
                ->with(['project'])
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get(),
        ]);
    });
    
    // Reports routes
    Route::prefix('reports')->group(function () {
        Route::get('tasks/summary', function (Request $request) {
            $user = $request->user();
            $companyId = $user->company_id;
            
            $data = [
                'total_tasks' => \App\Models\Task::whereHas('project', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->count(),
                'completed_tasks' => \App\Models\Task::whereHas('project', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->completed()->count(),
                'overdue_tasks' => \App\Models\Task::whereHas('project', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->overdue()->count(),
                'tasks_by_status' => \App\Models\Task::whereHas('project', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            ];
            
            return response()->json($data);
        });
        
        Route::get('projects/summary', function (Request $request) {
            $user = $request->user();
            $companyId = $user->company_id;
            
            $data = [
                'total_projects' => \App\Models\Project::where('company_id', $companyId)->count(),
                'active_projects' => \App\Models\Project::where('company_id', $companyId)->active()->count(),
                'completed_projects' => \App\Models\Project::where('company_id', $companyId)->completed()->count(),
                'overdue_projects' => \App\Models\Project::where('company_id', $companyId)
                    ->where('end_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
                'projects_by_status' => \App\Models\Project::where('company_id', $companyId)
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
            ];
            
            return response()->json($data);
        });
    });
    
    // HR Management System API Routes
    Route::prefix('hr')->group(function () {
        
        // Department routes
        Route::prefix('departments')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'store']);
            Route::get('/tree', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'tree']);
            Route::get('/list', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'list']);
            Route::get('/hierarchy', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'hierarchy']);
            Route::get('/statistics', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'statistics']);
            Route::get('/{department}', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'show']);
            Route::put('/{department}', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'update']);
            Route::delete('/{department}', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'destroy']);
            Route::get('/{department}/employees', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'employees']);
        });
        
        // Position routes
        Route::prefix('positions')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\HR\PositionController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\HR\PositionController::class, 'store']);
            Route::get('/statistics', [\App\Http\Controllers\Api\HR\PositionController::class, 'statistics']);
            Route::get('/{position}', [\App\Http\Controllers\Api\HR\PositionController::class, 'show']);
            Route::put('/{position}', [\App\Http\Controllers\Api\HR\PositionController::class, 'update']);
            Route::delete('/{position}', [\App\Http\Controllers\Api\HR\PositionController::class, 'destroy']);
        });
        
        // Employee routes
        Route::prefix('employees')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'store']);
            Route::get('/list', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'list']);
            Route::get('/statistics', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'statistics']);
            Route::get('/{employee}', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'show']);
            Route::put('/{employee}', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'update']);
            Route::delete('/{employee}', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'destroy']);
            Route::post('/{employee}/restore', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'restore']);
            Route::get('/{employee}/attendance', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'attendance']);
        });
        
        // Attendance routes
        Route::prefix('attendance')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'store']);
            Route::get('/live', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'live']);
            Route::get('/history', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'history']);
            Route::get('/current-status', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'currentStatus']);
            Route::get('/summary', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'summary']);
            Route::get('/export', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'export']);
            Route::post('/check-in', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'checkIn']);
            Route::post('/check-out', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'checkOut']);
            Route::get('/today', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'today']);
            Route::get('/statistics', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'statistics']);
            Route::get('/{attendance}', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'show']);
            Route::put('/{attendance}', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'update']);
            Route::delete('/{attendance}', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'destroy']);
            Route::post('/{attendance}/approve', [\App\Http\Controllers\Api\HR\AttendanceController::class, 'approve']);
        });
        
        // Leave Request routes
        Route::prefix('leave-requests')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\HR\LeaveRequestController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\HR\LeaveRequestController::class, 'store']);
            Route::post('/draft', [\App\Http\Controllers\Api\HR\LeaveRequestController::class, 'saveDraft']);
            Route::get('/pending', [\App\Http\Controllers\Api\HR\LeaveRequestController::class, 'pending']);
            Route::get('/statistics', [\App\Http\Controllers\Api\HR\LeaveRequestController::class, 'statistics']);
            Route::get('/{leaveRequest}', [\App\Http\Controllers\Api\HR\LeaveRequestController::class, 'show']);
            Route::put('/{leaveRequest}', [\App\Http\Controllers\Api\HR\LeaveRequestController::class, 'update']);
            Route::delete('/{leaveRequest}', [\App\Http\Controllers\Api\HR\LeaveRequestController::class, 'destroy']);
            Route::post('/{leaveRequest}/approve', [\App\Http\Controllers\Api\HR\LeaveRequestController::class, 'approve']);
            Route::post('/{leaveRequest}/reject', [\App\Http\Controllers\Api\HR\LeaveRequestController::class, 'reject']);
        });
        
        // Reports routes
        Route::prefix('reports')->group(function () {
            Route::get('/dashboard-kpis', [\App\Http\Controllers\Api\HR\ReportController::class, 'dashboardKpis']);
            Route::get('/attendance-trends', [\App\Http\Controllers\Api\HR\ReportController::class, 'attendanceTrends']);
            Route::get('/department-distribution', [\App\Http\Controllers\Api\HR\ReportController::class, 'departmentDistribution']);
            Route::get('/employee-performance', [\App\Http\Controllers\Api\HR\ReportController::class, 'employeePerformance']);
            Route::get('/leave-analysis', [\App\Http\Controllers\Api\HR\ReportController::class, 'leaveAnalysis']);
            Route::get('/attendance-summary', [\App\Http\Controllers\Api\HR\ReportController::class, 'attendanceSummary']);
            Route::get('/employee-demographics', [\App\Http\Controllers\Api\HR\ReportController::class, 'employeeDemographics']);
            Route::get('/leave-types', [\App\Http\Controllers\Api\HR\ReportController::class, 'leaveTypes']);
            Route::get('/export/{type}', [\App\Http\Controllers\Api\HR\ReportController::class, 'export']);
        });
        
        // HR Dashboard
        Route::get('/dashboard', function () {
            $stats = [
                'total_employees' => \App\Models\HR\Employee::where('employment_status', 'active')->count(),
                'total_departments' => \App\Models\HR\Department::where('is_active', true)->count(),
                'present_today' => \App\Models\HR\Attendance::whereDate('date', today())
                    ->where('status', 'present')->count(),
                'pending_leave_requests' => \App\Models\HR\LeaveRequest::where('status', 'pending')->count(),
                'new_hires_this_month' => \App\Models\HR\Employee::whereMonth('hire_date', now()->month)
                    ->whereYear('hire_date', now()->year)->count(),
                'total_payroll' => \App\Models\HR\Employee::where('employment_status', 'active')->sum('salary'),
                'departments_without_manager' => \App\Models\HR\Department::whereNull('manager_id')
                    ->where('is_active', true)->count(),
                'employees_on_leave' => \App\Models\HR\Employee::where('employment_status', 'on_leave')->count(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'HR dashboard data retrieved successfully'
            ]);
        });
    });
    
});
