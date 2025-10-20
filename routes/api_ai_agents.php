<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AIAgentsController;

/*
|--------------------------------------------------------------------------
| AI Agents API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for AI agents functionality.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

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

// Public routes (if needed for health checks, etc.)
Route::prefix('ai-agents/public')->group(function () {
    Route::get('/status', [AIAgentsController::class, 'getSystemHealth']);
});