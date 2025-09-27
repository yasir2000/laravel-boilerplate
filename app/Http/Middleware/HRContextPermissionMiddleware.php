<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\HR\Employee;
use App\Models\HR\Department;

class HRContextPermissionMiddleware
{
    /**
     * Handle an incoming request to check contextual HR permissions
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $context): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        $user = auth()->user();
        $canAccess = false;

        // Get the target employee/resource if specified
        $targetEmployeeId = $this->getTargetEmployeeId($request);

        switch ($context) {
            case 'own':
                $canAccess = $this->canAccessOwnData($user, $targetEmployeeId);
                break;
                
            case 'team':
                $canAccess = $this->canAccessTeamData($user, $targetEmployeeId);
                break;
                
            case 'department':
                $canAccess = $this->canAccessDepartmentData($user, $targetEmployeeId);
                break;
                
            case 'all':
                $canAccess = $user->can('hr:employees:manage-all') || 
                           $user->can('hr:attendance:view-all') || 
                           $user->can('hr:leave:view-all');
                break;
                
            default:
                $canAccess = false;
        }

        if (!$canAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied: Insufficient permissions for this context',
                'error_code' => 'CONTEXT_ACCESS_DENIED',
                'context' => $context
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if user can access their own data
     */
    private function canAccessOwnData($user, $targetEmployeeId): bool
    {
        if (!$targetEmployeeId) return true; // No specific target, allow general access
        
        $userEmployee = Employee::where('user_id', $user->id)->first();
        return $userEmployee && $userEmployee->id == $targetEmployeeId;
    }

    /**
     * Check if user can access team data (their direct reports)
     */
    private function canAccessTeamData($user, $targetEmployeeId): bool
    {
        // Check if user has team management permissions
        if (!$user->can('hr:employees:manage-team')) {
            return false;
        }
        
        if (!$targetEmployeeId) return true; // No specific target, allow general team access
        
        $userEmployee = Employee::where('user_id', $user->id)->first();
        if (!$userEmployee) return false;
        
        $targetEmployee = Employee::find($targetEmployeeId);
        if (!$targetEmployee) return false;
        
        // Check if target employee reports to this user
        return $targetEmployee->supervisor_id == $userEmployee->id;
    }

    /**
     * Check if user can access department data
     */
    private function canAccessDepartmentData($user, $targetEmployeeId): bool
    {
        // Check if user has department management permissions
        if (!$user->hasRole(['department-manager', 'hr-manager', 'hr-admin'])) {
            return false;
        }
        
        if (!$targetEmployeeId) return true; // No specific target, allow general department access
        
        $userEmployee = Employee::where('user_id', $user->id)->first();
        $targetEmployee = Employee::find($targetEmployeeId);
        
        if (!$userEmployee || !$targetEmployee) return false;
        
        // Check if they're in the same department or if user manages the department
        $userDepartment = $userEmployee->department;
        $targetDepartment = $targetEmployee->department;
        
        if ($userDepartment->id == $targetDepartment->id) return true;
        
        // Check if user is manager of target's department
        return $targetDepartment->manager_id == $user->id;
    }

    /**
     * Extract target employee ID from request
     */
    private function getTargetEmployeeId(Request $request): ?int
    {
        // Try to get employee ID from route parameters
        if ($request->route('employee')) {
            $employee = $request->route('employee');
            return is_object($employee) ? $employee->id : (int) $employee;
        }

        // Try to get from request parameters
        if ($request->has('employee_id')) {
            return (int) $request->get('employee_id');
        }

        // For attendance/leave requests, try to get employee through the relationship
        if ($request->route('attendance')) {
            $attendance = $request->route('attendance');
            return is_object($attendance) ? $attendance->employee_id : null;
        }

        if ($request->route('leaveRequest')) {
            $leave = $request->route('leaveRequest');
            return is_object($leave) ? $leave->employee_id : null;
        }

        return null;
    }
}