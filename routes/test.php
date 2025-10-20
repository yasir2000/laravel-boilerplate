<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AIAgentsController;

/*
|--------------------------------------------------------------------------
| Test Routes for AI Agents
|--------------------------------------------------------------------------
*/

// Test routes without authentication for testing purposes
Route::prefix('test')->group(function () {
    Route::get('/agents/status', function() {
        $controller = new AIAgentsController(app(\App\Services\AIAgentService::class));
        return $controller->getAgentsStatus();
    });
    
    Route::get('/system/health', function() {
        $controller = new AIAgentsController(app(\App\Services\AIAgentService::class));
        return $controller->getSystemHealth();
    });
    
    Route::get('/workflows/active', function() {
        $controller = new AIAgentsController(app(\App\Services\AIAgentService::class));
        return $controller->getActiveWorkflows();
    });
    
    Route::get('/activity/feed', function() {
        $controller = new AIAgentsController(app(\App\Services\AIAgentService::class));
        return $controller->getActivityFeed();
    });
    
    Route::get('/dashboard', function() {
        return view('ai-agents.dashboard');
    });
});