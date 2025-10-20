<?php

namespace App\Services\LLM\Cache;

use App\Services\LLM\Models\LLMRequest;
use App\Services\LLM\Models\LLMResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LLMCache
{
    protected array $config;
    protected int $ttl;
    protected float $similarityThreshold;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->ttl = $config['performance']['caching']['ttl'] ?? 3600;
        $this->similarityThreshold = $config['performance']['caching']['similarity_threshold'] ?? 0.95;
    }

    /**
     * Get cached response for request
     */
    public function get(LLMRequest $request): ?LLMResponse
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $cacheKey = $request->getCacheKey();
        $cached = Cache::get($cacheKey);

        if ($cached) {
            Log::debug("LLM cache hit for key: {$cacheKey}");
            return $this->deserializeResponse($cached);
        }

        // Check for similar requests
        return $this->findSimilarResponse($request);
    }

    /**
     * Store response in cache
     */
    public function put(LLMRequest $request, LLMResponse $response): void
    {
        if (!$this->isEnabled() || $response->isError()) {
            return;
        }

        $cacheKey = $request->getCacheKey();
        $serialized = $this->serializeResponse($response);
        
        Cache::put($cacheKey, $serialized, $this->ttl);
        
        // Also store similarity hash mapping
        $similarityKey = $this->getSimilarityKey($request);
        $similarityData = Cache::get($similarityKey, []);
        $similarityData[] = [
            'cache_key' => $cacheKey,
            'hash' => $request->getSimilarityHash(),
            'timestamp' => time(),
        ];
        
        // Keep only recent entries
        $similarityData = array_filter($similarityData, function($item) {
            return (time() - $item['timestamp']) < $this->ttl;
        });
        
        Cache::put($similarityKey, $similarityData, $this->ttl);
        
        Log::debug("LLM response cached with key: {$cacheKey}");
    }

    /**
     * Find similar cached response
     */
    protected function findSimilarResponse(LLMRequest $request): ?LLMResponse
    {
        $similarityKey = $this->getSimilarityKey($request);
        $similarityData = Cache::get($similarityKey, []);
        
        if (empty($similarityData)) {
            return null;
        }

        $requestHash = $request->getSimilarityHash();
        
        foreach ($similarityData as $item) {
            $similarity = $this->calculateSimilarity($requestHash, $item['hash']);
            
            if ($similarity >= $this->similarityThreshold) {
                $cached = Cache::get($item['cache_key']);
                if ($cached) {
                    Log::debug("LLM similar cache hit (similarity: {$similarity}) for key: {$item['cache_key']}");
                    return $this->deserializeResponse($cached);
                }
            }
        }

        return null;
    }

    /**
     * Calculate similarity between two request hashes
     */
    protected function calculateSimilarity(string $hash1, string $hash2): float
    {
        if ($hash1 === $hash2) {
            return 1.0;
        }

        // Simple similarity based on hash comparison
        // In a more sophisticated implementation, you might use
        // semantic similarity or other NLP techniques
        $common = 0;
        $length = min(strlen($hash1), strlen($hash2));
        
        for ($i = 0; $i < $length; $i++) {
            if ($hash1[$i] === $hash2[$i]) {
                $common++;
            }
        }
        
        return $length > 0 ? $common / $length : 0.0;
    }

    /**
     * Get similarity key for request type
     */
    protected function getSimilarityKey(LLMRequest $request): string
    {
        return 'llm_similarity_' . md5($request->getType() . '_' . ($request->getSystemPrompt() ?? ''));
    }

    /**
     * Serialize response for caching
     */
    protected function serializeResponse(LLMResponse $response): array
    {
        return [
            'content' => $response->getContent(),
            'model' => $response->getModel(),
            'provider' => $response->getProvider(),
            'usage' => $response->getUsage(),
            'function_call' => $response->getFunctionCall(),
            'metadata' => $response->getMetadata(),
            'response_time' => $response->getResponseTime(),
            'cost' => $response->getCost(),
            'finish_reason' => $response->getFinishReason(),
            'choices' => $response->getChoices(),
            'cached_at' => time(),
        ];
    }

    /**
     * Deserialize cached response
     */
    protected function deserializeResponse(array $data): LLMResponse
    {
        $response = LLMResponse::success(
            $data['content'],
            $data['model'],
            $data['provider'],
            $data['usage'] ?? [],
            [
                'response_time' => $data['response_time'] ?? 0.0,
                'cost' => $data['cost'] ?? null,
                'finish_reason' => $data['finish_reason'] ?? 'stop',
                'function_call' => $data['function_call'] ?? null,
                'choices' => $data['choices'] ?? [],
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'cached' => true,
                    'cached_at' => $data['cached_at'] ?? null,
                ])
            ]
        );

        return $response;
    }

    /**
     * Check if caching is enabled
     */
    protected function isEnabled(): bool
    {
        return $this->config['performance']['caching']['enabled'] ?? false;
    }

    /**
     * Clear cache for specific request
     */
    public function forget(LLMRequest $request): void
    {
        $cacheKey = $request->getCacheKey();
        Cache::forget($cacheKey);
    }

    /**
     * Clear all LLM cache
     */
    public function flush(): void
    {
        // This would require a way to identify all LLM cache keys
        // For now, we'll use a simple approach
        $prefixes = ['llm_request_', 'llm_similarity_'];
        
        foreach ($prefixes as $prefix) {
            // Note: This is a simplified approach
            // In production, you might want to use cache tags or a dedicated cache store
            $keys = Cache::getRedis()->keys($prefix . '*');
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
        
        Log::info('LLM cache flushed');
    }

    /**
     * Get cache statistics
     */
    public function getStatistics(): array
    {
        $stats = [
            'enabled' => $this->isEnabled(),
            'ttl' => $this->ttl,
            'similarity_threshold' => $this->similarityThreshold,
            'estimated_size' => 0,
            'hit_rate' => 0.0,
        ];

        // Try to estimate cache size and hit rate
        try {
            $redis = Cache::getRedis();
            $keys = $redis->keys('llm_request_*');
            $stats['estimated_size'] = count($keys);
            
            // Get hit rate from cache stats if available
            if (method_exists($redis, 'info')) {
                $info = $redis->info('stats');
                if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                    $hits = $info['keyspace_hits'];
                    $misses = $info['keyspace_misses'];
                    $total = $hits + $misses;
                    $stats['hit_rate'] = $total > 0 ? $hits / $total : 0.0;
                }
            }
        } catch (\Exception $e) {
            Log::debug('Could not get cache statistics: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Warm cache with common requests
     */
    public function warmup(array $commonRequests): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        Log::info('Starting LLM cache warmup with ' . count($commonRequests) . ' requests');
        
        foreach ($commonRequests as $requestData) {
            try {
                $request = new LLMRequest($requestData['prompt'] ?? '', $requestData['options'] ?? []);
                
                // Check if already cached
                if (!$this->get($request)) {
                    // This would typically involve making actual API calls
                    // For now, we'll just log the warmup attempt
                    Log::debug('Cache warmup needed for: ' . $request->getCacheKey());
                }
            } catch (\Exception $e) {
                Log::warning('Cache warmup failed for request: ' . $e->getMessage());
            }
        }
        
        Log::info('LLM cache warmup completed');
    }

    /**
     * Clean expired entries
     */
    public function cleanup(): void
    {
        try {
            $redis = Cache::getRedis();
            $keys = $redis->keys('llm_similarity_*');
            
            foreach ($keys as $key) {
                $data = $redis->get($key);
                if ($data) {
                    $decoded = json_decode($data, true);
                    if (is_array($decoded)) {
                        $filtered = array_filter($decoded, function($item) {
                            return (time() - ($item['timestamp'] ?? 0)) < $this->ttl;
                        });
                        
                        if (count($filtered) !== count($decoded)) {
                            $redis->setex($key, $this->ttl, json_encode(array_values($filtered)));
                        }
                    }
                }
            }
            
            Log::debug('LLM cache cleanup completed');
        } catch (\Exception $e) {
            Log::warning('LLM cache cleanup failed: ' . $e->getMessage());
        }
    }
}