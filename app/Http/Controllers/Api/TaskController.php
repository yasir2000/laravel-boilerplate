<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('can:view,task')->only(['show']);
        $this->middleware('can:update,task')->only(['update']);
        $this->middleware('can:delete,task')->only(['destroy']);
    }

    /**
     * Display a listing of tasks.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $tasks = $this->taskService->getTasks($request->all());

        return $this->successResponse($tasks, 'Tasks retrieved successfully');
    }

    /**
     * Store a newly created task.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $this->authorize('create', Task::class);

        $task = $this->taskService->createTask($request->validated());

        return $this->successResponse($task, 'Task created successfully', 201);
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task): JsonResponse
    {
        $task->load(['project', 'assignedUser', 'creator']);

        return $this->successResponse($task, 'Task retrieved successfully');
    }

    /**
     * Update the specified task.
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task = $this->taskService->updateTask($task, $request->validated());

        return $this->successResponse($task, 'Task updated successfully');
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->taskService->deleteTask($task);

        return $this->successResponse(null, 'Task deleted successfully');
    }

    /**
     * Mark task as completed.
     */
    public function complete(Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task = $this->taskService->completeTask($task);

        return $this->successResponse($task, 'Task marked as completed');
    }

    /**
     * Assign task to user.
     */
    public function assign(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $task = $this->taskService->assignTask($task, $request->user_id);

        return $this->successResponse($task, 'Task assigned successfully');
    }

    /**
     * Get user's assigned tasks.
     */
    public function myTasks(Request $request): JsonResponse
    {
        $tasks = $this->taskService->getUserTasks($request->user()->id, $request->all());

        return $this->successResponse($tasks, 'Your tasks retrieved successfully');
    }
}
