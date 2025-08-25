<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view tasks');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        if ($user->can('view tasks')) {
            // Super admin can view all tasks
            if ($user->hasRole('super-admin')) {
                return true;
            }
            
            // Users can view tasks from their company
            return $user->company_id === $task->project->company_id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create tasks');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        if ($user->can('update tasks')) {
            // Super admin can update all tasks
            if ($user->hasRole('super-admin')) {
                return true;
            }
            
            // Task assignee can update their task
            if ($user->id === $task->assigned_to) {
                return true;
            }
            
            // Task creator can update their task
            if ($user->id === $task->created_by) {
                return true;
            }
            
            // Project owner can update tasks in their project
            if ($user->id === $task->project->owner_id) {
                return true;
            }
            
            // Admins and managers from the same company can update tasks
            return $user->company_id === $task->project->company_id && 
                   $user->hasRole(['admin', 'manager']);
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        if ($user->can('delete tasks')) {
            // Super admin can delete all tasks
            if ($user->hasRole('super-admin')) {
                return true;
            }
            
            // Task creator can delete their task
            if ($user->id === $task->created_by) {
                return true;
            }
            
            // Project owner can delete tasks in their project
            if ($user->id === $task->project->owner_id) {
                return true;
            }
            
            // Admins and managers from the same company can delete tasks
            return $user->company_id === $task->project->company_id && 
                   $user->hasRole(['admin', 'manager']);
        }
        
        return false;
    }

    /**
     * Determine whether the user can assign the task.
     */
    public function assign(User $user, Task $task): bool
    {
        if ($user->can('assign tasks')) {
            // Super admin can assign all tasks
            if ($user->hasRole('super-admin')) {
                return true;
            }
            
            // Project owner can assign tasks in their project
            if ($user->id === $task->project->owner_id) {
                return true;
            }
            
            // Admins and managers from the same company can assign tasks
            return $user->company_id === $task->project->company_id && 
                   $user->hasRole(['admin', 'manager']);
        }
        
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return $this->delete($user, $task);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return $user->can('delete tasks') && $user->hasRole('super-admin');
    }
}
