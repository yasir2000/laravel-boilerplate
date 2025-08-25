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
});
