<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HRPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'AUTHENTICATION_REQUIRED'
            ], 401);
        }

        $user = auth()->user();

        // Check if user has the required permission
        if (!$user->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions to access this resource',
                'error_code' => 'INSUFFICIENT_PERMISSIONS',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}