<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CompanyService
{
    /**
     * Get companies with filtering and pagination.
     */
    public function getCompanies(array $filters = []): LengthAwarePaginator
    {
        $query = Company::query();

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['subscription_plan'])) {
            $query->where('subscription_plan', $filters['subscription_plan']);
        }

        if (isset($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Return paginated results
        $perPage = min($filters['per_page'] ?? 15, 100);
        return $query->paginate($perPage);
    }

    /**
     * Create a new company.
     */
    public function createCompany(array $data): Company
    {
        return DB::transaction(function () use ($data) {
            $company = Company::create($data);

            // Log activity
            activity()
                ->performedOn($company)
                ->log('Company created');

            return $company;
        });
    }

    /**
     * Update an existing company.
     */
    public function updateCompany(Company $company, array $data): Company
    {
        return DB::transaction(function () use ($company, $data) {
            $company->update($data);

            // Log activity
            activity()
                ->performedOn($company)
                ->log('Company updated');

            return $company->fresh();
        });
    }

    /**
     * Delete a company.
     */
    public function deleteCompany(Company $company): bool
    {
        return DB::transaction(function () use ($company) {
            // Soft delete related entities
            $company->users()->delete();
            $company->projects()->delete();

            // Log activity
            activity()
                ->performedOn($company)
                ->log('Company deleted');

            return $company->delete();
        });
    }

    /**
     * Get company statistics.
     */
    public function getCompanyStatistics(Company $company): array
    {
        return [
            'total_users' => $company->users()->count(),
            'active_users' => $company->users()->active()->count(),
            'total_projects' => $company->projects()->count(),
            'active_projects' => $company->projects()->active()->count(),
            'completed_projects' => $company->projects()->completed()->count(),
            'total_tasks' => $company->projects()->withCount('tasks')->get()->sum('tasks_count'),
            'completed_tasks' => $this->getCompletedTasksCount($company),
            'has_active_subscription' => $company->hasActiveSubscription(),
        ];
    }

    /**
     * Get active companies.
     */
    public function getActiveCompanies(): Collection
    {
        return Company::active()->get();
    }

    /**
     * Get companies with expiring subscriptions.
     */
    public function getCompaniesWithExpiringSubscriptions(int $days = 30): Collection
    {
        return Company::where('subscription_expires_at', '<=', now()->addDays($days))
                     ->where('subscription_expires_at', '>', now())
                     ->where('is_active', true)
                     ->get();
    }

    /**
     * Get completed tasks count for a company.
     */
    private function getCompletedTasksCount(Company $company): int
    {
        return DB::table('tasks')
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('projects.company_id', $company->id)
            ->where('tasks.status', 'completed')
            ->whereNull('tasks.deleted_at')
            ->count();
    }
}
