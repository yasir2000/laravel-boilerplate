<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIAgentService
{
    protected $baseUrl;
    protected $timeout;
    
    public function __construct()
    {
        $this->baseUrl = config('ai_agents.base_url', 'http://localhost:8001');
        $this->timeout = config('ai_agents.timeout', 30);
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
     * Get cached workflow status
     */
    public function getCachedWorkflowStatus(string $type, int $id): ?array
    {
        return Cache::get("{$type}_workflow_{$id}");
    }
}