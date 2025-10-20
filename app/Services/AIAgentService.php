<?php

namespace App\Services;

use App\Services\LLM\LLMManager;
use App\Services\LLM\Models\LLMRequest;
use App\Services\LLM\Models\LLMResponse;
use App\Services\LLM\Exceptions\LLMException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIAgentService
{
    protected $baseUrl;
    protected $timeout;
    protected LLMManager $llmManager;
    
    public function __construct(LLMManager $llmManager = null)
    {
        $this->baseUrl = config('ai_agents.base_url', 'http://localhost:8001');
        $this->timeout = config('ai_agents.timeout', 30);
        $this->llmManager = $llmManager ?? new LLMManager();
    }
    
    /**
     * Check if AI agent system is healthy
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/health");
            
            return $response->json();
        } catch (RequestException $e) {
            Log::error('AI Agent health check failed: ' . $e->getMessage());
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get status of all available agents
     */
    public function getAgentsStatus(): array
    {
        try {
            // Check cache first
            $cacheKey = 'agents_status';
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                return $cached;
            }

            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/agents/status");
            
            if ($response->successful()) {
                $data = $response->json();
                Cache::put($cacheKey, $data, 60); // Cache for 1 minute
                return $data;
            }

            // Fallback to mock data if service unavailable
            return $this->getMockAgentsStatus();

        } catch (RequestException $e) {
            Log::error('Failed to get agents status: ' . $e->getMessage());
            return $this->getMockAgentsStatus();
        }
    }
    
    /**
     * Execute a generic agent task
     */
    public function executeAgentTask(string $agentType, string $task, array $data = [], string $priority = 'normal'): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/agents/execute-task", [
                    'agent_type' => $agentType,
                    'task' => $task,
                    'data' => $data,
                    'priority' => $priority
                ]);
            
            return $response->json();
        } catch (RequestException $e) {
            Log::error("Failed to execute agent task: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get task status by task ID
     */
    public function getTaskStatus(string $taskId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/tasks/{$taskId}/status");
            
            return $response->json();
        } catch (RequestException $e) {
            Log::error("Failed to get task status: " . $e->getMessage());
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process employee onboarding using HR agent
     */
    public function processEmployeeOnboarding(array $employeeData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/hr/onboard-employee", $employeeData);
            
            $result = $response->json();
            
            // Cache the result for quick access
            if (isset($result['workflow_id'])) {
                Cache::put("onboarding_workflow_{$employeeData['employee_id']}", $result, 3600);
            }
            
            return $result;
        } catch (RequestException $e) {
            Log::error("Employee onboarding failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process leave request using HR agent
     */
    public function processLeaveRequest(array $leaveData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/hr/process-leave-request", $leaveData);
            
            $result = $response->json();
            
            // Store workflow ID for tracking
            if (isset($result['workflow_id'])) {
                Cache::put("leave_workflow_{$leaveData['leave_request_id']}", $result, 3600);
            }
            
            return $result;
        } catch (RequestException $e) {
            Log::error("Leave request processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Optimize project resources using Project agent
     */
    public function optimizeProjectResources(int $projectId): array 
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/projects/optimize-resources", [
                    'project_id' => $projectId,
                    'optimization_type' => 'resource_allocation'
                ]);
            
            return $response->json();
        } catch (RequestException $e) {
            Log::error("Project optimization failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Generate employee analytics using Analytics agent
     */
    public function generateEmployeeAnalytics(string $timePeriod = 'last_30_days', array $filters = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/analytics/employee-report", [
                    'report_type' => 'employee_analytics',
                    'time_period' => $timePeriod,
                    'filters' => $filters
                ]);
            
            $result = $response->json();
            
            // Cache analytics results for performance
            $cacheKey = "employee_analytics_{$timePeriod}_" . md5(json_encode($filters));
            Cache::put($cacheKey, $result, 1800); // 30 minutes
            
            return $result;
        } catch (RequestException $e) {
            Log::error("Analytics generation failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Execute database query through agents
     */
    public function executeDatabaseQuery(string $query, array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/database/query", [
                    'query' => $query,
                    'params' => $params
                ]);
            
            return $response->json();
        } catch (RequestException $e) {
            Log::error("Database query failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Execute a specific tool directly
     */
    public function executeTool(string $toolName, array $params): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/tools/{$toolName}/execute", $params);
            
            return $response->json();
        } catch (RequestException $e) {
            Log::error("Tool execution failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Trigger automated workflow based on model events
     */
    public function triggerAutomatedWorkflow(string $eventType, string $modelType, int $modelId, array $data = []): array
    {
        $workflowMappings = [
            'employee.created' => 'hr_agent',
            'leave_request.created' => 'hr_agent', 
            'project.created' => 'project_agent',
            'task.assigned' => 'project_agent',
            'performance_review.due' => 'hr_agent'
        ];
        
        $agentType = $workflowMappings[$eventType] ?? null;
        
        if (!$agentType) {
            return ['success' => false, 'error' => "No agent mapped for event: {$eventType}"];
        }
        
        $taskData = array_merge($data, [
            'event_type' => $eventType,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'triggered_at' => now()->toISOString()
        ]);
        
        return $this->executeAgentTask($agentType, "handle_model_event", $taskData);
    }
    
    /**
     * Generate AI completion using multi-LLM system
     */
    public function generateCompletion(string $prompt, string $agentName = null, array $options = []): LLMResponse
    {
        try {
            $request = LLMRequest::completion($prompt, $options);
            
            if ($agentName) {
                return $this->llmManager->completeForAgent($agentName, $request);
            }
            
            return $this->llmManager->complete($request);
        } catch (LLMException $e) {
            Log::error("AI completion failed: " . $e->getMessage(), [
                'agent' => $agentName,
                'prompt_length' => strlen($prompt),
                'options' => $options
            ]);
            throw $e;
        }
    }

    /**
     * Generate chat completion
     */
    public function generateChatCompletion(array $messages, string $agentName = null, array $options = []): LLMResponse
    {
        try {
            $request = LLMRequest::chat($messages, $options);
            
            if ($agentName) {
                return $this->llmManager->completeForAgent($agentName, $request);
            }
            
            return $this->llmManager->complete($request);
        } catch (LLMException $e) {
            Log::error("AI chat completion failed: " . $e->getMessage(), [
                'agent' => $agentName,
                'messages_count' => count($messages),
                'options' => $options
            ]);
            throw $e;
        }
    }

    /**
     * Stream AI completion for real-time responses
     */
    public function streamCompletion(string $prompt, callable $callback, string $agentName = null, array $options = []): void
    {
        try {
            $request = LLMRequest::completion($prompt, $options);
            
            if ($agentName) {
                $agentMapping = config('ai_agents.llm_providers.agent_llm_mapping.' . $agentName);
                $preferredProvider = $agentMapping ? explode(':', $agentMapping['primary'])[0] : null;
                $this->llmManager->stream($request, $callback, $preferredProvider);
            } else {
                $this->llmManager->stream($request, $callback);
            }
        } catch (LLMException $e) {
            Log::error("AI streaming failed: " . $e->getMessage(), [
                'agent' => $agentName,
                'prompt_length' => strlen($prompt)
            ]);
            throw $e;
        }
    }

    /**
     * Execute function calling with AI
     */
    public function executeFunctionCall(array $messages, array $functions, string $agentName = null, array $options = []): LLMResponse
    {
        try {
            $request = LLMRequest::functionCall($messages, $functions, $options);
            
            if ($agentName) {
                return $this->llmManager->completeForAgent($agentName, $request);
            }
            
            return $this->llmManager->complete($request);
        } catch (LLMException $e) {
            Log::error("AI function calling failed: " . $e->getMessage(), [
                'agent' => $agentName,
                'functions_count' => count($functions),
                'messages_count' => count($messages)
            ]);
            throw $e;
        }
    }

    /**
     * Get LLM system status
     */
    public function getLLMStatus(): array
    {
        try {
            return [
                'enabled' => true,
                'providers' => $this->llmManager->getProvidersStatus(),
                'usage_stats' => $this->llmManager->getUsageStatistics(7),
                'cost_analysis' => $this->llmManager->getCostAnalysis(30),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get LLM status: " . $e->getMessage());
            return [
                'enabled' => false,
                'error' => $e->getMessage(),
                'providers' => [],
                'usage_stats' => [],
                'cost_analysis' => [],
            ];
        }
    }

    /**
     * Get available models across all providers
     */
    public function getAvailableModels(): array
    {
        try {
            return $this->llmManager->getAvailableModels();
        } catch (\Exception $e) {
            Log::error("Failed to get available models: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Perform health check on all LLM providers
     */
    public function performLLMHealthCheck(): array
    {
        try {
            return $this->llmManager->healthCheck();
        } catch (\Exception $e) {
            Log::error("LLM health check failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate smart response for HR queries
     */
    public function generateHRResponse(string $query, array $context = []): array
    {
        try {
            $systemPrompt = "You are an AI HR assistant. Provide helpful, accurate, and professional responses to HR-related queries. Consider company policies, employment law, and best practices.";
            
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $query]
            ];

            if (!empty($context)) {
                $contextStr = "Context: " . json_encode($context);
                $messages[] = ['role' => 'system', 'content' => $contextStr];
            }

            $response = $this->generateChatCompletion($messages, 'hr_agent', [
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);

            return [
                'success' => true,
                'response' => $response->getContent(),
                'model' => $response->getModel(),
                'provider' => $response->getProvider(),
                'cost' => $response->getCost(),
                'tokens_used' => $response->getTokensUsed(),
            ];
        } catch (\Exception $e) {
            Log::error("HR response generation failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze employee data with AI
     */
    public function analyzeEmployeeData(array $employeeData, string $analysisType = 'general'): array
    {
        try {
            $systemPrompt = match($analysisType) {
                'performance' => "Analyze employee performance data and provide insights on strengths, areas for improvement, and recommendations.",
                'engagement' => "Analyze employee engagement data and identify factors affecting satisfaction and retention.",
                'productivity' => "Analyze productivity metrics and suggest optimization strategies.",
                default => "Analyze the provided employee data and generate actionable insights."
            };

            $dataStr = json_encode($employeeData, JSON_PRETTY_PRINT);
            $prompt = "Please analyze the following employee data:\n\n{$dataStr}\n\nProvide insights and recommendations.";

            $response = $this->generateCompletion($prompt, 'analytics_agent', [
                'system_prompt' => $systemPrompt,
                'temperature' => 0.3, // Lower temperature for analytical tasks
                'max_tokens' => 1500,
            ]);

            return [
                'success' => true,
                'analysis' => $response->getContent(),
                'analysis_type' => $analysisType,
                'model' => $response->getModel(),
                'provider' => $response->getProvider(),
                'quality_score' => $response->getQualityScore(),
            ];
        } catch (\Exception $e) {
            Log::error("Employee data analysis failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate automated reports with AI
     */
    public function generateAutomatedReport(string $reportType, array $data, array $options = []): array
    {
        try {
            $templates = [
                'weekly_summary' => "Generate a comprehensive weekly HR summary report based on the provided data. Include key metrics, trends, and actionable insights.",
                'performance_review' => "Create a detailed performance review report analyzing employee performance data, highlighting achievements and areas for development.",
                'compliance_audit' => "Generate a compliance audit report reviewing HR policies, procedures, and regulatory adherence.",
                'recruitment_analysis' => "Analyze recruitment data and generate insights on hiring effectiveness, candidate quality, and process improvements.",
            ];

            $systemPrompt = $templates[$reportType] ?? $templates['weekly_summary'];
            $dataStr = json_encode($data, JSON_PRETTY_PRINT);
            
            $prompt = "Generate a {$reportType} report based on this data:\n\n{$dataStr}";

            $response = $this->generateCompletion($prompt, 'report_generator', array_merge([
                'system_prompt' => $systemPrompt,
                'temperature' => 0.4,
                'max_tokens' => 2000,
            ], $options));

            return [
                'success' => true,
                'report' => $response->getContent(),
                'report_type' => $reportType,
                'generated_at' => now()->toISOString(),
                'model' => $response->getModel(),
                'provider' => $response->getProvider(),
                'word_count' => str_word_count($response->getContent()),
            ];
        } catch (\Exception $e) {
            Log::error("Automated report generation failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process natural language queries about company data
     */
    public function processNaturalLanguageQuery(string $query, array $availableData = []): array
    {
        try {
            $systemPrompt = "You are an AI assistant that helps users query and understand company data. Interpret natural language questions and provide relevant insights from the available data.";
            
            $contextStr = "";
            if (!empty($availableData)) {
                $contextStr = "\n\nAvailable data types: " . implode(', ', array_keys($availableData));
            }

            $fullPrompt = $query . $contextStr;

            $response = $this->generateCompletion($fullPrompt, 'analytics_agent', [
                'system_prompt' => $systemPrompt,
                'temperature' => 0.5,
                'max_tokens' => 1000,
            ]);

            return [
                'success' => true,
                'interpretation' => $response->getContent(),
                'query' => $query,
                'model' => $response->getModel(),
                'provider' => $response->getProvider(),
                'confidence' => $response->getQualityScore() / 100,
            ];
        } catch (\Exception $e) {
            Log::error("Natural language query processing failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get cached workflow status
     */
    public function getCachedWorkflowStatus(string $type, int $id): ?array
    {
        return Cache::get("{$type}_workflow_{$id}");
    }
}