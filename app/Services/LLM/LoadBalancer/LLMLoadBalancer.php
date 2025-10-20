<?php

namespace App\Services\LLM\LoadBalancer;

use App\Services\LLM\Providers\LLMProviderInterface;
use App\Services\LLM\Models\LLMRequest;
use App\Services\LLM\Exceptions\LLMException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LLMLoadBalancer
{
    protected array $config;
    protected array $providerMetrics = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->loadMetrics();
    }

    /**
     * Select the best provider for a request
     */
    public function selectProvider(array $providers, LLMRequest $request): LLMProviderInterface
    {
        if (empty($providers)) {
            throw new LLMException('No providers available');
        }

        if (count($providers) === 1) {
            return reset($providers);
        }

        $strategy = $this->config['load_balancing']['strategy'] ?? 'round_robin';

        return match($strategy) {
            'round_robin' => $this->selectRoundRobin($providers),
            'least_cost' => $this->selectLeastCost($providers, $request),
            'fastest_response' => $this->selectFastestResponse($providers),
            'random' => $this->selectRandom($providers),
            'weighted' => $this->selectWeighted($providers),
            default => $this->selectRoundRobin($providers)
        };
    }

    /**
     * Round robin selection
     */
    protected function selectRoundRobin(array $providers): LLMProviderInterface
    {
        $cacheKey = 'llm_round_robin_counter';
        $counter = Cache::get($cacheKey, 0);
        
        $providerNames = array_keys($providers);
        $selectedName = $providerNames[$counter % count($providerNames)];
        
        Cache::put($cacheKey, $counter + 1, 3600);
        
        return $providers[$selectedName];
    }

    /**
     * Select provider with lowest estimated cost
     */
    protected function selectLeastCost(array $providers, LLMRequest $request): LLMProviderInterface
    {
        $lowestCost = PHP_FLOAT_MAX;
        $selectedProvider = null;

        foreach ($providers as $provider) {
            try {
                $cost = $provider->estimateCost($request);
                if ($cost < $lowestCost) {
                    $lowestCost = $cost;
                    $selectedProvider = $provider;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to estimate cost for provider {$provider->getName()}: " . $e->getMessage());
            }
        }

        return $selectedProvider ?? reset($providers);
    }

    /**
     * Select provider with fastest average response time
     */
    protected function selectFastestResponse(array $providers): LLMProviderInterface
    {
        $fastestTime = PHP_FLOAT_MAX;
        $selectedProvider = null;

        foreach ($providers as $name => $provider) {
            $metrics = $this->getProviderMetrics($name);
            $avgResponseTime = $metrics['avg_response_time'] ?? PHP_FLOAT_MAX;
            
            if ($avgResponseTime < $fastestTime) {
                $fastestTime = $avgResponseTime;
                $selectedProvider = $provider;
            }
        }

        return $selectedProvider ?? reset($providers);
    }

    /**
     * Random selection
     */
    protected function selectRandom(array $providers): LLMProviderInterface
    {
        $keys = array_keys($providers);
        $randomKey = $keys[array_rand($keys)];
        return $providers[$randomKey];
    }

    /**
     * Weighted selection based on provider capabilities and performance
     */
    protected function selectWeighted(array $providers): LLMProviderInterface
    {
        $weights = [];
        $totalWeight = 0;

        foreach ($providers as $name => $provider) {
            $weight = $this->calculateProviderWeight($name, $provider);
            $weights[$name] = $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight === 0) {
            return reset($providers);
        }

        $random = mt_rand(1, $totalWeight);
        $currentWeight = 0;

        foreach ($weights as $name => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $providers[$name];
            }
        }

        return reset($providers);
    }

    /**
     * Calculate weight for a provider based on performance metrics
     */
    protected function calculateProviderWeight(string $name, LLMProviderInterface $provider): int
    {
        $metrics = $this->getProviderMetrics($name);
        $weight = 100; // Base weight

        // Adjust based on response time (faster = higher weight)
        $avgResponseTime = $metrics['avg_response_time'] ?? 5.0;
        if ($avgResponseTime < 2.0) {
            $weight += 30;
        } elseif ($avgResponseTime < 5.0) {
            $weight += 15;
        } elseif ($avgResponseTime > 10.0) {
            $weight -= 20;
        }

        // Adjust based on error rate (lower = higher weight)
        $errorRate = $metrics['error_rate'] ?? 0.1;
        if ($errorRate < 0.05) {
            $weight += 20;
        } elseif ($errorRate < 0.1) {
            $weight += 10;
        } elseif ($errorRate > 0.2) {
            $weight -= 30;
        }

        // Adjust based on capabilities
        $capabilities = $provider->getCapabilities();
        if ($capabilities['local_execution'] ?? false) {
            $weight += 10; // Prefer local models for privacy/cost
        }
        if ($capabilities['streaming'] ?? false) {
            $weight += 5;
        }

        // Ensure minimum weight
        return max(1, $weight);
    }

    /**
     * Get provider metrics
     */
    protected function getProviderMetrics(string $providerName): array
    {
        $cacheKey = "llm_metrics_{$providerName}";
        return Cache::get($cacheKey, [
            'avg_response_time' => 5.0,
            'error_rate' => 0.1,
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'total_cost' => 0.0,
        ]);
    }

    /**
     * Update provider metrics
     */
    public function updateProviderMetrics(string $providerName, array $metrics): void
    {
        $cacheKey = "llm_metrics_{$providerName}";
        $existing = $this->getProviderMetrics($providerName);
        
        $updated = array_merge($existing, $metrics);
        
        // Recalculate averages
        if (isset($metrics['response_time'])) {
            $totalRequests = $updated['total_requests'] + 1;
            $currentAvg = $existing['avg_response_time'] ?? 0;
            $updated['avg_response_time'] = (($currentAvg * $existing['total_requests']) + $metrics['response_time']) / $totalRequests;
            $updated['total_requests'] = $totalRequests;
        }

        if (isset($metrics['success'])) {
            if ($metrics['success']) {
                $updated['successful_requests'] = ($existing['successful_requests'] ?? 0) + 1;
            } else {
                $updated['failed_requests'] = ($existing['failed_requests'] ?? 0) + 1;
            }
            
            $total = $updated['successful_requests'] + $updated['failed_requests'];
            $updated['error_rate'] = $total > 0 ? $updated['failed_requests'] / $total : 0;
        }

        if (isset($metrics['cost'])) {
            $updated['total_cost'] = ($existing['total_cost'] ?? 0) + $metrics['cost'];
        }

        Cache::put($cacheKey, $updated, 3600); // Cache for 1 hour
    }

    /**
     * Load cached metrics
     */
    protected function loadMetrics(): void
    {
        // Load any persistent metrics if needed
    }

    /**
     * Check if provider should be excluded due to health issues
     */
    public function isProviderHealthy(string $providerName): bool
    {
        $metrics = $this->getProviderMetrics($providerName);
        
        // Check error rate
        $errorRate = $metrics['error_rate'] ?? 0;
        if ($errorRate > 0.5) { // More than 50% error rate
            return false;
        }

        // Check if provider is in cooldown due to failures
        $cooldownKey = "llm_cooldown_{$providerName}";
        if (Cache::has($cooldownKey)) {
            return false;
        }

        return true;
    }

    /**
     * Put provider in cooldown after repeated failures
     */
    public function setCooldown(string $providerName, int $minutes = 5): void
    {
        $cooldownKey = "llm_cooldown_{$providerName}";
        Cache::put($cooldownKey, true, $minutes * 60);
        
        Log::warning("Provider {$providerName} put in cooldown for {$minutes} minutes due to failures");
    }

    /**
     * Remove provider from cooldown
     */
    public function removeCooldown(string $providerName): void
    {
        $cooldownKey = "llm_cooldown_{$providerName}";
        Cache::forget($cooldownKey);
        
        Log::info("Provider {$providerName} removed from cooldown");
    }

    /**
     * Get all provider metrics
     */
    public function getAllMetrics(): array
    {
        $providers = array_keys($this->config['providers'] ?? []);
        $allMetrics = [];
        
        foreach ($providers as $provider) {
            $allMetrics[$provider] = $this->getProviderMetrics($provider);
        }
        
        return $allMetrics;
    }

    /**
     * Reset metrics for a provider
     */
    public function resetProviderMetrics(string $providerName): void
    {
        $cacheKey = "llm_metrics_{$providerName}";
        Cache::forget($cacheKey);
    }

    /**
     * Reset all metrics
     */
    public function resetAllMetrics(): void
    {
        $providers = array_keys($this->config['providers'] ?? []);
        
        foreach ($providers as $provider) {
            $this->resetProviderMetrics($provider);
        }
    }
}