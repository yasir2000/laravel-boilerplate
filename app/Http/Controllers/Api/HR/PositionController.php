<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Position;
use App\Models\HR\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PositionController extends Controller
{
    /**
     * Display a listing of positions
     */
    public function index(Request $request): JsonResponse
    {
        $query = Position::with(['department', 'employees.user'])
            ->withCount(['employees' => function ($query) {
                $query->where('employment_status', 'active');
            }]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                  ->orWhere('code', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by active status
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Filter by level
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        // Salary range filters
        if ($request->filled('min_salary_from')) {
            $query->where('min_salary', '>=', $request->min_salary_from);
        }

        if ($request->filled('max_salary_to')) {
            $query->where('max_salary', '<=', $request->max_salary_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'title');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination or all results
        if ($request->boolean('all')) {
            $positions = $query->get();
        } else {
            $perPage = $request->get('per_page', 15);
            $positions = $query->paginate($perPage);
        }

        return response()->json([
            'success' => true,
            'data' => $positions,
            'message' => 'Positions retrieved successfully'
        ]);
    }

    /**
     * Store a newly created position
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:hr_positions,code',
            'description' => 'nullable|string',
            'department_id' => 'required|exists:hr_departments,id',
            'level' => 'nullable|string|max:50',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
            'requirements' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'is_active' => 'boolean',
            'skills' => 'nullable|array',
            'metadata' => 'nullable|array'
        ]);

        $position = Position::create($validated);
        $position->load(['department', 'employees.user']);

        return response()->json([
            'success' => true,
            'data' => $position,
            'message' => 'Position created successfully'
        ], 201);
    }

    /**
     * Display the specified position
     */
    public function show(Position $position): JsonResponse
    {
        $position->load([
            'department.manager',
            'employees' => function ($query) {
                $query->where('employment_status', 'active')
                      ->with(['user', 'supervisor.user']);
            }
        ]);

        // Add calculated fields
        $position->active_employees_count = $position->active_employees_count;
        $position->salary_range = $position->salary_range;

        return response()->json([
            'success' => true,
            'data' => $position,
            'message' => 'Position retrieved successfully'
        ]);
    }

    /**
     * Update the specified position
     */
    public function update(Request $request, Position $position): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('hr_positions', 'code')->ignore($position->id)
            ],
            'description' => 'nullable|string',
            'department_id' => 'required|exists:hr_departments,id',
            'level' => 'nullable|string|max:50',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
            'requirements' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'is_active' => 'boolean',
            'skills' => 'nullable|array',
            'metadata' => 'nullable|array'
        ]);

        $position->update($validated);
        $position->load(['department', 'employees.user']);

        return response()->json([
            'success' => true,
            'data' => $position,
            'message' => 'Position updated successfully'
        ]);
    }

    /**
     * Remove the specified position
     */
    public function destroy(Position $position): JsonResponse
    {
        // Check if position can be deleted
        if (!$position->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete position with active employees',
                'errors' => ['position' => ['Position has active employees']]
            ], 422);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Position deleted successfully'
        ]);
    }

    /**
     * Get position statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_positions' => Position::count(),
            'active_positions' => Position::active()->count(),
            'positions_with_employees' => Position::has('employees')->count(),
            'positions_by_level' => Position::selectRaw('level, count(*) as count')
                                          ->whereNotNull('level')
                                          ->groupBy('level')
                                          ->pluck('count', 'level'),
            'positions_by_department' => Position::join('hr_departments', 'hr_positions.department_id', '=', 'hr_departments.id')
                                               ->selectRaw('hr_departments.name as department, count(*) as count')
                                               ->groupBy('hr_departments.name')
                                               ->pluck('count', 'department'),
            'salary_statistics' => [
                'min_salary_range' => [
                    'lowest' => Position::whereNotNull('min_salary')->min('min_salary'),
                    'highest' => Position::whereNotNull('min_salary')->max('min_salary'),
                    'average' => Position::whereNotNull('min_salary')->avg('min_salary')
                ],
                'max_salary_range' => [
                    'lowest' => Position::whereNotNull('max_salary')->min('max_salary'),
                    'highest' => Position::whereNotNull('max_salary')->max('max_salary'),
                    'average' => Position::whereNotNull('max_salary')->avg('max_salary')
                ]
            ],
            'vacant_positions' => Position::active()
                                        ->whereDoesntHave('employees', function ($query) {
                                            $query->where('employment_status', 'active');
                                        })->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Position statistics retrieved successfully'
        ]);
    }
}