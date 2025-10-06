<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceMonitoringService
{
    private array $metrics = [];
    private float $startTime;
    private int $startMemory;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    /**
     * Start monitoring a specific operation
     */
    public function startOperation(string $operation): string
    {
        $operationId = uniqid($operation . '_');
        
        $this->metrics[$operationId] = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'query_count_start' => $this->getCurrentQueryCount(),
        ];
        
        return $operationId;
    }

    /**
     * End monitoring a specific operation
     */
    public function endOperation(string $operationId): array
    {
        if (!isset($this->metrics[$operationId])) {
            return ['error' => 'Operation not found'];
        }
        
        $metric = $this->metrics[$operationId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $result = [
            'operation' => $metric['operation'],
            'duration_ms' => round(($endTime - $metric['start_time']) * 1000, 2),
            'memory_usage_mb' => round(($endMemory - $metric['start_memory']) / 1024 / 1024, 2),
            'query_count' => $this->getCurrentQueryCount() - $metric['query_count_start'],
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];
        
        // Log slow operations
        if ($result['duration_ms'] > 1000) { // Log operations over 1 second
            Log::warning('Slow operation detected', $result);
        }
        
        // Log high memory usage
        if ($result['memory_usage_mb'] > 50) { // Log operations using over 50MB
            Log::warning('High memory usage detected', $result);
        }
        
        // Log excessive queries
        if ($result['query_count'] > 20) { // Log operations with over 20 queries
            Log::warning('Excessive database queries detected', $result);
        }
        
        unset($this->metrics[$operationId]);
        
        return $result;
    }

    /**
     * Get current system performance metrics
     */
    public function getSystemMetrics(): array
    {
        return [
            'memory' => $this->getMemoryMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'queue' => $this->getQueueMetrics(),
            'system' => $this->getSystemResourceMetrics(),
        ];
    }

    /**
     * Get memory usage metrics
     */
    private function getMemoryMetrics(): array
    {
        return [
            'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'limit_mb' => $this->getMemoryLimit(),
            'usage_percentage' => $this->getMemoryUsagePercentage(),
        ];
    }

    /**
     * Get database performance metrics
     */
    private function getDatabaseMetrics(): array
    {
        try {
            $connectionStats = DB::select('SHOW STATUS LIKE "Threads_connected"');
            $maxConnections = DB::select('SHOW VARIABLES LIKE "max_connections"');
            $slowQueries = DB::select('SHOW STATUS LIKE "Slow_queries"');
            
            return [
                'connections' => $connectionStats[0]->Value ?? 0,
                'max_connections' => $maxConnections[0]->Value ?? 0,
                'slow_queries' => $slowQueries[0]->Value ?? 0,
                'query_count' => $this->getCurrentQueryCount(),
                'average_query_time' => $this->getAverageQueryTime(),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to fetch database metrics: ' . $e->getMessage()];
        }
    }

    /**
     * Get cache performance metrics
     */
    private function getCacheMetrics(): array
    {
        if (config('cache.default') === 'redis') {
            try {
                $cacheService = app(CacheService::class);
                return $cacheService->getCacheStats();
            } catch (\Exception $e) {
                return ['error' => 'Unable to fetch cache metrics: ' . $e->getMessage()];
            }
        }
        
        return ['type' => config('cache.default'), 'stats' => 'unavailable'];
    }

    /**
     * Get queue performance metrics
     */
    private function getQueueMetrics(): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            $pendingJobs = DB::table('jobs')->count();
            
            return [
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'queues' => $this->getQueueSizes(),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to fetch queue metrics: ' . $e->getMessage()];
        }
    }

    /**
     * Get system resource metrics
     */
    private function getSystemResourceMetrics(): array
    {
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        
        return [
            'load_average' => [
                '1min' => $loadAvg[0] ?? 0,
                '5min' => $loadAvg[1] ?? 0,
                '15min' => $loadAvg[2] ?? 0,
            ],
            'disk_usage' => $this->getDiskUsage(),
            'request_duration_ms' => round((microtime(true) - $this->startTime) * 1000, 2),
            'request_memory_mb' => round((memory_get_usage(true) - $this->startMemory) / 1024 / 1024, 2),
        ];
    }

    /**
     * Monitor database query performance
     */
    public function monitorQueries(): void
    {
        DB::listen(function ($query) {
            $duration = $query->time;
            
            // Log slow queries
            if ($duration > 1000) { // Queries over 1 second
                Log::warning('Slow database query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'duration_ms' => $duration,
                ]);
            }
            
            // Cache query statistics
            $this->cacheQueryStats($query);
        });
    }

    /**
     * Get performance recommendations
     */
    public function getPerformanceRecommendations(): array
    {
        $metrics = $this->getSystemMetrics();
        $recommendations = [];
        
        // Memory recommendations
        if ($metrics['memory']['usage_percentage'] > 80) {
            $recommendations[] = [
                'type' => 'memory',
                'severity' => 'high',
                'message' => 'High memory usage detected. Consider optimizing queries or increasing memory limit.',
            ];
        }
        
        // Database recommendations
        if (isset($metrics['database']['slow_queries']) && $metrics['database']['slow_queries'] > 0) {
            $recommendations[] = [
                'type' => 'database',
                'severity' => 'medium',
                'message' => 'Slow queries detected. Review and optimize database queries.',
            ];
        }
        
        // Cache recommendations
        if (isset($metrics['cache']['hit_rate'])) {
            $hitRate = (float) str_replace('%', '', $metrics['cache']['hit_rate']);
            if ($hitRate < 80) {
                $recommendations[] = [
                    'type' => 'cache',
                    'severity' => 'medium',
                    'message' => 'Low cache hit rate. Review caching strategy.',
                ];
            }
        }
        
        // Queue recommendations
        if ($metrics['queue']['failed_jobs'] > 10) {
            $recommendations[] = [
                'type' => 'queue',
                'severity' => 'high',
                'message' => 'High number of failed jobs. Review job processing and error handling.',
            ];
        }
        
        if ($metrics['queue']['pending_jobs'] > 100) {
            $recommendations[] = [
                'type' => 'queue',
                'severity' => 'medium',
                'message' => 'High number of pending jobs. Consider scaling queue workers.',
            ];
        }
        
        return $recommendations;
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'metrics' => $this->getSystemMetrics(),
            'recommendations' => $this->getPerformanceRecommendations(),
            'active_operations' => count($this->metrics),
            'monitoring_duration_ms' => round((microtime(true) - $this->startTime) * 1000, 2),
        ];
    }

    // Helper methods
    private function getCurrentQueryCount(): int
    {
        return DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
    }

    private function getAverageQueryTime(): float
    {
        $queryLog = DB::getQueryLog();
        if (empty($queryLog)) {
            return 0;
        }
        
        $totalTime = array_sum(array_column($queryLog, 'time'));
        return round($totalTime / count($queryLog), 2);
    }

    private function getMemoryLimit(): string
    {
        $limit = ini_get('memory_limit');
        return $limit === '-1' ? 'unlimited' : $limit;
    }

    private function getMemoryUsagePercentage(): float
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return 0;
        }
        
        $limitBytes = $this->convertToBytes($limit);
        $currentBytes = memory_get_usage(true);
        
        return round(($currentBytes / $limitBytes) * 100, 2);
    }

    private function convertToBytes(string $value): int
    {
        $unit = strtolower(substr($value, -1));
        $number = (int) substr($value, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $number * 1024 * 1024 * 1024;
            case 'm':
                return $number * 1024 * 1024;
            case 'k':
                return $number * 1024;
            default:
                return (int) $value;
        }
    }

    private function getQueueSizes(): array
    {
        $queues = ['default', 'notifications', 'reports', 'high', 'low'];
        $sizes = [];
        
        foreach ($queues as $queue) {
            try {
                $sizes[$queue] = DB::table('jobs')->where('queue', $queue)->count();
            } catch (\Exception $e) {
                $sizes[$queue] = 0;
            }
        }
        
        return $sizes;
    }

    private function getDiskUsage(): array
    {
        $storageDir = storage_path();
        $totalBytes = disk_total_space($storageDir);
        $freeBytes = disk_free_space($storageDir);
        $usedBytes = $totalBytes - $freeBytes;
        
        return [
            'total_gb' => round($totalBytes / 1024 / 1024 / 1024, 2),
            'used_gb' => round($usedBytes / 1024 / 1024 / 1024, 2),
            'free_gb' => round($freeBytes / 1024 / 1024 / 1024, 2),
            'usage_percentage' => round(($usedBytes / $totalBytes) * 100, 2),
        ];
    }

    private function cacheQueryStats(object $query): void
    {
        $key = 'query_stats_' . date('Y-m-d-H');
        $stats = Cache::get($key, ['count' => 0, 'total_time' => 0]);
        
        $stats['count']++;
        $stats['total_time'] += $query->time;
        
        Cache::put($key, $stats, now()->addHour());
    }
}