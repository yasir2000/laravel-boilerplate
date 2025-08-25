<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\WorkflowAction;
use App\Events\WorkflowStepAssigned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Start a workflow for a subject
     */
    public function startWorkflow(
        string $workflowKey,
        Model $subject,
        User $createdBy,
        array $context = [],
        array $variables = []
    ): WorkflowInstance {
        $definition = WorkflowDefinition::where('key', $workflowKey)
            ->where('is_active', true)
            ->firstOrFail();

        return DB::transaction(function () use ($definition, $subject, $createdBy, $context, $variables) {
            $instance = WorkflowInstance::create([
                'workflow_definition_id' => $definition->id,
                'subject_type' => get_class($subject),
                'subject_id' => $subject->getKey(),
                'status' => 'pending',
                'context' => $context,
                'variables' => $variables,
                'created_by' => $createdBy->id,
                'started_at' => now(),
            ]);

            // Create workflow steps based on definition config
            $this->createWorkflowSteps($instance, $definition->config ?? []);

            // Start the first step
            $this->startNextStep($instance);

            return $instance;
        });
    }

    /**
     * Create workflow steps from configuration
     */
    protected function createWorkflowSteps(WorkflowInstance $instance, array $config): void
    {
        $steps = $config['steps'] ?? [];

        foreach ($steps as $index => $stepConfig) {
            WorkflowStep::create([
                'workflow_instance_id' => $instance->id,
                'name' => $stepConfig['name'],
                'description' => $stepConfig['description'] ?? null,
                'type' => $stepConfig['type'],
                'status' => 'pending',
                'config' => $stepConfig,
                'order' => $index + 1,
            ]);
        }
    }

    /**
     * Start the next pending step
     */
    public function startNextStep(WorkflowInstance $instance): ?WorkflowStep
    {
        $nextStep = $instance->steps()
            ->where('status', 'pending')
            ->orderBy('order')
            ->first();

        if (!$nextStep) {
            $this->completeWorkflow($instance);
            return null;
        }

        $nextStep->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        // Assign step if assignee is specified in config
        if ($assigneeId = $nextStep->config['assignee_id'] ?? null) {
            $this->assignStep($nextStep, User::find($assigneeId));
        }

        return $nextStep;
    }

    /**
     * Assign a workflow step to a user
     */
    public function assignStep(WorkflowStep $step, User $assignee, User $assignedBy = null): void
    {
        $step->update([
            'assignee_id' => $assignee->id,
            'assigned_by' => $assignedBy?->id,
            'assigned_at' => now(),
            'due_date' => $step->config['due_days'] ?? null ? now()->addDays($step->config['due_days']) : null,
        ]);

        // Send notification
        $this->notificationService->sendToUser(
            $assignee,
            __('workflow.step_assigned'),
            __('You have been assigned to workflow step: :name', ['name' => $step->name]),
            [
                'type' => 'info',
                'priority' => $step->config['priority'] ?? 'medium',
                'action_url' => "/workflows/steps/{$step->id}",
                'action_text' => __('View Step'),
            ]
        );

        // Broadcast event
        event(new WorkflowStepAssigned($step, $assignee));
    }

    /**
     * Record an action on a workflow step
     */
    public function recordAction(
        WorkflowStep $step,
        User $user,
        string $action,
        ?string $comment = null,
        array $data = [],
        ?User $delegatedTo = null
    ): WorkflowAction {
        $workflowAction = WorkflowAction::create([
            'workflow_step_id' => $step->id,
            'user_id' => $user->id,
            'action' => $action,
            'comment' => $comment,
            'data' => $data,
            'delegated_to' => $delegatedTo?->id,
        ]);

        // Process the action
        $this->processStepAction($step, $action, $user, $delegatedTo);

        return $workflowAction;
    }

    /**
     * Process step action
     */
    protected function processStepAction(WorkflowStep $step, string $action, User $user, ?User $delegatedTo = null): void
    {
        switch ($action) {
            case 'approve':
                $this->approveStep($step, $user);
                break;
            case 'reject':
                $this->rejectStep($step, $user);
                break;
            case 'delegate':
                if ($delegatedTo) {
                    $this->assignStep($step, $delegatedTo, $user);
                }
                break;
            case 'complete':
                $this->completeStep($step, $user);
                break;
        }
    }

    /**
     * Approve a workflow step
     */
    public function approveStep(WorkflowStep $step, User $user): void
    {
        $step->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Continue to next step
        $this->startNextStep($step->workflowInstance);

        // Send notification
        $this->notificationService->sendToUser(
            $step->workflowInstance->creator,
            __('workflow.approved'),
            __('Workflow step ":name" has been approved by :user', [
                'name' => $step->name,
                'user' => $user->name
            ]),
            ['type' => 'success']
        );
    }

    /**
     * Reject a workflow step
     */
    public function rejectStep(WorkflowStep $step, User $user): void
    {
        $step->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Mark workflow as rejected
        $step->workflowInstance->update(['status' => 'rejected']);

        // Send notification
        $this->notificationService->sendToUser(
            $step->workflowInstance->creator,
            __('workflow.rejected'),
            __('Workflow step ":name" has been rejected by :user', [
                'name' => $step->name,
                'user' => $user->name
            ]),
            ['type' => 'error', 'priority' => 'high']
        );
    }

    /**
     * Complete a workflow step
     */
    public function completeStep(WorkflowStep $step, User $user): void
    {
        $step->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Continue to next step
        $this->startNextStep($step->workflowInstance);
    }

    /**
     * Complete the entire workflow
     */
    protected function completeWorkflow(WorkflowInstance $instance): void
    {
        $instance->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Send completion notification
        $this->notificationService->sendToUser(
            $instance->creator,
            __('workflow.completed'),
            __('Workflow ":name" has been completed successfully', [
                'name' => $instance->definition->name
            ]),
            ['type' => 'success']
        );
    }

    /**
     * Cancel a workflow
     */
    public function cancelWorkflow(WorkflowInstance $instance, User $user, string $reason = null): void
    {
        $instance->update(['status' => 'cancelled']);

        // Cancel all pending steps
        $instance->steps()
            ->whereIn('status', ['pending', 'in_progress'])
            ->update(['status' => 'cancelled']);

        // Send notification
        $this->notificationService->sendToUser(
            $instance->creator,
            __('workflow.cancelled'),
            __('Workflow ":name" has been cancelled', [
                'name' => $instance->definition->name
            ]),
            ['type' => 'warning']
        );
    }

    /**
     * Get user's pending workflow steps
     */
    public function getUserPendingSteps(User $user)
    {
        return WorkflowStep::where('assignee_id', $user->id)
            ->where('status', 'in_progress')
            ->with(['workflowInstance.definition', 'workflowInstance.subject'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get workflow statistics
     */
    public function getWorkflowStats(array $filters = []): array
    {
        $query = WorkflowInstance::query();

        if ($filters['date_from'] ?? null) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] ?? null) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];
    }
}
