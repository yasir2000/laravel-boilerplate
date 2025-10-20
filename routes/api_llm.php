<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\LLMController;

/*
|--------------------------------------------------------------------------
| LLM API Routes
|--------------------------------------------------------------------------
|
| Multi-LLM system API routes for managing and interacting with various
| language model providers including OpenAI, Anthropic, Google, Mistral, and Ollama
|
*/

Route::middleware(['auth:sanctum'])->prefix('llm')->group(function () {
    
    // Provider Management
    Route::get('/providers', [LLMController::class, 'getProviders']);
    Route::get('/providers/{provider}/status', [LLMController::class, 'getProviderStatus']);
    Route::post('/providers/{provider}/health-check', [LLMController::class, 'performHealthCheck']);
    
    // Model Management
    Route::get('/models', [LLMController::class, 'getAvailableModels']);
    Route::get('/models/{provider}', [LLMController::class, 'getProviderModels']);
    Route::get('/models/{provider}/{model}/info', [LLMController::class, 'getModelInfo']);
    
    // Completions
    Route::post('/completions', [LLMController::class, 'createCompletion']);
    Route::post('/chat/completions', [LLMController::class, 'createChatCompletion']);
    Route::post('/function-calling', [LLMController::class, 'executeFunctionCall']);
    Route::post('/batch-completions', [LLMController::class, 'batchCompletions']);
    
    // Streaming (requires special handling)
    Route::post('/stream/completions', [LLMController::class, 'streamCompletion']);
    Route::post('/stream/chat', [LLMController::class, 'streamChat']);
    
    // Agent-specific completions
    Route::post('/agents/{agent}/completion', [LLMController::class, 'agentCompletion']);
    Route::post('/agents/{agent}/chat', [LLMController::class, 'agentChatCompletion']);
    
    // HR-specific AI features
    Route::post('/hr/query', [LLMController::class, 'processHRQuery']);
    Route::post('/hr/analysis', [LLMController::class, 'analyzeEmployeeData']);
    Route::post('/hr/report', [LLMController::class, 'generateReport']);
    Route::post('/hr/natural-query', [LLMController::class, 'processNaturalLanguageQuery']);
    
    // Monitoring & Analytics
    Route::get('/usage/statistics', [LLMController::class, 'getUsageStatistics']);
    Route::get('/usage/cost-analysis', [LLMController::class, 'getCostAnalysis']);
    Route::get('/monitoring/metrics', [LLMController::class, 'getMetrics']);
    Route::get('/monitoring/health', [LLMController::class, 'getSystemHealth']);
    
    // Cache Management
    Route::post('/cache/clear', [LLMController::class, 'clearCache']);
    Route::get('/cache/statistics', [LLMController::class, 'getCacheStatistics']);
    Route::post('/cache/warmup', [LLMController::class, 'warmupCache']);
    
    // Ollama-specific endpoints
    Route::post('/ollama/pull/{model}', [LLMController::class, 'pullOllamaModel']);
    Route::delete('/ollama/remove/{model}', [LLMController::class, 'removeOllamaModel']);
    Route::get('/ollama/running', [LLMController::class, 'getRunningOllamaModels']);
    
    // Cost Management
    Route::get('/cost/budget-status', [LLMController::class, 'getBudgetStatus']);
    Route::post('/cost/set-limits', [LLMController::class, 'setCostLimits']);
    Route::get('/cost/alerts', [LLMController::class, 'getCostAlerts']);
    
    // Configuration
    Route::get('/config', [LLMController::class, 'getConfig']);
    Route::post('/config/agent-mapping', [LLMController::class, 'updateAgentMapping']);
    Route::post('/config/load-balancing', [LLMController::class, 'updateLoadBalancing']);
});

// Public test endpoints (no authentication required)
Route::prefix('test/llm')->group(function () {
    Route::get('/providers', [LLMController::class, 'testProviders']);
    Route::post('/simple-completion', [LLMController::class, 'testCompletion']);
    Route::get('/health', [LLMController::class, 'testHealth']);
});