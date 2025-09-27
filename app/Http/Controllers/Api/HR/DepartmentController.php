<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Department;
use App\Models\HR\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments
     */
    public function index(Request $request): JsonResponse
    {
        $query = Department::with(['manager', 'parent', 'children'])
            ->withCount(['employees' => function ($query) {
                $query->where('employment_status', 'active');
            }]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('code', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Filter by parent department
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // Get root departments if no parent specified
        if ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination or all results
        if ($request->boolean('all')) {
            $departments = $query->get();
        } else {
            $perPage = $request->get('per_page', 15);
            $departments = $query->paginate($perPage);
        }

        return response()->json([
            'success' => true,
            'data' => $departments,
            'message' => 'Departments retrieved successfully'
        ]);
    }

    /**
     * Store a newly created department
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:hr_departments,code',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:hr_departments,id',
            'manager_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:100',
            'budget' => 'nullable|numeric|min:0',
            'max_employees' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array'
        ]);

        // Prevent circular hierarchy
        if ($validated['parent_id'] ?? false) {
            $parent = Department::find($validated['parent_id']);
            if ($parent && $this->wouldCreateCircularHierarchy($parent, null)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot create circular department hierarchy',
                    'errors' => ['parent_id' => ['This would create a circular hierarchy']]
                ], 422);
            }
        }

        $department = Department::create($validated);
        $department->load(['manager', 'parent', 'children']);

        return response()->json([
            'success' => true,
            'data' => $department,
            'message' => 'Department created successfully'
        ], 201);
    }

    /**
     * Display the specified department
     */
    public function show(Department $department): JsonResponse
    {
        $department->load([
            'manager',
            'parent',
            'children.manager',
            'employees' => function ($query) {
                $query->where('employment_status', 'active')
                      ->with(['user', 'position']);
            },
            'positions'
        ]);

        // Add calculated fields
        $department->budget_utilization = $department->budget_utilization;
        $department->active_employees_count = $department->active_employees_count;

        return response()->json([
            'success' => true,
            'data' => $department,
            'message' => 'Department retrieved successfully'
        ]);
    }

    /**
     * Update the specified department
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('hr_departments', 'code')->ignore($department->id)
            ],
            'description' => 'nullable|string',
            'parent_id' => [
                'nullable',
                'exists:hr_departments,id',
                Rule::notIn([$department->id]) // Cannot be parent of itself
            ],
            'manager_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:100',
            'budget' => 'nullable|numeric|min:0',
            'max_employees' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array'
        ]);

        // Prevent circular hierarchy
        if (isset($validated['parent_id'])) {
            $parent = Department::find($validated['parent_id']);
            if ($parent && $this->wouldCreateCircularHierarchy($parent, $department)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot create circular department hierarchy',
                    'errors' => ['parent_id' => ['This would create a circular hierarchy']]
                ], 422);
            }
        }

        $department->update($validated);
        $department->load(['manager', 'parent', 'children']);

        return response()->json([
            'success' => true,
            'data' => $department,
            'message' => 'Department updated successfully'
        ]);
    }

    /**
     * Remove the specified department
     */
    public function destroy(Department $department): JsonResponse
    {
        // Check if department can be deleted
        if (!$department->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete department with employees, positions, or sub-departments',
                'errors' => ['department' => ['Department has dependencies that prevent deletion']]
            ], 422);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully'
        ]);
    }

    /**
     * Get department hierarchy (tree structure)
     */
    public function hierarchy(): JsonResponse
    {
        $departments = Department::whereNull('parent_id')
            ->with(['descendants.manager', 'descendants.employees' => function ($query) {
                $query->where('employment_status', 'active');
            }])
            ->withCount(['employees' => function ($query) {
                $query->where('employment_status', 'active');
            }])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $departments,
            'message' => 'Department hierarchy retrieved successfully'
        ]);
    }

    /**
     * Get department statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_departments' => Department::count(),
            'active_departments' => Department::active()->count(),
            'departments_with_manager' => Department::whereNotNull('manager_id')->count(),
            'departments_with_budget' => Department::whereNotNull('budget')->count(),
            'total_budget' => Department::whereNotNull('budget')->sum('budget'),
            'average_employees_per_dept' => round(
                Employee::where('employment_status', 'active')->count() / 
                max(Department::active()->count(), 1), 2
            ),
            'departments_by_level' => $this->getDepartmentsByLevel()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Department statistics retrieved successfully'
        ]);
    }

    /**
     * Get employees in a department
     */
    public function employees(Department $department, Request $request): JsonResponse
    {
        $query = $department->employees()
            ->with(['user', 'position', 'supervisor.user']);

        // Filter by employment status
        if ($request->filled('status')) {
            $query->where('employment_status', $request->status);
        } else {
            $query->where('employment_status', 'active'); // Default to active
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                  ->orWhere('last_name', 'ILIKE', "%{$search}%")
                  ->orWhere('employee_id', 'ILIKE', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $employees = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $employees,
            'message' => 'Department employees retrieved successfully'
        ]);
    }

    /**
     * Check if adding parent would create circular hierarchy
     */
    private function wouldCreateCircularHierarchy($parent, $department = null): bool
    {
        if (!$parent) {
            return false;
        }

        $currentParent = $parent;
        while ($currentParent) {
            if ($department && $currentParent->id === $department->id) {
                return true;
            }
            $currentParent = $currentParent->parent;
        }

        return false;
    }

    /**
     * Get departments in tree format for ExtJS TreePanel
     */
    public function tree(Request $request): JsonResponse
    {
        $departments = Department::whereNull('parent_id')
            ->with(['children' => function ($query) {
                $this->loadTreeChildren($query);
            }])
            ->withCount(['employees' => function ($query) {
                $query->where('employment_status', 'active');
            }])
            ->get();

        $treeData = $this->formatTreeData($departments);

        return response()->json([
            'success' => true,
            'data' => $treeData,
            'message' => 'Department tree retrieved successfully'
        ]);
    }

    /**
     * Get departments in simple list format for combo boxes
     */
    public function list(Request $request): JsonResponse
    {
        $departments = Department::where('is_active', true)
            ->select('id', 'name', 'code', 'parent_id')
            ->orderBy('name')
            ->get();

        // Format for ExtJS combo box
        $listData = $departments->map(function ($dept) {
            return [
                'id' => $dept->id,
                'name' => $dept->name,
                'code' => $dept->code,
                'display_name' => $dept->name . ' (' . $dept->code . ')'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $listData,
            'message' => 'Department list retrieved successfully'
        ]);
    }

    /**
     * Recursively load tree children for nested tree structure
     */
    private function loadTreeChildren($query)
    {
        $query->with(['children' => function ($subQuery) {
            $this->loadTreeChildren($subQuery);
        }])->withCount(['employees' => function ($countQuery) {
            $countQuery->where('employment_status', 'active');
        }]);
    }

    /**
     * Format departments data for ExtJS TreePanel
     */
    private function formatTreeData($departments)
    {
        return $departments->map(function ($dept) {
            $node = [
                'id' => $dept->id,
                'text' => $dept->name,
                'name' => $dept->name,
                'code' => $dept->code,
                'description' => $dept->description,
                'location' => $dept->location,
                'budget' => $dept->budget,
                'max_employees' => $dept->max_employees,
                'is_active' => $dept->is_active,
                'employees_count' => $dept->employees_count,
                'manager_id' => $dept->manager_id,
                'parent_id' => $dept->parent_id,
                'leaf' => $dept->children->isEmpty(),
                'expanded' => true, // Auto-expand nodes
                'iconCls' => $dept->is_active ? 'fa fa-building' : 'fa fa-building-o'
            ];

            if ($dept->children->isNotEmpty()) {
                $node['children'] = $this->formatTreeData($dept->children);
            }

            return $node;
        })->toArray();
    }

    /**
     * Get departments grouped by hierarchy level
     */
    private function getDepartmentsByLevel(): array
    {
        $levelStats = [];
        $departments = Department::with('parent')->get();

        foreach ($departments as $dept) {
            $level = 0;
            $current = $dept;
            
            while ($current->parent) {
                $level++;
                $current = $current->parent;
            }

            $levelKey = "level_$level";
            if (!isset($levelStats[$levelKey])) {
                $levelStats[$levelKey] = 0;
            }
            $levelStats[$levelKey]++;
        }

        return $levelStats;
    }
}