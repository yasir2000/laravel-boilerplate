<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Service for communicating with the Apache Camel Integration Service
 */
class ErpIntegrationService
{
    private string $integrationServiceUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->integrationServiceUrl = config('integration.service_url', 'http://localhost:8083/integration');
        $this->apiKey = config('integration.api_key', 'default-api-key');
        $this->timeout = config('integration.timeout', 30);
    }

    /**
     * Trigger employee synchronization
     */
    public function syncEmployees(array $options = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->integrationServiceUrl}/camel/employee/sync", $options);

            if ($response->successful()) {
                Log::info('Employee sync triggered successfully', ['response' => $response->json()]);
                return [
                    'success' => true,
                    'message' => 'Employee sync triggered successfully',
                    'data' => $response->json()
                ];
            }

            throw new Exception("Employee sync failed: " . $response->body());
        } catch (Exception $e) {
            Log::error('Employee sync failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Employee sync failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Trigger payroll synchronization
     */
    public function syncPayroll(array $options = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->integrationServiceUrl}/camel/payroll/sync", $options);

            if ($response->successful()) {
                Log::info('Payroll sync triggered successfully', ['response' => $response->json()]);
                return [
                    'success' => true,
                    'message' => 'Payroll sync triggered successfully',
                    'data' => $response->json()
                ];
            }

            throw new Exception("Payroll sync failed: " . $response->body());
        } catch (Exception $e) {
            Log::error('Payroll sync failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Payroll sync failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Trigger accounting synchronization
     */
    public function syncAccounting(array $options = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->integrationServiceUrl}/camel/accounting/sync", $options);

            if ($response->successful()) {
                Log::info('Accounting sync triggered successfully', ['response' => $response->json()]);
                return [
                    'success' => true,
                    'message' => 'Accounting sync triggered successfully',
                    'data' => $response->json()
                ];
            }

            throw new Exception("Accounting sync failed: " . $response->body());
        } catch (Exception $e) {
            Log::error('Accounting sync failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Accounting sync failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get employee synchronization status
     */
    public function getEmployeeSyncStatus(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get("{$this->integrationServiceUrl}/camel/employee/status");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            throw new Exception("Failed to get employee sync status: " . $response->body());
        } catch (Exception $e) {
            Log::error('Failed to get employee sync status', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to get employee sync status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get payroll synchronization status
     */
    public function getPayrollSyncStatus(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get("{$this->integrationServiceUrl}/camel/payroll/status");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            throw new Exception("Failed to get payroll sync status: " . $response->body());
        } catch (Exception $e) {
            Log::error('Failed to get payroll sync status', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to get payroll sync status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get accounting synchronization status
     */
    public function getAccountingSyncStatus(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get("{$this->integrationServiceUrl}/camel/accounting/status");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            throw new Exception("Failed to get accounting sync status: " . $response->body());
        } catch (Exception $e) {
            Log::error('Failed to get accounting sync status', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to get accounting sync status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get overall integration status
     */
    public function getIntegrationStatus(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get("{$this->integrationServiceUrl}/camel/integration/status");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            throw new Exception("Failed to get integration status: " . $response->body());
        } catch (Exception $e) {
            Log::error('Failed to get integration status', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to get integration status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check integration service health
     */
    public function checkHealth(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders())
                ->get("{$this->integrationServiceUrl}/camel/health");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            throw new Exception("Health check failed: " . $response->body());
        } catch (Exception $e) {
            Log::error('Integration service health check failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Integration service health check failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sync all data types (employees, payroll, accounting)
     */
    public function syncAll(): array
    {
        $results = [];
        
        // Sync employees
        $results['employee'] = $this->syncEmployees();
        
        // Sync payroll
        $results['payroll'] = $this->syncPayroll();
        
        // Sync accounting
        $results['accounting'] = $this->syncAccounting();
        
        $allSuccessful = collect($results)->every(function ($result) {
            return $result['success'] ?? false;
        });
        
        return [
            'success' => $allSuccessful,
            'message' => $allSuccessful ? 'All synchronizations completed successfully' : 'Some synchronizations failed',
            'results' => $results
        ];
    }

    /**
     * Get cached sync status for dashboard
     */
    public function getCachedSyncStatus(): array
    {
        return Cache::remember('integration_sync_status', 300, function () {
            return [
                'overall' => $this->getIntegrationStatus(),
                'employee' => $this->getEmployeeSyncStatus(),
                'payroll' => $this->getPayrollSyncStatus(),
                'accounting' => $this->getAccountingSyncStatus(),
                'health' => $this->checkHealth(),
                'last_updated' => now()->toISOString()
            ];
        });
    }

    /**
     * Clear cached sync status
     */
    public function clearSyncStatusCache(): void
    {
        Cache::forget('integration_sync_status');
    }

    /**
     * Get request headers for API calls
     */
    private function getHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->apiKey,
            'User-Agent' => 'Laravel-HR-System/1.0'
        ];
    }

    /**
     * Send employee data to integration service for processing
     */
    public function sendEmployeeData(array $employees): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->integrationServiceUrl}/api/employees/process", [
                    'employees' => $employees
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            throw new Exception("Failed to send employee data: " . $response->body());
        } catch (Exception $e) {
            Log::error('Failed to send employee data', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to send employee data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send payroll data to integration service for processing
     */
    public function sendPayrollData(array $payroll): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->integrationServiceUrl}/api/payroll/process", [
                    'payroll' => $payroll
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            throw new Exception("Failed to send payroll data: " . $response->body());
        } catch (Exception $e) {
            Log::error('Failed to send payroll data', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to send payroll data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send accounting data to integration service for processing
     */
    public function sendAccountingData(array $accounting): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->integrationServiceUrl}/api/accounting/process", [
                    'accounting' => $accounting
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            throw new Exception("Failed to send accounting data: " . $response->body());
        } catch (Exception $e) {
            Log::error('Failed to send accounting data', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to send accounting data: ' . $e->getMessage()
            ];
        }
    }
}