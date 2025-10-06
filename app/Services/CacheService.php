<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;

class CacheService
{
    // Cache durations in seconds
    const CACHE_FOREVER = -1;
    const CACHE_LONG = 86400; // 24 hours
    const CACHE_MEDIUM = 3600; // 1 hour
    const CACHE_SHORT = 300; // 5 minutes

    // Cache key prefixes
    const USER_PREFIX = 'user';
    const COMPANY_PREFIX = 'company';
    const HR_PREFIX = 'hr';
    const NOTIFICATION_PREFIX = 'notification';
    const WORKFLOW_PREFIX = 'workflow';
    const STATS_PREFIX = 'stats';

    /**
     * Cache user data
     */
    public function cacheUser(Model $user, int $duration = self::CACHE_MEDIUM): void
    {
        $key = $this->getUserCacheKey($user->id);
        
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'roles' => $user->roles->pluck('name')->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'locale' => $user->locale,
            'timezone' => $user->timezone,
            'avatar_url' => $user->getFirstMediaUrl('avatars'),
            'last_login_at' => $user->last_login_at,
        ];

        Cache::put($key, $userData, $duration);
    }

    /**
     * Get cached user data
     */
    public function getCachedUser(string $userId): ?array
    {
        return Cache::get($this->getUserCacheKey($userId));
    }

    /**
     * Clear user cache
     */
    public function clearUserCache(string $userId): void
    {
        Cache::forget($this->getUserCacheKey($userId));
        
        // Clear related caches
        Cache::forget($this->getUserPermissionsCacheKey($userId));
        Cache::forget($this->getUserNotificationsCacheKey($userId));
    }

    /**
     * Cache company data
     */
    public function cacheCompany(Model $company, int $duration = self::CACHE_LONG): void
    {
        $key = $this->getCompanyCacheKey($company->id);
        
        $companyData = [
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'is_active' => $company->is_active,
            'subscription_plan' => $company->subscription_plan,
            'settings' => $company->settings ?? [],
            'employee_count' => $company->users()->count(),
            'department_count' => $company->hrDepartments()->count(),
        ];

        Cache::put($key, $companyData, $duration);
    }

    /**
     * Cache HR statistics
     */
    public function cacheHRStats(string $companyId, array $stats, int $duration = self::CACHE_MEDIUM): void
    {
        $key = $this->getHRStatsCacheKey($companyId);
        Cache::put($key, $stats, $duration);
    }

    /**
     * Get cached HR statistics
     */
    public function getCachedHRStats(string $companyId): ?array
    {
        return Cache::get($this->getHRStatsCacheKey($companyId));
    }

    /**
     * Cache workflow definitions
     */
    public function cacheWorkflowDefinitions(string $companyId, array $definitions, int $duration = self::CACHE_LONG): void
    {
        $key = $this->getWorkflowDefinitionsCacheKey($companyId);
        Cache::put($key, $definitions, $duration);
    }

    /**
     * Cache user permissions
     */
    public function cacheUserPermissions(string $userId, array $permissions, int $duration = self::CACHE_LONG): void
    {
        $key = $this->getUserPermissionsCacheKey($userId);
        Cache::put($key, $permissions, $duration);
    }

    /**
     * Get cached user permissions
     */
    public function getCachedUserPermissions(string $userId): ?array
    {
        return Cache::get($this->getUserPermissionsCacheKey($userId));
    }

    /**
     * Cache notification count
     */
    public function cacheNotificationCount(string $userId, int $count, int $duration = self::CACHE_SHORT): void
    {
        $key = $this->getUserNotificationsCacheKey($userId);
        Cache::put($key, $count, $duration);
    }

    /**
     * Increment notification count
     */
    public function incrementNotificationCount(string $userId): void
    {
        $key = $this->getUserNotificationsCacheKey($userId);
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, self::CACHE_SHORT);
    }

    /**
     * Clear notification count
     */
    public function clearNotificationCount(string $userId): void
    {
        Cache::forget($this->getUserNotificationsCacheKey($userId));
    }

    /**
     * Cache database query results
     */
    public function cacheQuery(string $key, callable $callback, int $duration = self::CACHE_MEDIUM)
    {
        return Cache::remember($key, $duration, $callback);
    }

    /**
     * Cache with tags (Redis only)
     */
    public function cacheWithTags(array $tags, string $key, $value, int $duration = self::CACHE_MEDIUM): void
    {
        if (config('cache.default') === 'redis') {
            Cache::tags($tags)->put($key, $value, $duration);
        } else {
            Cache::put($key, $value, $duration);
        }
    }

    /**
     * Clear cache by tags (Redis only)
     */
    public function clearByTags(array $tags): void
    {
        if (config('cache.default') === 'redis') {
            Cache::tags($tags)->flush();
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        if (config('cache.default') !== 'redis') {
            return ['error' => 'Cache statistics only available for Redis'];
        }

        try {
            $redis = Redis::connection();
            $info = $redis->info();
            
            return [
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'memory_peak' => $info['used_memory_peak_human'] ?? 'N/A',
                'keys' => $redis->dbsize(),
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
                'connected_clients' => $info['connected_clients'] ?? 0,
                'uptime' => $info['uptime_in_seconds'] ?? 0,
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to fetch Redis stats: ' . $e->getMessage()];
        }
    }

    /**
     * Warm up essential caches
     */
    public function warmUpCache(): array
    {
        $warmedCaches = [];

        try {
            // Cache active companies
            $companies = \App\Models\Company::where('is_active', true)->get();
            foreach ($companies as $company) {
                $this->cacheCompany($company);
                $warmedCaches[] = "company_{$company->id}";
            }

            // Cache workflow definitions
            $workflows = \App\Models\WorkflowDefinition::where('is_active', true)->get();
            foreach ($workflows->groupBy('created_by') as $companyWorkflows) {
                $firstWorkflow = $companyWorkflows->first();
                if ($firstWorkflow->creator) {
                    $this->cacheWorkflowDefinitions(
                        $firstWorkflow->creator->company_id, 
                        $companyWorkflows->toArray()
                    );
                    $warmedCaches[] = "workflow_definitions_{$firstWorkflow->creator->company_id}";
                }
            }

            return ['success' => true, 'cached_items' => $warmedCaches];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Clear all application caches
     */
    public function clearAllCaches(): array
    {
        $cleared = [];

        try {
            // Clear different cache types
            Cache::flush();
            $cleared[] = 'application_cache';

            // Clear specific cache tags if using Redis
            if (config('cache.default') === 'redis') {
                $this->clearByTags([self::USER_PREFIX]);
                $this->clearByTags([self::COMPANY_PREFIX]);
                $this->clearByTags([self::HR_PREFIX]);
                $this->clearByTags([self::WORKFLOW_PREFIX]);
                $cleared = array_merge($cleared, ['user_cache', 'company_cache', 'hr_cache', 'workflow_cache']);
            }

            return ['success' => true, 'cleared' => $cleared];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Private helper methods for cache keys
    private function getUserCacheKey(string $userId): string
    {
        return self::USER_PREFIX . "_{$userId}";
    }

    private function getCompanyCacheKey(string $companyId): string
    {
        return self::COMPANY_PREFIX . "_{$companyId}";
    }

    private function getUserPermissionsCacheKey(string $userId): string
    {
        return self::USER_PREFIX . "_permissions_{$userId}";
    }

    private function getUserNotificationsCacheKey(string $userId): string
    {
        return self::NOTIFICATION_PREFIX . "_count_{$userId}";
    }

    private function getHRStatsCacheKey(string $companyId): string
    {
        return self::HR_PREFIX . "_stats_{$companyId}";
    }

    private function getWorkflowDefinitionsCacheKey(string $companyId): string
    {
        return self::WORKFLOW_PREFIX . "_definitions_{$companyId}";
    }

    private function calculateHitRate(array $info): string
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        if ($total === 0) {
            return '0%';
        }
        
        return round(($hits / $total) * 100, 2) . '%';
    }
}