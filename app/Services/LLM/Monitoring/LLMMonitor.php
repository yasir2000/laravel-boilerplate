<?php

namespace App\Services\LLM\Monitoring;

use App\Services\LLM\Models\LLMRequest;
use App\Services\LLM\Models\LLMResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LLMMonitor
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Record a successful request
     */
    public function recordRequest(string $provider, LLMRequest $request, LLMResponse $response, float $duration): void
    {
        $data = [
            'provider' => $provider,
            'model' => $response->getModel(),
            'request_type' => $request->getType(),
            'prompt_tokens' => $response->getPromptTokens(),
            'completion_tokens' => $response->getCompletionTokens(),
            'total_tokens' => $response->getTokensUsed(),
            'cost' => $response->getCost(),
            'duration' => $duration,
            'quality_score' => $response->getQualityScore(),
            'success' => true,
            'timestamp' => time(),
        ];

        $this->storeMetrics($data);
        $this->updateProviderHealth($provider, true, $duration);
        $this->checkCostLimits($response->getCost() ?? 0.0);
    }

    /**
     * Record an error
     */
    public function recordError(string $provider, \Exception $error): void
    {
        $data = [
            'provider' => $provider,
            'error_type' => get_class($error),
            'error_message' => $error->getMessage(),
            'success' => false,
            'timestamp' => time(),
        ];

        $this->storeMetrics($data);
        $this->updateProviderHealth($provider, false);
        
        Log::error("LLM Error [{$provider}]: " . $error->getMessage(), [
            'provider' => $provider,
            'error_type' => get_class($error),
            'trace' => $error->getTraceAsString(),
        ]);
    }

    /**
     * Record cache hit
     */
    public function recordCacheHit(LLMRequest $request): void
    {
        $data = [
            'event_type' => 'cache_hit',
            'request_type' => $request->getType(),
            'estimated_tokens' => $request->getEstimatedTokenCount(),
            'timestamp' => time(),
        ];

        $this->storeMetrics($data);
        $this->incrementCounter('cache_hits');
    }

    /**
     * Store metrics data
     */
    protected function storeMetrics(array $data): void
    {
        try {
            // Store in database if table exists
            if ($this->hasMetricsTable()) {
                DB::table('llm_metrics')->insert(array_merge($data, [
                    'created_at' => now(),
                ]));
            }

            // Store in cache for quick access
            $cacheKey = 'llm_metrics_' . date('Y-m-d-H');
            $cached = Cache::get($cacheKey, []);
            $cached[] = $data;
            
            // Keep only last 1000 entries per hour
            if (count($cached) > 1000) {
                $cached = array_slice($cached, -1000);
            }
            
            Cache::put($cacheKey, $cached, 3600); // 1 hour
        } catch (\Exception $e) {
            Log::error('Failed to store LLM metrics: ' . $e->getMessage());
        }
    }

    /**
     * Update provider health status
     */
    protected function updateProviderHealth(string $provider, bool $success, float $duration = 0.0): void
    {
        $healthKey = "llm_health_{$provider}";
        $health = Cache::get($healthKey, [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'avg_response_time' => 0.0,
            'last_success' => null,
            'last_failure' => null,
            'consecutive_failures' => 0,
        ]);

        $health['total_requests']++;
        
        if ($success) {
            $health['successful_requests']++;
            $health['last_success'] = time();
            $health['consecutive_failures'] = 0;
            
            // Update average response time
            if ($duration > 0) {
                $totalTime = $health['avg_response_time'] * ($health['successful_requests'] - 1);
                $health['avg_response_time'] = ($totalTime + $duration) / $health['successful_requests'];
            }
        } else {
            $health['failed_requests']++;
            $health['last_failure'] = time();
            $health['consecutive_failures']++;
        }

        Cache::put($healthKey, $health, 3600);

        // Check if provider should be put in cooldown
        if ($health['consecutive_failures'] >= 3) {
            $this->triggerProviderCooldown($provider, $health['consecutive_failures']);
        }
    }

    /**
     * Check if provider is healthy
     */
    public function isProviderHealthy(string $provider): bool
    {
        $healthKey = "llm_health_{$provider}";
        $health = Cache::get($healthKey, ['consecutive_failures' => 0]);
        
        // Provider is unhealthy if it has too many consecutive failures
        if ($health['consecutive_failures'] >= 5) {
            return false;
        }

        // Check if provider is in cooldown
        $cooldownKey = "llm_cooldown_{$provider}";
        return !Cache::has($cooldownKey);
    }

    /**
     * Get last health check time
     */
    public function getLastHealthCheck(string $provider): ?int
    {
        $healthKey = "llm_health_{$provider}";
        $health = Cache::get($healthKey, []);
        
        return max(
            $health['last_success'] ?? 0,
            $health['last_failure'] ?? 0
        ) ?: null;
    }

    /**
     * Get provider metrics
     */
    public function getProviderMetrics(string $provider): array
    {
        $healthKey = "llm_health_{$provider}";
        $health = Cache::get($healthKey, []);
        
        $errorRate = 0.0;
        if ($health['total_requests'] ?? 0 > 0) {
            $errorRate = ($health['failed_requests'] ?? 0) / $health['total_requests'];
        }

        return [
            'total_requests' => $health['total_requests'] ?? 0,
            'successful_requests' => $health['successful_requests'] ?? 0,
            'failed_requests' => $health['failed_requests'] ?? 0,
            'error_rate' => $errorRate,
            'avg_response_time' => $health['avg_response_time'] ?? 0.0,
            'consecutive_failures' => $health['consecutive_failures'] ?? 0,
            'last_success' => $health['last_success'],
            'last_failure' => $health['last_failure'],
            'is_healthy' => $this->isProviderHealthy($provider),
        ];
    }

    /**
     * Get usage statistics
     */
    public function getUsageStatistics(int $days = 7): array
    {
        $stats = [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'total_tokens' => 0,
            'total_cost' => 0.0,
            'avg_response_time' => 0.0,
            'cache_hits' => 0,
            'providers' => [],
            'models' => [],
            'daily_breakdown' => [],
        ];

        try {
            if ($this->hasMetricsTable()) {
                $since = Carbon::now()->subDays($days);
                
                $results = DB::table('llm_metrics')
                    ->where('created_at', '>=', $since)
                    ->get();

                foreach ($results as $row) {
                    if ($row->success ?? false) {
                        $stats['successful_requests']++;
                        $stats['total_tokens'] += $row->total_tokens ?? 0;
                        $stats['total_cost'] += $row->cost ?? 0.0;
                    } else {
                        $stats['failed_requests']++;
                    }

                    if ($row->provider ?? null) {
                        $stats['providers'][$row->provider] = ($stats['providers'][$row->provider] ?? 0) + 1;
                    }

                    if ($row->model ?? null) {
                        $stats['models'][$row->model] = ($stats['models'][$row->model] ?? 0) + 1;
                    }

                    $date = Carbon::parse($row->created_at)->format('Y-m-d');
                    if (!isset($stats['daily_breakdown'][$date])) {
                        $stats['daily_breakdown'][$date] = [
                            'requests' => 0,
                            'cost' => 0.0,
                            'tokens' => 0,
                        ];
                    }
                    $stats['daily_breakdown'][$date]['requests']++;
                    $stats['daily_breakdown'][$date]['cost'] += $row->cost ?? 0.0;
                    $stats['daily_breakdown'][$date]['tokens'] += $row->total_tokens ?? 0;
                }
            }

            $stats['total_requests'] = $stats['successful_requests'] + $stats['failed_requests'];
            $stats['cache_hits'] = $this->getCounter('cache_hits');

        } catch (\Exception $e) {
            Log::error('Failed to get usage statistics: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Get cost analysis
     */
    public function getCostAnalysis(int $days = 30): array
    {
        $analysis = [
            'total_cost' => 0.0,
            'daily_average' => 0.0,
            'cost_by_provider' => [],
            'cost_by_model' => [],
            'cost_trend' => [],
            'budget_status' => [
                'daily_limit' => $this->config['cost_management']['daily_budget_limit'] ?? 0,
                'monthly_limit' => $this->config['cost_management']['monthly_budget_limit'] ?? 0,
                'current_daily' => 0.0,
                'current_monthly' => 0.0,
            ],
        ];

        try {
            if ($this->hasMetricsTable()) {
                $since = Carbon::now()->subDays($days);
                
                $results = DB::table('llm_metrics')
                    ->where('created_at', '>=', $since)
                    ->whereNotNull('cost')
                    ->get();

                foreach ($results as $row) {
                    $cost = $row->cost ?? 0.0;
                    $analysis['total_cost'] += $cost;

                    if ($row->provider) {
                        $analysis['cost_by_provider'][$row->provider] = 
                            ($analysis['cost_by_provider'][$row->provider] ?? 0.0) + $cost;
                    }

                    if ($row->model) {
                        $analysis['cost_by_model'][$row->model] = 
                            ($analysis['cost_by_model'][$row->model] ?? 0.0) + $cost;
                    }

                    $date = Carbon::parse($row->created_at)->format('Y-m-d');
                    $analysis['cost_trend'][$date] = ($analysis['cost_trend'][$date] ?? 0.0) + $cost;
                }

                $analysis['daily_average'] = $days > 0 ? $analysis['total_cost'] / $days : 0.0;

                // Current budget usage
                $today = Carbon::now()->format('Y-m-d');
                $thisMonth = Carbon::now()->format('Y-m');
                
                $analysis['budget_status']['current_daily'] = $analysis['cost_trend'][$today] ?? 0.0;
                
                $monthlyResults = DB::table('llm_metrics')
                    ->where('created_at', '>=', Carbon::now()->startOfMonth())
                    ->whereNotNull('cost')
                    ->sum('cost');
                
                $analysis['budget_status']['current_monthly'] = $monthlyResults ?? 0.0;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get cost analysis: ' . $e->getMessage());
        }

        return $analysis;
    }

    /**
     * Check cost limits and trigger alerts
     */
    protected function checkCostLimits(float $cost): void
    {
        if (!($this->config['cost_management']['enabled'] ?? false) || $cost <= 0) {
            return;
        }

        $dailyLimit = $this->config['cost_management']['daily_budget_limit'] ?? 0;
        $monthlyLimit = $this->config['cost_management']['monthly_budget_limit'] ?? 0;

        if ($dailyLimit > 0) {
            $dailyCost = $this->getDailyCost();
            $dailyPercentage = ($dailyCost / $dailyLimit) * 100;
            
            $this->checkAlertThresholds('daily', $dailyPercentage, $dailyCost, $dailyLimit);
        }

        if ($monthlyLimit > 0) {
            $monthlyCost = $this->getMonthlyCost();
            $monthlyPercentage = ($monthlyCost / $monthlyLimit) * 100;
            
            $this->checkAlertThresholds('monthly', $monthlyPercentage, $monthlyCost, $monthlyLimit);
        }
    }

    /**
     * Check alert thresholds and send notifications
     */
    protected function checkAlertThresholds(string $period, float $percentage, float $current, float $limit): void
    {
        $thresholds = $this->config['cost_management']['cost_alerts']['thresholds'] ?? [];
        
        foreach ($thresholds as $threshold) {
            if ($percentage >= $threshold) {
                $alertKey = "cost_alert_{$period}_{$threshold}";
                
                // Only send alert once per period
                if (!Cache::has($alertKey)) {
                    $this->sendCostAlert($period, $threshold, $current, $limit);
                    Cache::put($alertKey, true, $period === 'daily' ? 1440 : 43200); // 24h or 30 days
                }
            }
        }
    }

    /**
     * Send cost alert
     */
    protected function sendCostAlert(string $period, float $threshold, float $current, float $limit): void
    {
        $message = "LLM Cost Alert: {$period} usage is at {$threshold}% ({$current}/{$limit} USD)";
        
        Log::warning($message);
        
        // Here you would integrate with your notification system
        // For example, send email, Slack notification, etc.
    }

    /**
     * Get daily cost
     */
    protected function getDailyCost(): float
    {
        try {
            if ($this->hasMetricsTable()) {
                return DB::table('llm_metrics')
                    ->whereDate('created_at', Carbon::today())
                    ->sum('cost') ?? 0.0;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get daily cost: ' . $e->getMessage());
        }
        
        return 0.0;
    }

    /**
     * Get monthly cost
     */
    protected function getMonthlyCost(): float
    {
        try {
            if ($this->hasMetricsTable()) {
                return DB::table('llm_metrics')
                    ->where('created_at', '>=', Carbon::now()->startOfMonth())
                    ->sum('cost') ?? 0.0;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get monthly cost: ' . $e->getMessage());
        }
        
        return 0.0;
    }

    /**
     * Trigger provider cooldown
     */
    protected function triggerProviderCooldown(string $provider, int $failures): void
    {
        $cooldownMinutes = min(60, $failures * 5); // Max 1 hour cooldown
        $cooldownKey = "llm_cooldown_{$provider}";
        
        Cache::put($cooldownKey, true, $cooldownMinutes * 60);
        
        Log::warning("Provider {$provider} put in cooldown for {$cooldownMinutes} minutes after {$failures} consecutive failures");
    }

    /**
     * Increment counter
     */
    protected function incrementCounter(string $counter): void
    {
        $key = "llm_counter_{$counter}";
        Cache::increment($key, 1);
        
        // Set expiry if it's a new key
        if (Cache::get($key) === 1) {
            Cache::put($key, 1, 86400); // 24 hours
        }
    }

    /**
     * Get counter value
     */
    protected function getCounter(string $counter): int
    {
        return Cache::get("llm_counter_{$counter}", 0);
    }

    /**
     * Check if metrics table exists
     */
    protected function hasMetricsTable(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable('llm_metrics');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reset all monitoring data
     */
    public function reset(): void
    {
        // Clear cache data
        $keys = Cache::getRedis()->keys('llm_health_*');
        $keys = array_merge($keys, Cache::getRedis()->keys('llm_cooldown_*'));
        $keys = array_merge($keys, Cache::getRedis()->keys('llm_counter_*'));
        $keys = array_merge($keys, Cache::getRedis()->keys('llm_metrics_*'));
        
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }
        
        Log::info('LLM monitoring data reset');
    }
}