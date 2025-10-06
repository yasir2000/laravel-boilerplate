<?php

namespace App\Http\Controllers\Api\Workflow;

use App\Http\Controllers\Controller;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WorkflowController extends Controller
{
    public function __construct(
        private WorkflowService $workflowService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all workflow definitions
     */
    public function definitions(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkflowDefinition::class);

        $definitions = WorkflowDefinition::query()
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->active !== null, fn($q) => $q->where('is_active', $request->boolean('active')))
            ->with('creator:id,first_name,last_name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($definitions, 'Workflow definitions retrieved successfully');
    }

    /**
     * Create a new workflow definition
     */
    public function createDefinition(Request $request): JsonResponse
    {
        $this->authorize('create', WorkflowDefinition::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255|unique:workflow_definitions,key',
            'description' => 'nullable|string',
            'type' => 'required|string|in:approval,review,notification,sequential,parallel',
            'config' => 'nullable|array',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.type' => 'required|string|in:approval,review,notification,condition,parallel,sequential,custom',
            'steps.*.config' => 'nullable|array',
            'steps.*.order' => 'required|integer|min:0',
        ]);

        $definition = $this->workflowService->createDefinition($validated);

        return $this->successResponse($definition, 'Workflow definition created successfully', 201);
    }

    /**
     * Start a new workflow instance
     */
    public function startWorkflow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workflow_definition_id' => 'required|uuid|exists:workflow_definitions,id',
            'subject_type' => 'required|string',
            'subject_id' => 'required',
            'context' => 'nullable|array',
            'variables' => 'nullable|array',
        ]);

        $definition = WorkflowDefinition::findOrFail($validated['workflow_definition_id']);
        $this->authorize('start', $definition);

        $instance = $this->workflowService->startWorkflow($definition, $validated);

        return $this->successResponse($instance, 'Workflow started successfully', 201);
    }

    /**
     * Get workflow instances
     */
    public function instances(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkflowInstance::class);

        $instances = WorkflowInstance::query()
            ->with(['definition', 'creator:id,first_name,last_name,email', 'steps.assignee:id,first_name,last_name,email'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->definition_id, fn($q) => $q->where('workflow_definition_id', $request->definition_id))
            ->when($request->subject_type, fn($q) => $q->where('subject_type', $request->subject_type))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($instances, 'Workflow instances retrieved successfully');
    }

    /**
     * Get workflow instance details
     */
    public function showInstance(WorkflowInstance $instance): JsonResponse
    {
        $this->authorize('view', $instance);

        $instance->load([
            'definition',
            'creator:id,first_name,last_name,email',
            'steps' => function ($query) {
                $query->orderBy('order')->with([
                    'assignee:id,first_name,last_name,email',
                    'assignedBy:id,first_name,last_name,email',
                    'actions.user:id,first_name,last_name,email'
                ]);
            }
        ]);

        return $this->successResponse($instance, 'Workflow instance retrieved successfully');
    }

    /**
     * Get user's assigned workflow steps
     */
    public function userSteps(Request $request): JsonResponse
    {
        $user = $request->user();

        $steps = $user->workflowSteps()
            ->with([
                'workflowInstance.definition',
                'workflowInstance.creator:id,first_name,last_name,email',
                'assignedBy:id,first_name,last_name,email'
            ])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($steps, 'User workflow steps retrieved successfully');
    }

    /**
     * Take action on a workflow step
     */
    public function takeAction(Request $request, WorkflowInstance $instance, $stepId): JsonResponse
    {
        $step = $instance->steps()->findOrFail($stepId);
        $this->authorize('takeAction', $step);

        $validated = $request->validate([
            'action' => 'required|string|in:approve,reject,delegate,comment,request_changes,complete',
            'comment' => 'nullable|string',
            'data' => 'nullable|array',
            'delegated_to' => 'nullable|uuid|exists:users,id',
        ]);

        $result = $this->workflowService->takeAction($step, $validated);

        return $this->successResponse($result, 'Action taken successfully');
    }
}