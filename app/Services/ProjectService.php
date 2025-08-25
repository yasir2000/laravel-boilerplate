<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    /**
     * Get projects with filtering and pagination.
     */
    public function getProjects(array $filters = []): LengthAwarePaginator
    {
        $query = Project::with(['company', 'owner']);

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->where('end_date', '<', now())
                  ->whereNotIn('status', [Project::STATUS_COMPLETED, Project::STATUS_CANCELLED]);
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
     * Create a new project.
     */
    public function createProject(array $data): Project
    {
        return DB::transaction(function () use ($data) {
            $project = Project::create($data);

            // Log activity
            activity()
                ->performedOn($project)
                ->log('Project created');

            return $project->load(['company', 'owner']);
        });
    }

    /**
     * Update an existing project.
     */
    public function updateProject(Project $project, array $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $project->update($data);

            // Log activity
            activity()
                ->performedOn($project)
                ->log('Project updated');

            return $project->fresh(['company', 'owner']);
        });
    }

    /**
     * Delete a project.
     */
    public function deleteProject(Project $project): bool
    {
        return DB::transaction(function () use ($project) {
            // Soft delete related tasks
            $project->tasks()->delete();

            // Log activity
            activity()
                ->performedOn($project)
                ->log('Project deleted');

            return $project->delete();
        });
    }

    /**
     * Update project status.
     */
    public function updateProjectStatus(Project $project, string $status): Project
    {
        return DB::transaction(function () use ($project, $status) {
            $oldStatus = $project->status;
            $project->update(['status' => $status]);

            // Update progress percentage based on status
            if ($status === Project::STATUS_COMPLETED) {
                $project->update(['progress_percentage' => 100]);
            }

            // Log activity
            activity()
                ->performedOn($project)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                ])
                ->log('Project status updated');

            return $project->fresh();
        });
    }

    /**
     * Get project dashboard data.
     */
    public function getProjectDashboard(Project $project): array
    {
        $tasks = $project->tasks();

        return [
            'project' => $project->load(['company', 'owner']),
            'task_statistics' => [
                'total' => $tasks->count(),
                'completed' => $tasks->completed()->count(),
                'pending' => $tasks->pending()->count(),
                'overdue' => $tasks->overdue()->count(),
            ],
            'progress' => [
                'percentage' => $project->progress_percentage ?? 0,
                'days_remaining' => $project->end_date ? $project->end_date->diffInDays(now(), false) : null,
                'is_overdue' => $project->isOverdue(),
            ],
            'recent_tasks' => $tasks->with(['assignedUser'])
                                  ->orderBy('updated_at', 'desc')
                                  ->limit(10)
                                  ->get(),
        ];
    }

    /**
     * Get overdue projects.
     */
    public function getOverdueProjects(): Collection
    {
        return Project::where('end_date', '<', now())
                     ->whereNotIn('status', [Project::STATUS_COMPLETED, Project::STATUS_CANCELLED])
                     ->with(['company', 'owner'])
                     ->get();
    }

    /**
     * Get projects by status.
     */
    public function getProjectsByStatus(string $status): Collection
    {
        return Project::where('status', $status)
                     ->with(['company', 'owner'])
                     ->get();
    }

    /**
     * Calculate project progress based on tasks.
     */
    public function calculateProjectProgress(Project $project): int
    {
        $totalTasks = $project->tasks()->count();
        
        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $project->tasks()->completed()->count();
        return round(($completedTasks / $totalTasks) * 100);
    }

    /**
     * Update project progress.
     */
    public function updateProjectProgress(Project $project): Project
    {
        $progress = $this->calculateProjectProgress($project);
        
        return DB::transaction(function () use ($project, $progress) {
            $project->update(['progress_percentage' => $progress]);

            // Auto-complete project if all tasks are done
            if ($progress === 100 && $project->status !== Project::STATUS_COMPLETED) {
                $this->updateProjectStatus($project, Project::STATUS_COMPLETED);
            }

            return $project->fresh();
        });
    }
}
