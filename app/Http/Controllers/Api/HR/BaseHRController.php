<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

abstract class BaseHRController extends Controller
{
    /**
     * Return a successful JSON response
     */
    protected function success($data = null, string $message = 'Operation successful', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Return an error JSON response
     */
    protected function error(string $message = 'Operation failed', $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    /**
     * Return a paginated JSON response
     */
    protected function paginated($data, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ]
        ]);
    }

    /**
     * Get common query parameters
     */
    protected function getQueryParams(Request $request): array
    {
        return [
            'page' => $request->get('page', 1),
            'per_page' => min($request->get('per_page', 25), 100),
            'search' => $request->get('search'),
            'sort' => $request->get('sort', 'id'),
            'order' => $request->get('order', 'asc'),
            'filters' => $request->get('filters', [])
        ];
    }

    /**
     * Apply search and filters to query
     */
    protected function applyFilters($query, array $searchFields, Request $request)
    {
        $params = $this->getQueryParams($request);

        // Apply search
        if ($params['search']) {
            $query->where(function($q) use ($searchFields, $params) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', "%{$params['search']}%");
                }
            });
        }

        // Apply sorting
        $query->orderBy($params['sort'], $params['order']);

        return $query;
    }
}