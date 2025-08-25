<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    /**
     * Get tasks with filtering and pagination.
     */
    public function getTasks(array $filters = []): LengthAwarePaginator
    {
        $query = Task::with(['project', 'assignedUser', 'creator']);

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->overdue();
        }

        if (isset($filters['due_date'])) {
            $query->whereDate('due_date', $filters['due_date']);
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
     * Create a new task.
     */
    public function createTask(array $data): Task
    {
        return DB::transaction(function () use ($data) {
            $task = Task::create($data);

            // Log activity
            activity()
                ->performedOn($task)
                ->log('Task created');

            // Update project progress
            $this->projectService->updateProjectProgress($task->project);

            return $task->load(['project', 'assignedUser', 'creator']);
        });
    }

    /**
     * Update an existing task.
     */
    public function updateTask(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            $oldStatus = $task->status;
            $task->update($data);

            // If status changed to completed, set completed_at
            if (isset($data['status']) && $data['status'] === Task::STATUS_COMPLETED && $oldStatus !== Task::STATUS_COMPLETED) {
                $task->update(['completed_at' => now()]);
            }

            // Log activity
            activity()
                ->performedOn($task)
                ->log('Task updated');

            // Update project progress
            $this->projectService->updateProjectProgress($task->project);

            return $task->fresh(['project', 'assignedUser', 'creator']);
        });
    }

    /**
     * Delete a task.
     */
    public function deleteTask(Task $task): bool
    {
        return DB::transaction(function () use ($task) {
            $project = $task->project;

            // Log activity
            activity()
                ->performedOn($task)
                ->log('Task deleted');

            $result = $task->delete();

            // Update project progress
            $this->projectService->updateProjectProgress($project);

            return $result;
        });
    }

    /**
     * Complete a task.
     */
    public function completeTask(Task $task): Task
    {
        return DB::transaction(function () use ($task) {
            $task->markAsCompleted();

            // Log activity
            activity()
                ->performedOn($task)
                ->log('Task completed');

            // Update project progress
            $this->projectService->updateProjectProgress($task->project);

            return $task->fresh(['project', 'assignedUser', 'creator']);
        });
    }

    /**
     * Assign a task to a user.
     */
    public function assignTask(Task $task, string $userId): Task
    {
        return DB::transaction(function () use ($task, $userId) {
            $oldAssignee = $task->assigned_to;
            $task->update(['assigned_to' => $userId]);

            // Log activity
            activity()
                ->performedOn($task)
                ->withProperties([
                    'old_assignee' => $oldAssignee,
                    'new_assignee' => $userId,
                ])
                ->log('Task assigned');

            return $task->fresh(['project', 'assignedUser', 'creator']);
        });
    }

    /**
     * Get tasks for a specific user.
     */
    public function getUserTasks(string $userId, array $filters = []): LengthAwarePaginator
    {
        $filters['assigned_to'] = $userId;
        return $this->getTasks($filters);
    }

    /**
     * Get overdue tasks.
     */
    public function getOverdueTasks(): Collection
    {
        return Task::overdue()
                  ->with(['project', 'assignedUser'])
                  ->get();
    }

    /**
     * Get tasks due today.
     */
    public function getTasksDueToday(): Collection
    {
        return Task::whereDate('due_date', today())
                  ->pending()
                  ->with(['project', 'assignedUser'])
                  ->get();
    }

    /**
     * Get task statistics for a user.
     */
    public function getUserTaskStatistics(string $userId): array
    {
        $userTasks = Task::where('assigned_to', $userId);

        return [
            'total' => $userTasks->count(),
            'completed' => $userTasks->completed()->count(),
            'pending' => $userTasks->pending()->count(),
            'overdue' => $userTasks->overdue()->count(),
            'due_today' => $userTasks->whereDate('due_date', today())->pending()->count(),
            'due_this_week' => $userTasks->whereBetween('due_date', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->pending()->count(),
        ];
    }

    /**
     * Get task statistics for a project.
     */
    public function getProjectTaskStatistics(string $projectId): array
    {
        $projectTasks = Task::where('project_id', $projectId);

        return [
            'total' => $projectTasks->count(),
            'completed' => $projectTasks->completed()->count(),
            'pending' => $projectTasks->pending()->count(),
            'overdue' => $projectTasks->overdue()->count(),
            'by_priority' => [
                'low' => $projectTasks->where('priority', Task::PRIORITY_LOW)->count(),
                'medium' => $projectTasks->where('priority', Task::PRIORITY_MEDIUM)->count(),
                'high' => $projectTasks->where('priority', Task::PRIORITY_HIGH)->count(),
                'urgent' => $projectTasks->where('priority', Task::PRIORITY_URGENT)->count(),
            ],
            'by_status' => [
                'todo' => $projectTasks->where('status', Task::STATUS_TODO)->count(),
                'in_progress' => $projectTasks->where('status', Task::STATUS_IN_PROGRESS)->count(),
                'review' => $projectTasks->where('status', Task::STATUS_REVIEW)->count(),
                'completed' => $projectTasks->where('status', Task::STATUS_COMPLETED)->count(),
                'cancelled' => $projectTasks->where('status', Task::STATUS_CANCELLED)->count(),
            ],
        ];
    }
}
