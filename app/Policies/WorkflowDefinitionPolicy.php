<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowDefinition;
use Illuminate\Auth\Access\Response;

class WorkflowDefinitionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['hr:workflows:view', 'hr:workflows:manage']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkflowDefinition $workflowDefinition): bool
    {
        return $user->hasAnyPermission(['hr:workflows:view', 'hr:workflows:manage']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr:workflows:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkflowDefinition $workflowDefinition): bool
    {
        return $user->hasPermissionTo('hr:workflows:update') &&
               $user->company_id === $workflowDefinition->creator->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkflowDefinition $workflowDefinition): bool
    {
        return $user->hasPermissionTo('hr:workflows:delete') &&
               $user->company_id === $workflowDefinition->creator->company_id;
    }

    /**
     * Determine whether the user can start the workflow.
     */
    public function start(User $user, WorkflowDefinition $workflowDefinition): bool
    {
        return $workflowDefinition->is_active &&
               $user->hasPermissionTo('hr:workflows:start') &&
               $user->company_id === $workflowDefinition->creator->company_id;
    }
}