<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view companies');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Company $company): bool
    {
        if ($user->can('view companies')) {
            // Super admin can view all companies
            if ($user->hasRole('super-admin')) {
                return true;
            }
            
            // Users can only view their own company
            return $user->company_id === $company->id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create companies');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Company $company): bool
    {
        if ($user->can('update companies')) {
            // Super admin can update all companies
            if ($user->hasRole('super-admin')) {
                return true;
            }
            
            // Admins can only update their own company
            return $user->company_id === $company->id && $user->hasRole(['admin']);
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Company $company): bool
    {
        // Only super admin can delete companies
        return $user->can('delete companies') && $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Company $company): bool
    {
        return $user->can('delete companies') && $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        return $user->can('delete companies') && $user->hasRole('super-admin');
    }
}
