<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view projects');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->can('view projects')) {
            // Super admin can view all projects
            if ($user->hasRole('super-admin')) {
                return true;
            }
            
            // Users can only view projects from their company
            return $user->company_id === $project->company_id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create projects');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        if ($user->can('update projects')) {
            // Super admin can update all projects
            if ($user->hasRole('super-admin')) {
                return true;
            }
            
            // Project owner can update their project
            if ($user->id === $project->owner_id) {
                return true;
            }
            
            // Admins and managers from the same company can update projects
            return $user->company_id === $project->company_id && 
                   $user->hasRole(['admin', 'manager']);
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        if ($user->can('delete projects')) {
            // Super admin can delete all projects
            if ($user->hasRole('super-admin')) {
                return true;
            }
            
            // Project owner can delete their project
            if ($user->id === $project->owner_id) {
                return true;
            }
            
            // Admins from the same company can delete projects
            return $user->company_id === $project->company_id && 
                   $user->hasRole('admin');
        }
        
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $user->can('delete projects') && $user->hasRole('super-admin');
    }
}
