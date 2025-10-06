<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class AdvancedFilterService
{
    public function applyFilters(Builder $query, array $filters, string $model): Builder
    {
        foreach ($filters as $field => $value) {
            if (empty($value) && $value !== '0' && $value !== 0) {
                continue;
            }

            $this->applyFilter($query, $field, $value, $model);
        }

        return $query;
    }

    private function applyFilter(Builder $query, string $field, $value, string $model): void
    {
        // Handle different filter types based on field name patterns
        if (str_ends_with($field, '_from') || str_ends_with($field, '_start')) {
            $this->applyDateFromFilter($query, $field, $value);
        } elseif (str_ends_with($field, '_to') || str_ends_with($field, '_end')) {
            $this->applyDateToFilter($query, $field, $value);
        } elseif (str_ends_with($field, '_range')) {
            $this->applyRangeFilter($query, $field, $value);
        } elseif (str_ends_with($field, '_search')) {
            $this->applySearchFilter($query, $field, $value, $model);
        } elseif (str_ends_with($field, '_in')) {
            $this->applyInFilter($query, $field, $value);
        } elseif (str_ends_with($field, '_not_in')) {
            $this->applyNotInFilter($query, $field, $value);
        } elseif (str_ends_with($field, '_like')) {
            $this->applyLikeFilter($query, $field, $value);
        } elseif (str_ends_with($field, '_greater_than')) {
            $this->applyGreaterThanFilter($query, $field, $value);
        } elseif (str_ends_with($field, '_less_than')) {
            $this->applyLessThanFilter($query, $field, $value);
        } elseif (str_contains($field, '.')) {
            $this->applyRelationFilter($query, $field, $value);
        } else {
            $this->applyBasicFilter($query, $field, $value);
        }
    }

    private function applyDateFromFilter(Builder $query, string $field, $value): void
    {
        $actualField = str_replace(['_from', '_start'], '', $field);
        $query->whereDate($actualField, '>=', $value);
    }

    private function applyDateToFilter(Builder $query, string $field, $value): void
    {
        $actualField = str_replace(['_to', '_end'], '', $field);
        $query->whereDate($actualField, '<=', $value);
    }

    private function applyRangeFilter(Builder $query, string $field, $value): void
    {
        if (!is_array($value) || count($value) !== 2) {
            return;
        }

        $actualField = str_replace('_range', '', $field);
        $query->whereBetween($actualField, $value);
    }

    private function applySearchFilter(Builder $query, string $field, $value, string $model): void
    {
        $searchFields = $this->getSearchFields($model);
        
        $query->where(function ($q) use ($searchFields, $value) {
            foreach ($searchFields as $searchField) {
                if (str_contains($searchField, '.')) {
                    // Relation search
                    [$relation, $column] = explode('.', $searchField, 2);
                    $q->orWhereHas($relation, function ($subQuery) use ($column, $value) {
                        $subQuery->where($column, 'LIKE', "%{$value}%");
                    });
                } else {
                    // Direct column search
                    $q->orWhere($searchField, 'LIKE', "%{$value}%");
                }
            }
        });
    }

    private function applyInFilter(Builder $query, string $field, $value): void
    {
        $actualField = str_replace('_in', '', $field);
        $values = is_array($value) ? $value : explode(',', $value);
        $query->whereIn($actualField, $values);
    }

    private function applyNotInFilter(Builder $query, string $field, $value): void
    {
        $actualField = str_replace('_not_in', '', $field);
        $values = is_array($value) ? $value : explode(',', $value);
        $query->whereNotIn($actualField, $values);
    }

    private function applyLikeFilter(Builder $query, string $field, $value): void
    {
        $actualField = str_replace('_like', '', $field);
        $query->where($actualField, 'LIKE', "%{$value}%");
    }

    private function applyGreaterThanFilter(Builder $query, string $field, $value): void
    {
        $actualField = str_replace('_greater_than', '', $field);
        $query->where($actualField, '>', $value);
    }

    private function applyLessThanFilter(Builder $query, string $field, $value): void
    {
        $actualField = str_replace('_less_than', '', $field);
        $query->where($actualField, '<', $value);
    }

    private function applyRelationFilter(Builder $query, string $field, $value): void
    {
        $parts = explode('.', $field);
        $relation = $parts[0];
        $column = $parts[1];

        $query->whereHas($relation, function ($q) use ($column, $value) {
            $q->where($column, $value);
        });
    }

    private function applyBasicFilter(Builder $query, string $field, $value): void
    {
        if (is_array($value)) {
            $query->whereIn($field, $value);
        } else {
            $query->where($field, $value);
        }
    }

    private function getSearchFields(string $model): array
    {
        return match ($model) {
            'Employee' => [
                'employee_id',
                'user.name',
                'user.email',
                'phone',
                'position',
                'department.name'
            ],
            'Department' => [
                'name',
                'description',
                'code'
            ],
            'Attendance' => [
                'employee.user.name',
                'employee.employee_id',
                'status'
            ],
            'PerformanceEvaluation' => [
                'employee.user.name',
                'employee.employee_id',
                'evaluator.name',
                'evaluation_period'
            ],
            default => ['name', 'title', 'description']
        };
    }

    public function applySorting(Builder $query, ?string $sortBy, ?string $sortDirection = 'asc'): Builder
    {
        if (!$sortBy) {
            return $query;
        }

        $direction = in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'asc';

        if (str_contains($sortBy, '.')) {
            // Relation sorting
            [$relation, $column] = explode('.', $sortBy, 2);
            $query->join(
                str_plural($relation),
                str_plural($relation) . '.id',
                '=',
                $query->getModel()->getTable() . '.' . $relation . '_id'
            )->orderBy(str_plural($relation) . '.' . $column, $direction);
        } else {
            // Direct column sorting
            $query->orderBy($sortBy, $direction);
        }

        return $query;
    }

    public function applyPagination(Builder $query, ?int $page = 1, ?int $perPage = 15): array
    {
        $page = max(1, $page ?? 1);
        $perPage = min(100, max(1, $perPage ?? 15));

        $total = $query->count();
        $totalPages = ceil($total / $perPage);
        
        $items = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ];
    }

    public function buildAdvancedQuery(string $model, array $filters = []): Builder
    {
        $modelClass = "App\\Models\\{$model}";
        $query = $modelClass::query();

        // Apply eager loading based on model
        $with = $this->getEagerLoadRelations($model);
        if (!empty($with)) {
            $query->with($with);
        }

        // Apply filters
        if (!empty($filters['filters'])) {
            $query = $this->applyFilters($query, $filters['filters'], $model);
        }

        // Apply search
        if (!empty($filters['search'])) {
            $this->applySearchFilter($query, 'global_search', $filters['search'], $model);
        }

        // Apply sorting
        if (!empty($filters['sort_by'])) {
            $query = $this->applySorting(
                $query,
                $filters['sort_by'],
                $filters['sort_direction'] ?? 'asc'
            );
        }

        return $query;
    }

    private function getEagerLoadRelations(string $model): array
    {
        return match ($model) {
            'Employee' => ['user', 'department', 'manager'],
            'Department' => ['employees', 'manager'],
            'Attendance' => ['employee.user', 'employee.department'],
            'PerformanceEvaluation' => ['employee.user', 'evaluator'],
            default => []
        };
    }

    public function exportData(Builder $query, string $format = 'csv'): array
    {
        $data = $query->get();

        return match ($format) {
            'json' => [
                'data' => $data->toArray(),
                'format' => 'json',
                'filename' => 'export_' . now()->format('Y-m-d_H-i-s') . '.json'
            ],
            'csv' => [
                'data' => $this->convertToCsv($data),
                'format' => 'csv',
                'filename' => 'export_' . now()->format('Y-m-d_H-i-s') . '.csv'
            ],
            'excel' => [
                'data' => $data->toArray(),
                'format' => 'excel',
                'filename' => 'export_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
            ],
            default => [
                'data' => $data->toArray(),
                'format' => 'json',
                'filename' => 'export_' . now()->format('Y-m-d_H-i-s') . '.json'
            ]
        };
    }

    private function convertToCsv($data): string
    {
        if ($data->isEmpty()) {
            return '';
        }

        $csv = '';
        $headers = array_keys($data->first()->toArray());
        $csv .= implode(',', $headers) . "\n";

        foreach ($data as $row) {
            $csv .= implode(',', array_map(function ($value) {
                return is_array($value) ? json_encode($value) : '"' . str_replace('"', '""', $value) . '"';
            }, $row->toArray())) . "\n";
        }

        return $csv;
    }

    public function getFilterOptions(string $model): array
    {
        return match ($model) {
            'Employee' => [
                'departments' => \App\Models\Department::select('id', 'name')->get(),
                'statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                    ['value' => 'terminated', 'label' => 'Terminated']
                ],
                'positions' => \App\Models\Employee::distinct('position')->pluck('position'),
                'salary_ranges' => [
                    ['value' => '0-30000', 'label' => '$0 - $30,000'],
                    ['value' => '30000-50000', 'label' => '$30,000 - $50,000'],
                    ['value' => '50000-75000', 'label' => '$50,000 - $75,000'],
                    ['value' => '75000-100000', 'label' => '$75,000 - $100,000'],
                    ['value' => '100000+', 'label' => '$100,000+']
                ]
            ],
            'Attendance' => [
                'employees' => \App\Models\Employee::with('user')->get()->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'name' => $employee->user->name,
                        'employee_id' => $employee->employee_id
                    ];
                }),
                'departments' => \App\Models\Department::select('id', 'name')->get(),
                'statuses' => [
                    ['value' => 'present', 'label' => 'Present'],
                    ['value' => 'absent', 'label' => 'Absent'],
                    ['value' => 'late', 'label' => 'Late'],
                    ['value' => 'half_day', 'label' => 'Half Day']
                ]
            ],
            'PerformanceEvaluation' => [
                'employees' => \App\Models\Employee::with('user')->get()->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'name' => $employee->user->name,
                        'employee_id' => $employee->employee_id
                    ];
                }),
                'evaluators' => \App\Models\User::whereHas('roles', function ($q) {
                    $q->whereIn('name', ['manager', 'hr', 'admin']);
                })->select('id', 'name')->get(),
                'score_ranges' => [
                    ['value' => '90-100', 'label' => 'Excellent (90-100)'],
                    ['value' => '80-89', 'label' => 'Good (80-89)'],
                    ['value' => '70-79', 'label' => 'Average (70-79)'],
                    ['value' => '60-69', 'label' => 'Below Average (60-69)'],
                    ['value' => '0-59', 'label' => 'Poor (0-59)']
                ]
            ],
            default => []
        };
    }
}