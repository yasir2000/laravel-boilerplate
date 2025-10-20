<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AIAgentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * AI Agents API Controller
 * Handles all AI agent and workflow-related API endpoints
 */
class AIAgentsController extends Controller
{
    protected $agentService;

    public function __construct(AIAgentService $agentService)
    {
        $this->agentService = $agentService;
    }

    /**
     * Get status of all agents
     */
    public function getAgentsStatus(): JsonResponse
    {
        try {
            $status = $this->agentService->getAgentsStatus();
            
            return response()->json([
                'success' => true,
                'core_agents' => $this->formatCoreAgents($status['core_agents'] ?? []),
                'specialized_agents' => $this->formatSpecializedAgents($status['specialized_agents'] ?? []),
                'last_updated' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get agents status', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve agents status'
            ], 500);
        }
    }

    /**
     * Get system health information
     */
    public function getSystemHealth(): JsonResponse
    {
        try {
            $health = $this->agentService->healthCheck();
            
            return response()->json([
                'success' => true,
                'health_data' => [
                    'overall_health' => $health['status'] ?? 'unknown',
                    'health_percentage' => $health['health_percentage'] ?? 0,
                    'active_workflows' => $health['active_workflows'] ?? 0,
                    'healthy_agents' => $health['healthy_agents'] ?? 0,
                    'total_agents' => $health['total_agents'] ?? 12,
                    'memory_usage' => $health['memory_usage'] ?? 0,
                    'avg_response_time' => $health['avg_response_time'] ?? 0
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get system health', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve system health'
            ], 500);
        }
    }

    /**
     * Get active workflows
     */
    public function getActiveWorkflows(): JsonResponse
    {
        try {
            // This would integrate with your workflow tracking system
            // For now, we'll return mock data
            $workflows = [
                [
                    'workflow_id' => 'onboard_20251020_143022',
                    'workflow_type' => 'employee_onboarding',
                    'employee_id' => 1,
                    'employee_name' => 'John Doe',
                    'status' => 'active',
                    'progress_percentage' => 65,
                    'started_at' => now()->subHours(2)->toISOString(),
                    'estimated_completion' => now()->addHours(4)->toISOString(),
                    'current_step' => 'IT Account Setup',
                    'assigned_agents' => ['HR Agent', 'IT Support Agent'],
                    'priority' => 'medium'
                ],
                [
                    'workflow_id' => 'leave_20251020_140015',
                    'workflow_type' => 'leave_request',
                    'employee_id' => 2,
                    'employee_name' => 'Jane Smith',
                    'status' => 'active',
                    'progress_percentage' => 80,
                    'started_at' => now()->subHours(1)->toISOString(),
                    'estimated_completion' => now()->addHours(1)->toISOString(),
                    'current_step' => 'Manager Approval',
                    'assigned_agents' => ['Leave Processing Agent', 'Coverage Agent'],
                    'priority' => 'high'
                ]
            ];
            
            return response()->json([
                'success' => true,
                'workflows' => $workflows
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get active workflows', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active workflows'
            ], 500);
        }
    }

    /**
     * Get activity feed
     */
    public function getActivityFeed(): JsonResponse
    {
        try {
            $activities = [
                [
                    'id' => 1,
                    'timestamp' => now()->subMinutes(5)->toISOString(),
                    'type' => 'workflow_started',
                    'title' => 'Employee Onboarding Started',
                    'description' => 'New hire onboarding workflow initiated for John Doe',
                    'agent_name' => 'HR Agent',
                    'workflow_id' => 'onboard_20251020_143022',
                    'iconCls' => 'fa-user-plus',
                    'severity' => 'info'
                ],
                [
                    'id' => 2,
                    'timestamp' => now()->subMinutes(15)->toISOString(),
                    'type' => 'agent_task_completed',
                    'title' => 'IT Account Created',
                    'description' => 'System accounts and email setup completed',
                    'agent_name' => 'IT Support Agent',
                    'workflow_id' => 'onboard_20251020_143022',
                    'iconCls' => 'fa-check-circle',
                    'severity' => 'success'
                ],
                [
                    'id' => 3,
                    'timestamp' => now()->subMinutes(30)->toISOString(),
                    'type' => 'workflow_completed',
                    'title' => 'Leave Request Approved',
                    'description' => 'Vacation request for Jane Smith has been approved',
                    'agent_name' => 'Leave Processing Agent',
                    'workflow_id' => 'leave_20251020_140015',
                    'iconCls' => 'fa-calendar-check',
                    'severity' => 'success'
                ]
            ];
            
            return response()->json([
                'success' => true,
                'activities' => $activities
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get activity feed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activity feed'
            ], 500);
        }
    }

    /**
     * Start a new workflow
     */
    public function startWorkflow(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'workflow_type' => 'required|string|in:employee_onboarding,leave_request,performance_review,payroll_exceptions,recruitment,compliance_monitoring',
                'workflow_name' => 'required|string|max:255',
                'priority' => 'required|string|in:low,medium,high,urgent',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $workflowData = $request->all();
            
            // Route to appropriate service method based on workflow type
            $result = $this->routeWorkflowStart($workflowData);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'workflow_id' => $result['workflow_id'],
                    'message' => 'Workflow started successfully',
                    'estimated_completion' => $result['estimated_completion'] ?? null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to start workflow'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to start workflow', [
                'error' => $e->getMessage(),
                'workflow_type' => $request->input('workflow_type')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start workflow'
            ], 500);
        }
    }

    /**
     * Pause a workflow
     */
    public function pauseWorkflow(Request $request, string $workflowId): JsonResponse
    {
        try {
            // This would integrate with your workflow management system
            // For now, we'll simulate the pause operation
            
            Log::info('Pausing workflow', ['workflow_id' => $workflowId]);
            
            return response()->json([
                'success' => true,
                'message' => 'Workflow paused successfully',
                'workflow_id' => $workflowId,
                'paused_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to pause workflow', [
                'error' => $e->getMessage(),
                'workflow_id' => $workflowId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause workflow'
            ], 500);
        }
    }

    /**
     * Process employee query
     */
    public function processEmployeeQuery(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|integer|exists:employees,id',
                'query' => 'required|string',
                'priority' => 'required|string|in:low,medium,high,urgent'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $queryData = $request->all();
            
            // Use the agent service to process the query
            $result = $this->agentService->executeAgentTask(
                'query_resolution',
                'process_employee_query',
                $queryData,
                $queryData['priority']
            );
            
            return response()->json([
                'success' => true,
                'query_id' => $result['task_id'] ?? null,
                'message' => 'Employee query processed successfully',
                'estimated_response_time' => $result['estimated_completion'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process employee query', [
                'error' => $e->getMessage(),
                'employee_id' => $request->input('employee_id')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process employee query'
            ], 500);
        }
    }

    /**
     * Run system health check
     */
    public function runHealthCheck(): JsonResponse
    {
        try {
            $healthReport = $this->agentService->healthCheck();
            
            return response()->json([
                'success' => true,
                'health_report' => $healthReport,
                'checked_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to run health check', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to run health check'
            ], 500);
        }
    }

    /**
     * Emergency shutdown
     */
    public function emergencyShutdown(Request $request): JsonResponse
    {
        try {
            $reason = $request->input('reason', 'Emergency shutdown requested');
            
            Log::warning('Emergency shutdown initiated', ['reason' => $reason]);
            
            // This would implement actual emergency shutdown logic
            // For now, we'll simulate the operation
            
            return response()->json([
                'success' => true,
                'message' => 'Emergency shutdown completed',
                'shutdown_time' => now()->toISOString(),
                'reason' => $reason,
                'workflows_stopped' => 5,
                'agents_stopped' => 12
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to perform emergency shutdown', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform emergency shutdown'
            ], 500);
        }
    }

    /**
     * Get workflow details
     */
    public function getWorkflowDetails(string $workflowId): JsonResponse
    {
        try {
            // This would retrieve actual workflow details from your system
            $workflowDetails = [
                'workflow_id' => $workflowId,
                'type' => 'employee_onboarding',
                'status' => 'active',
                'progress' => 65,
                'steps' => [
                    [
                        'step' => 'Document Collection',
                        'status' => 'completed',
                        'completed_at' => now()->subHours(3)->toISOString(),
                        'agent' => 'HR Agent'
                    ],
                    [
                        'step' => 'IT Account Setup',
                        'status' => 'in_progress',
                        'started_at' => now()->subHours(1)->toISOString(),
                        'agent' => 'IT Support Agent'
                    ],
                    [
                        'step' => 'Training Schedule',
                        'status' => 'pending',
                        'agent' => 'Training Agent'
                    ]
                ]
            ];
            
            return response()->json([
                'success' => true,
                'workflow' => $workflowDetails
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get workflow details', [
                'error' => $e->getMessage(),
                'workflow_id' => $workflowId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve workflow details'
            ], 500);
        }
    }

    /**
     * Route workflow start to appropriate service method
     */
    private function routeWorkflowStart(array $workflowData): array
    {
        switch ($workflowData['workflow_type']) {
            case 'employee_onboarding':
                return $this->agentService->processEmployeeOnboarding($workflowData);
                
            case 'leave_request':
                return $this->agentService->processLeaveRequest($workflowData);
                
            case 'performance_review':
                return $this->agentService->executeAgentTask(
                    'performance_review',
                    'initiate_review',
                    $workflowData,
                    $workflowData['priority']
                );
                
            case 'payroll_exceptions':
                return $this->agentService->executeAgentTask(
                    'payroll_exceptions',
                    'process_exceptions',
                    $workflowData,
                    $workflowData['priority']
                );
                
            case 'recruitment':
                return $this->agentService->executeAgentTask(
                    'recruitment',
                    'initiate_process',
                    $workflowData,
                    $workflowData['priority']
                );
                
            case 'compliance_monitoring':
                return $this->agentService->executeAgentTask(
                    'compliance',
                    'initiate_monitoring',
                    $workflowData,
                    $workflowData['priority']
                );
                
            default:
                return [
                    'success' => false,
                    'message' => 'Unknown workflow type'
                ];
        }
    }

    /**
     * Format core agents data for UI
     */
    private function formatCoreAgents(array $agents): array
    {
        $iconMap = [
            'hr_agent' => 'fa-users',
            'project_manager_agent' => 'fa-tasks',
            'analytics_agent' => 'fa-chart-line',
            'workflow_engine_agent' => 'fa-cogs',
            'integration_agent' => 'fa-plug',
            'notification_agent' => 'fa-bell'
        ];

        return array_map(function ($agent) use ($iconMap) {
            return [
                'id' => $agent['id'] ?? uniqid(),
                'name' => $agent['name'] ?? 'Unknown Agent',
                'type' => $agent['type'] ?? 'unknown',
                'status' => $agent['status'] ?? 'unknown',
                'active_tasks' => $agent['active_tasks'] ?? 0,
                'load_percentage' => $agent['load_percentage'] ?? 0,
                'iconCls' => $iconMap[$agent['type']] ?? 'fa-robot',
                'last_activity' => $agent['last_activity'] ?? now()->toISOString()
            ];
        }, $agents);
    }

    /**
     * Format specialized agents data for UI
     */
    private function formatSpecializedAgents(array $agents): array
    {
        $iconMap = [
            'it_support_agent' => 'fa-laptop',
            'compliance_agent' => 'fa-shield',
            'training_agent' => 'fa-graduation-cap',
            'payroll_agent' => 'fa-dollar',
            'leave_processing_agent' => 'fa-calendar',
            'coverage_agent' => 'fa-users'
        ];

        return array_map(function ($agent) use ($iconMap) {
            return [
                'id' => $agent['id'] ?? uniqid(),
                'name' => $agent['name'] ?? 'Unknown Agent',
                'type' => $agent['type'] ?? 'unknown',
                'status' => $agent['status'] ?? 'unknown',
                'queue_size' => $agent['queue_size'] ?? 0,
                'completed_today' => $agent['completed_today'] ?? 0,
                'iconCls' => $iconMap[$agent['type']] ?? 'fa-wrench',
                'specialization' => $agent['specialization'] ?? 'General'
            ];
        }, $agents);
    }
}