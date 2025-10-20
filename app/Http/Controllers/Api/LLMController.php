<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\LLM\LLMManager;
use App\Services\LLM\Models\LLMRequest;
use App\Services\LLM\Exceptions\LLMException;
use App\Services\AIAgentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LLMController extends Controller
{
    protected LLMManager $llmManager;
    protected AIAgentService $aiAgentService;

    public function __construct(LLMManager $llmManager, AIAgentService $aiAgentService)
    {
        $this->llmManager = $llmManager;
        $this->aiAgentService = $aiAgentService;
    }

    /**
     * Get all available providers
     */
    public function getProviders(): JsonResponse
    {
        try {
            $providers = $this->llmManager->getProvidersStatus();
            
            return response()->json([
                'success' => true,
                'providers' => $providers,
                'total_providers' => count($providers),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific provider status
     */
    public function getProviderStatus(string $provider): JsonResponse
    {
        try {
            $providerInstance = $this->llmManager->getProvider($provider);
            
            if (!$providerInstance) {
                return response()->json([
                    'success' => false,
                    'error' => "Provider '{$provider}' not found",
                ], 404);
            }

            $status = $providerInstance->healthCheck();
            $models = $providerInstance->getAvailableModels();
            $capabilities = $providerInstance->getCapabilities();

            return response()->json([
                'success' => true,
                'provider' => $provider,
                'status' => $status,
                'models' => $models,
                'capabilities' => $capabilities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available models
     */
    public function getAvailableModels(Request $request): JsonResponse
    {
        try {
            $provider = $request->query('provider');
            $models = $this->llmManager->getAvailableModels($provider);
            
            return response()->json([
                'success' => true,
                'models' => $models,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create text completion
     */
    public function createCompletion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:50000',
            'model' => 'nullable|string',
            'provider' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:32000',
            'system_prompt' => 'nullable|string|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $llmRequest = LLMRequest::completion($request->input('prompt'), [
                'model' => $request->input('model'),
                'temperature' => $request->input('temperature', 0.7),
                'max_tokens' => $request->input('max_tokens'),
                'system_prompt' => $request->input('system_prompt'),
            ]);

            $response = $this->llmManager->complete($llmRequest, $request->input('provider'));

            return response()->json([
                'success' => true,
                'response' => $response->toArray(),
            ]);
        } catch (LLMException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $e->getProvider(),
                'model' => $e->getModel(),
            ], 500);
        }
    }

    /**
     * Create chat completion
     */
    public function createChatCompletion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'messages' => 'required|array|max:100',
            'messages.*.role' => 'required|string|in:system,user,assistant',
            'messages.*.content' => 'required|string|max:10000',
            'model' => 'nullable|string',
            'provider' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:32000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $llmRequest = LLMRequest::chat($request->input('messages'), [
                'model' => $request->input('model'),
                'temperature' => $request->input('temperature', 0.7),
                'max_tokens' => $request->input('max_tokens'),
            ]);

            $response = $this->llmManager->complete($llmRequest, $request->input('provider'));

            return response()->json([
                'success' => true,
                'response' => $response->toArray(),
            ]);
        } catch (LLMException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $e->getProvider(),
                'model' => $e->getModel(),
            ], 500);
        }
    }

    /**
     * Execute function calling
     */
    public function executeFunctionCall(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'messages' => 'required|array|max:100',
            'functions' => 'required|array|max:50',
            'model' => 'nullable|string',
            'provider' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $llmRequest = LLMRequest::functionCall(
                $request->input('messages'),
                $request->input('functions'),
                [
                    'model' => $request->input('model'),
                    'temperature' => $request->input('temperature', 0.3),
                ]
            );

            $response = $this->llmManager->complete($llmRequest, $request->input('provider'));

            return response()->json([
                'success' => true,
                'response' => $response->toArray(),
                'has_function_call' => $response->hasFunctionCall(),
                'function_call' => $response->getFunctionCall(),
            ]);
        } catch (LLMException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $e->getProvider(),
                'model' => $e->getModel(),
            ], 500);
        }
    }

    /**
     * Stream completion
     */
    public function streamCompletion(Request $request): StreamedResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:50000',
            'model' => 'nullable|string',
            'provider' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        return response()->stream(function () use ($request) {
            try {
                $llmRequest = LLMRequest::completion($request->input('prompt'), [
                    'model' => $request->input('model'),
                    'temperature' => $request->input('temperature', 0.7),
                ]);

                $this->llmManager->stream($llmRequest, function ($chunk) {
                    echo "data: " . json_encode([
                        'content' => $chunk->getContent(),
                        'model' => $chunk->getModel(),
                        'provider' => $chunk->getProvider(),
                    ]) . "\n\n";
                    
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }, $request->input('provider'));

                echo "data: [DONE]\n\n";
            } catch (LLMException $e) {
                echo "data: " . json_encode([
                    'error' => $e->getMessage(),
                    'provider' => $e->getProvider(),
                ]) . "\n\n";
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Agent-specific completion
     */
    public function agentCompletion(Request $request, string $agent): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:50000',
            'context' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $response = $this->aiAgentService->generateCompletion(
                $request->input('prompt'),
                $agent,
                [
                    'context' => $request->input('context', []),
                    'temperature' => $request->input('temperature', 0.7),
                    'max_tokens' => $request->input('max_tokens', 1000),
                ]
            );

            return response()->json([
                'success' => true,
                'agent' => $agent,
                'response' => $response->toArray(),
            ]);
        } catch (LLMException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'agent' => $agent,
            ], 500);
        }
    }

    /**
     * Process HR query
     */
    public function processHRQuery(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:10000',
            'context' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->aiAgentService->generateHRResponse(
                $request->input('query'),
                $request->input('context', [])
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze employee data
     */
    public function analyzeEmployeeData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'analysis_type' => 'nullable|string|in:general,performance,engagement,productivity',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->aiAgentService->analyzeEmployeeData(
                $request->input('data'),
                $request->input('analysis_type', 'general')
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate automated report
     */
    public function generateReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:weekly_summary,performance_review,compliance_audit,recruitment_analysis',
            'data' => 'required|array',
            'options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->aiAgentService->generateAutomatedReport(
                $request->input('type'),
                $request->input('data'),
                $request->input('options', [])
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get usage statistics
     */
    public function getUsageStatistics(Request $request): JsonResponse
    {
        try {
            $days = $request->query('days', 7);
            $stats = $this->llmManager->getUsageStatistics($days);

            return response()->json([
                'success' => true,
                'statistics' => $stats,
                'period_days' => $days,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cost analysis
     */
    public function getCostAnalysis(Request $request): JsonResponse
    {
        try {
            $days = $request->query('days', 30);
            $analysis = $this->llmManager->getCostAnalysis($days);

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
                'period_days' => $days,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system health
     */
    public function getSystemHealth(): JsonResponse
    {
        try {
            $health = $this->llmManager->healthCheck();

            return response()->json([
                'success' => true,
                'health_check' => $health,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pull Ollama model
     */
    public function pullOllamaModel(string $model): JsonResponse
    {
        try {
            $ollamaProvider = $this->llmManager->getProvider('ollama');
            
            if (!$ollamaProvider) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ollama provider not available',
                ], 404);
            }

            $result = $ollamaProvider->pullModel($model);

            return response()->json([
                'success' => $result,
                'model' => $model,
                'message' => $result ? 'Model pulled successfully' : 'Failed to pull model',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test endpoints for development
     */
    public function testProviders(): JsonResponse
    {
        try {
            $providers = $this->llmManager->getProvidersStatus();
            
            return response()->json([
                'success' => true,
                'message' => 'Multi-LLM system is operational',
                'providers_count' => count($providers),
                'healthy_providers' => array_filter($providers, fn($p) => $p['healthy']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Multi-LLM system has issues',
            ], 500);
        }
    }

    /**
     * Test simple completion
     */
    public function testCompletion(Request $request): JsonResponse
    {
        try {
            $prompt = $request->input('prompt', 'Hello, how are you?');
            $provider = $request->input('provider');
            
            $llmRequest = LLMRequest::completion($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 100,
            ]);

            $response = $this->llmManager->complete($llmRequest, $provider);

            return response()->json([
                'success' => true,
                'test_prompt' => $prompt,
                'response' => $response->getContent(),
                'model' => $response->getModel(),
                'provider' => $response->getProvider(),
                'tokens_used' => $response->getTokensUsed(),
                'cost' => $response->getCost(),
                'response_time' => $response->getResponseTime(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test health
     */
    public function testHealth(): JsonResponse
    {
        try {
            $health = $this->llmManager->healthCheck();
            $healthyCount = count(array_filter($health, fn($h) => $h['status'] === 'healthy'));

            return response()->json([
                'success' => true,
                'message' => 'Multi-LLM health check completed',
                'total_providers' => count($health),
                'healthy_providers' => $healthyCount,
                'health_details' => $health,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}