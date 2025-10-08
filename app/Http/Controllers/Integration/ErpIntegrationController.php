<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\Integration\ErpIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller for ERP integration management
 */
class ErpIntegrationController extends Controller
{
    private ErpIntegrationService $integrationService;

    public function __construct(ErpIntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
    }

    /**
     * Get integration dashboard data
     */
    public function dashboard(): JsonResponse
    {
        try {
            $status = $this->integrationService->getCachedSyncStatus();
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load integration dashboard', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load integration dashboard'
            ], 500);
        }
    }

    /**
     * Trigger employee synchronization
     */
    public function syncEmployees(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'force' => 'boolean',
                'batch_size' => 'integer|min:1|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $options = $request->only(['force', 'batch_size']);
            $result = $this->integrationService->syncEmployees($options);
            
            // Clear cache to reflect new status
            $this->integrationService->clearSyncStatusCache();
            
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Employee sync API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Employee sync failed'
            ], 500);
        }
    }

    /**
     * Trigger payroll synchronization
     */
    public function syncPayroll(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'force' => 'boolean',
                'batch_size' => 'integer|min:1|max:1000',
                'pay_period' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $options = $request->only(['force', 'batch_size', 'pay_period']);
            $result = $this->integrationService->syncPayroll($options);
            
            $this->integrationService->clearSyncStatusCache();
            
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Payroll sync API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Payroll sync failed'
            ], 500);
        }
    }

    /**
     * Trigger accounting synchronization
     */
    public function syncAccounting(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'force' => 'boolean',
                'batch_size' => 'integer|min:1|max:1000',
                'sync_type' => 'string|in:all,accounts,journal_entries,expense_claims,purchase_orders'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $options = $request->only(['force', 'batch_size', 'sync_type']);
            $result = $this->integrationService->syncAccounting($options);
            
            $this->integrationService->clearSyncStatusCache();
            
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Accounting sync API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Accounting sync failed'
            ], 500);
        }
    }

    /**
     * Trigger full synchronization (all data types)
     */
    public function syncAll(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'force' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->integrationService->syncAll();
            
            $this->integrationService->clearSyncStatusCache();
            
            return response()->json($result, $result['success'] ? 200 : 207); // 207 Multi-Status
        } catch (\Exception $e) {
            Log::error('Full sync API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Full sync failed'
            ], 500);
        }
    }

    /**
     * Get employee sync status
     */
    public function getEmployeeStatus(): JsonResponse
    {
        try {
            $result = $this->integrationService->getEmployeeSyncStatus();
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Get employee status API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get employee status'
            ], 500);
        }
    }

    /**
     * Get payroll sync status
     */
    public function getPayrollStatus(): JsonResponse
    {
        try {
            $result = $this->integrationService->getPayrollSyncStatus();
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Get payroll status API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payroll status'
            ], 500);
        }
    }

    /**
     * Get accounting sync status
     */
    public function getAccountingStatus(): JsonResponse
    {
        try {
            $result = $this->integrationService->getAccountingSyncStatus();
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Get accounting status API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get accounting status'
            ], 500);
        }
    }

    /**
     * Get overall integration status
     */
    public function getStatus(): JsonResponse
    {
        try {
            $result = $this->integrationService->getIntegrationStatus();
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Get integration status API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get integration status'
            ], 500);
        }
    }

    /**
     * Check integration service health
     */
    public function health(): JsonResponse
    {
        try {
            $result = $this->integrationService->checkHealth();
            return response()->json($result, $result['success'] ? 200 : 503);
        } catch (\Exception $e) {
            Log::error('Integration health check API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Integration service health check failed'
            ], 503);
        }
    }

    /**
     * Send employee data for processing
     */
    public function processEmployees(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'employees' => 'required|array',
                'employees.*.employee_id' => 'required|string',
                'employees.*.first_name' => 'required|string',
                'employees.*.last_name' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->integrationService->sendEmployeeData($request->input('employees'));
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Process employees API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process employees'
            ], 500);
        }
    }

    /**
     * Send payroll data for processing
     */
    public function processPayroll(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'payroll' => 'required|array',
                'payroll.*.employee_id' => 'required|string',
                'payroll.*.pay_period' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->integrationService->sendPayrollData($request->input('payroll'));
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Process payroll API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payroll'
            ], 500);
        }
    }

    /**
     * Send accounting data for processing
     */
    public function processAccounting(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'accounting' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->integrationService->sendAccountingData($request->input('accounting'));
            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Process accounting API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process accounting data'
            ], 500);
        }
    }

    /**
     * Clear integration cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->integrationService->clearSyncStatusCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Integration cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Clear cache API error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache'
            ], 500);
        }
    }
}