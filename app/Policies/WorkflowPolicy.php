<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use Illuminate\Auth\Access\Response;

class WorkflowInstancePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['hr:workflows:view', 'hr:workflows:view-all']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkflowInstance $workflowInstance): bool
    {
        // Can view if user has view-all permission
        if ($user->hasPermissionTo('hr:workflows:view-all')) {
            return true;
        }

        // Can view if user created the workflow
        if ($workflowInstance->created_by === $user->id) {
            return true;
        }

        // Can view if user is assigned to any step
        return $workflowInstance->steps()
            ->where('assignee_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can cancel the workflow.
     */
    public function cancel(User $user, WorkflowInstance $workflowInstance): bool
    {
        return $user->hasPermissionTo('hr:workflows:cancel') &&
               ($workflowInstance->created_by === $user->id ||
                $user->hasPermissionTo('hr:workflows:manage'));
    }
}

class WorkflowStepPolicy
{
    /**
     * Determine whether the user can take action on the step.
     */
    public function takeAction(User $user, WorkflowStep $workflowStep): bool
    {
        // Must be assigned to this step
        if ($workflowStep->assignee_id !== $user->id) {
            return false;
        }

        // Step must be in actionable status
        return in_array($workflowStep->status, ['pending', 'in_progress']);
    }

    /**
     * Determine whether the user can delegate the step.
     */
    public function delegate(User $user, WorkflowStep $workflowStep): bool
    {
        return $this->takeAction($user, $workflowStep) &&
               $user->hasPermissionTo('hr:workflows:delegate');
    }
}