<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('can:view,project')->only(['show']);
        $this->middleware('can:update,project')->only(['update']);
        $this->middleware('can:delete,project')->only(['destroy']);
    }

    /**
     * Display a listing of projects.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $projects = $this->projectService->getProjects($request->all());

        return $this->successResponse($projects, 'Projects retrieved successfully');
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->projectService->createProject($request->validated());

        return $this->successResponse($project, 'Project created successfully', 201);
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): JsonResponse
    {
        $project->load(['company', 'owner', 'tasks']);

        return $this->successResponse($project, 'Project retrieved successfully');
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $project = $this->projectService->updateProject($project, $request->validated());

        return $this->successResponse($project, 'Project updated successfully');
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->projectService->deleteProject($project);

        return $this->successResponse(null, 'Project deleted successfully');
    }

    /**
     * Get project dashboard data.
     */
    public function dashboard(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $dashboard = $this->projectService->getProjectDashboard($project);

        return $this->successResponse($dashboard, 'Project dashboard data retrieved successfully');
    }

    /**
     * Update project status.
     */
    public function updateStatus(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(Project::getStatusOptions())),
        ]);

        $project = $this->projectService->updateProjectStatus($project, $request->status);

        return $this->successResponse($project, 'Project status updated successfully');
    }
}
