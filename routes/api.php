<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AIAgentsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

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

// AI Agents API Routes
Route::middleware(['auth:sanctum'])->prefix('ai-agents')->group(function () {
    
    // Agent Status and Health
    Route::get('/agents/status', [AIAgentsController::class, 'getAgentsStatus']);
    Route::get('/system/health', [AIAgentsController::class, 'getSystemHealth']);
    Route::post('/system/health-check', [AIAgentsController::class, 'runHealthCheck']);
    Route::post('/system/emergency-shutdown', [AIAgentsController::class, 'emergencyShutdown']);
    
    // Workflow Management
    Route::get('/workflows/active', [AIAgentsController::class, 'getActiveWorkflows']);
    Route::post('/workflows/start', [AIAgentsController::class, 'startWorkflow']);
    Route::post('/workflows/{workflowId}/pause', [AIAgentsController::class, 'pauseWorkflow']);
    Route::get('/workflows/{workflowId}/details', [AIAgentsController::class, 'getWorkflowDetails']);
    
    // Activity and Monitoring
    Route::get('/activity/feed', [AIAgentsController::class, 'getActivityFeed']);
    
    // Employee Query Processing
    Route::post('/queries/process', [AIAgentsController::class, 'processEmployeeQuery']);
});

// Test routes without authentication for testing
Route::prefix('test')->group(function () {
    Route::get('/agents/status', function() {
        return response()->json([
            'success' => true,
            'core_agents' => [
                [
                    'id' => 'hr_001',
                    'name' => 'HR Agent',
                    'type' => 'hr_agent',
                    'status' => 'active',
                    'active_tasks' => 3,
                    'load_percentage' => 45,
                    'iconCls' => 'fa-users',
                    'last_activity' => now()->subMinutes(2)->toISOString()
                ],
                [
                    'id' => 'pm_001',
                    'name' => 'Project Manager Agent',
                    'type' => 'project_manager_agent',
                    'status' => 'active',
                    'active_tasks' => 2,
                    'load_percentage' => 30,
                    'iconCls' => 'fa-tasks',
                    'last_activity' => now()->subMinutes(5)->toISOString()
                ]
            ],
            'specialized_agents' => [
                [
                    'id' => 'it_001',
                    'name' => 'IT Support Agent',
                    'type' => 'it_support_agent',
                    'status' => 'active',
                    'queue_size' => 4,
                    'completed_today' => 12,
                    'iconCls' => 'fa-laptop',
                    'specialization' => 'System Administration'
                ]
            ],
            'last_updated' => now()->toISOString()
        ]);
    });
    
    Route::get('/system/health', function() {
        return response()->json([
            'success' => true,
            'health_data' => [
                'overall_health' => 'healthy',
                'health_percentage' => 92,
                'active_workflows' => 8,
                'healthy_agents' => 11,
                'total_agents' => 12,
                'memory_usage' => 68,
                'avg_response_time' => 150
            ]
        ]);
    });
    
    Route::get('/workflows/active', function() {
        return response()->json([
            'success' => true,
            'workflows' => [
                [
                    'workflow_id' => 'onboard_20251020_143022',
                    'workflow_type' => 'employee_onboarding',
                    'employee_id' => 1,
                    'employee_name' => 'John Doe',
                    'status' => 'active',
                    'progress_percentage' => 65,
                    'started_at' => now()->subHours(2)->toISOString(),
                    'current_step' => 'IT Account Setup',
                    'assigned_agents' => ['HR Agent', 'IT Support Agent'],
                    'priority' => 'medium'
                ]
            ]
        ]);
    });
    
    Route::get('/activity/feed', function() {
        return response()->json([
            'success' => true,
            'activities' => [
                [
                    'id' => 1,
                    'timestamp' => now()->subMinutes(5)->toISOString(),
                    'type' => 'workflow_started',
                    'title' => 'Employee Onboarding Started',
                    'description' => 'New hire onboarding workflow initiated for John Doe',
                    'agent_name' => 'HR Agent',
                    'iconCls' => 'fa-user-plus',
                    'severity' => 'info'
                ]
            ]
        ]);
    });
    
    Route::get('/dashboard', function() {
        try {
            return view('ai-agents.dashboard');
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});