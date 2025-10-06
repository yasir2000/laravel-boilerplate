<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Services\PerformanceMonitoringService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PerformanceController extends Controller
{
    private PerformanceMonitoringService $performanceService;
    private CacheService $cacheService;

    public function __construct(
        PerformanceMonitoringService $performanceService,
        CacheService $cacheService
    ) {
        $this->performanceService = $performanceService;
        $this->cacheService = $cacheService;
        
        // Only allow admin users to access performance data
        $this->middleware(['auth:sanctum', 'can:view-system-performance']);
    }

    /**
     * Get current system performance metrics
     */
    public function metrics(): JsonResponse
    {
        $metrics = $this->performanceService->getSystemMetrics();
        
        return response()->json([
            'success' => true,
            'data' => $metrics,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get performance recommendations
     */
    public function recommendations(): JsonResponse
    {
        $recommendations = $this->performanceService->getPerformanceRecommendations();
        
        return response()->json([
            'success' => true,
            'data' => $recommendations,
            'count' => count($recommendations),
        ]);
    }

    /**
     * Generate full performance report
     */
    public function report(): JsonResponse
    {
        $report = $this->performanceService->generatePerformanceReport();
        
        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get cache statistics and management
     */
    public function cache(): JsonResponse
    {
        $stats = $this->cacheService->getCacheStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Warm up application caches
     */
    public function warmCache(): JsonResponse
    {
        $result = $this->cacheService->warmUpCache();
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Cache warmed successfully' : 'Cache warm failed',
            'data' => $result,
        ]);
    }

    /**
     * Clear application caches
     */
    public function clearCache(): JsonResponse
    {
        $result = $this->cacheService->clearAllCaches();
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Caches cleared successfully' : 'Cache clear failed',
            'data' => $result,
        ]);
    }

    /**
     * Get queue status and statistics
     */
    public function queues(): JsonResponse
    {
        try {
            $stats = [
                'failed_jobs' => \DB::table('failed_jobs')->count(),
                'pending_jobs' => \DB::table('jobs')->count(),
                'job_batches' => \DB::table('job_batches')->count(),
                'queue_sizes' => $this->getQueueSizes(),
                'recent_failed_jobs' => $this->getRecentFailedJobs(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch queue statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry failed jobs
     */
    public function retryFailedJobs(Request $request): JsonResponse
    {
        try {
            $jobIds = $request->input('job_ids', []);
            
            if (empty($jobIds)) {
                // Retry all failed jobs
                \Artisan::call('queue:retry', ['id' => 'all']);
                $message = 'All failed jobs have been queued for retry';
            } else {
                // Retry specific jobs
                foreach ($jobIds as $jobId) {
                    \Artisan::call('queue:retry', ['id' => $jobId]);
                }
                $message = count($jobIds) . ' failed jobs have been queued for retry';
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry jobs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear failed jobs
     */
    public function clearFailedJobs(): JsonResponse
    {
        try {
            \Artisan::call('queue:flush');
            
            return response()->json([
                'success' => true,
                'message' => 'All failed jobs have been cleared',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear failed jobs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get database performance statistics
     */
    public function database(): JsonResponse
    {
        try {
            $stats = [
                'connections' => $this->getDatabaseConnections(),
                'slow_queries' => $this->getSlowQueries(),
                'table_sizes' => $this->getTableSizes(),
                'index_usage' => $this->getIndexUsage(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch database statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optimize database tables
     */
    public function optimizeDatabase(): JsonResponse
    {
        try {
            $tables = \DB::select('SHOW TABLES');
            $optimized = [];
            
            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                \DB::statement("OPTIMIZE TABLE `{$tableName}`");
                $optimized[] = $tableName;
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Database optimization completed',
                'optimized_tables' => $optimized,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database optimization failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Private helper methods
    private function getQueueSizes(): array
    {
        $queues = ['default', 'notifications', 'reports', 'high', 'low'];
        $sizes = [];
        
        foreach ($queues as $queue) {
            $sizes[$queue] = \DB::table('jobs')->where('queue', $queue)->count();
        }
        
        return $sizes;
    }

    private function getRecentFailedJobs(): array
    {
        return \DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(10)
            ->get(['id', 'queue', 'exception', 'failed_at'])
            ->toArray();
    }

    private function getDatabaseConnections(): array
    {
        try {
            $result = \DB::select('SHOW STATUS LIKE "Threads_connected"');
            $maxConnections = \DB::select('SHOW VARIABLES LIKE "max_connections"');
            
            return [
                'current' => $result[0]->Value ?? 0,
                'max' => $maxConnections[0]->Value ?? 0,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getSlowQueries(): array
    {
        try {
            $result = \DB::select('SHOW STATUS LIKE "Slow_queries"');
            return [
                'count' => $result[0]->Value ?? 0,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getTableSizes(): array
    {
        try {
            $tables = \DB::select("
                SELECT 
                    table_name,
                    round(((data_length + index_length) / 1024 / 1024), 2) as size_mb,
                    table_rows
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
                LIMIT 10
            ");
            
            return array_map(function ($table) {
                return [
                    'name' => $table->table_name,
                    'size_mb' => $table->size_mb,
                    'rows' => $table->table_rows,
                ];
            }, $tables);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getIndexUsage(): array
    {
        try {
            $indexes = \DB::select("
                SELECT 
                    t.table_name,
                    s.index_name,
                    s.cardinality,
                    s.sub_part
                FROM information_schema.TABLES t
                LEFT JOIN information_schema.STATISTICS s ON t.table_name = s.table_name
                WHERE t.table_schema = DATABASE()
                AND s.index_name IS NOT NULL
                ORDER BY s.cardinality DESC
                LIMIT 20
            ");
            
            return array_map(function ($index) {
                return [
                    'table' => $index->table_name,
                    'index' => $index->index_name,
                    'cardinality' => $index->cardinality,
                ];
            }, $indexes);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}