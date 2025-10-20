<?php

namespace App\Services\LLM;

use App\Services\LLM\Providers\LLMProviderInterface;
use App\Services\LLM\Providers\OpenAIProvider;
use App\Services\LLM\Providers\AnthropicProvider;
use App\Services\LLM\Providers\GoogleProvider;
use App\Services\LLM\Providers\MistralProvider;
use App\Services\LLM\Providers\OllamaProvider;
use App\Services\LLM\Models\LLMRequest;
use App\Services\LLM\Models\LLMResponse;
use App\Services\LLM\Exceptions\LLMException;
use App\Services\LLM\LoadBalancer\LLMLoadBalancer;
use App\Services\LLM\Cache\LLMCache;
use App\Services\LLM\Monitoring\LLMMonitor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LLMManager
{
    protected array $providers = [];
    protected array $config;
    protected LLMLoadBalancer $loadBalancer;
    protected LLMCache $cache;
    protected LLMMonitor $monitor;

    public function __construct()
    {
        $this->config = config('ai_agents.llm_providers', []);
        $this->loadBalancer = new LLMLoadBalancer($this->config);
        $this->cache = new LLMCache($this->config);
        $this->monitor = new LLMMonitor($this->config);
        
        $this->initializeProviders();
    }

    /**
     * Initialize all enabled LLM providers
     */
    protected function initializeProviders(): void
    {
        $providersConfig = $this->config['providers'] ?? [];

        foreach ($providersConfig as $name => $config) {
            if (!($config['enabled'] ?? false)) {
                continue;
            }

            $provider = $this->createProvider($name, $config);
            if ($provider) {
                $this->providers[$name] = $provider;
                Log::info("LLM Provider initialized: {$name}");
            }
        }

        if (empty($this->providers)) {
            throw new LLMException('No LLM providers are enabled or available');
        }
    }

    /**
     * Create a provider instance based on type
     */
    protected function createProvider(string $name, array $config): ?LLMProviderInterface
    {
        try {
            return match($name) {
                'openai' => new OpenAIProvider($config),
                'anthropic' => new AnthropicProvider($config),
                'google' => new GoogleProvider($config),
                'mistral' => new MistralProvider($config),
                'ollama' => new OllamaProvider($config),
                default => null
            };
        } catch (\Exception $e) {
            Log::error("Failed to initialize {$name} provider: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate completion using the best available LLM
     */
    public function complete(LLMRequest $request, ?string $preferredProvider = null): LLMResponse
    {
        $startTime = microtime(true);
        
        try {
            // Check cache first
            if ($this->config['performance']['caching']['enabled'] ?? false) {
                $cachedResponse = $this->cache->get($request);
                if ($cachedResponse) {
                    $this->monitor->recordCacheHit($request);
                    return $cachedResponse;
                }
            }

            // Select provider
            $provider = $this->selectProvider($request, $preferredProvider);
            
            // Execute request
            $response = $provider->complete($request);
            
            // Record metrics
            $duration = microtime(true) - $startTime;
            $this->monitor->recordRequest($provider->getName(), $request, $response, $duration);
            
            // Cache response
            if ($this->config['performance']['caching']['enabled'] ?? false) {
                $this->cache->put($request, $response);
            }
            
            return $response;
            
        } catch (\Exception $e) {
            $this->monitor->recordError($preferredProvider ?? 'unknown', $e);
            
            // Try fallback if primary fails
            if ($preferredProvider && $this->config['load_balancing']['failover_enabled'] ?? false) {
                return $this->completeFallback($request, $preferredProvider);
            }
            
            throw new LLMException("LLM completion failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Complete using fallback provider
     */
    protected function completeFallback(LLMRequest $request, string $failedProvider): LLMResponse
    {
        Log::warning("Attempting fallback for failed provider: {$failedProvider}");
        
        // Get fallback provider from agent mapping
        $agentMapping = $this->config['agent_llm_mapping'] ?? [];
        $fallbackModel = null;
        
        foreach ($agentMapping as $agent => $mapping) {
            if (($mapping['primary'] ?? '') === $failedProvider) {
                $fallbackModel = $mapping['fallback'] ?? null;
                break;
            }
        }
        
        if (!$fallbackModel) {
            // Use default fallback (first available local provider)
            $fallbackProvider = $this->getFirstAvailableLocalProvider();
            if (!$fallbackProvider) {
                throw new LLMException("No fallback provider available");
            }
        } else {
            [$providerName, $model] = explode(':', $fallbackModel, 2);
            $fallbackProvider = $this->providers[$providerName] ?? null;
            if ($fallbackProvider) {
                $request->setModel($model);
            }
        }
        
        if (!$fallbackProvider) {
            throw new LLMException("Fallback provider not available");
        }
        
        return $fallbackProvider->complete($request);
    }

    /**
     * Select the best provider for a request
     */
    protected function selectProvider(LLMRequest $request, ?string $preferredProvider = null): LLMProviderInterface
    {
        // Use preferred provider if specified and available
        if ($preferredProvider && isset($this->providers[$preferredProvider])) {
            $provider = $this->providers[$preferredProvider];
            if ($this->monitor->isProviderHealthy($preferredProvider)) {
                return $provider;
            }
        }

        // Use load balancer for selection
        return $this->loadBalancer->selectProvider($this->providers, $request);
    }

    /**
     * Get first available local provider (Ollama)
     */
    protected function getFirstAvailableLocalProvider(): ?LLMProviderInterface
    {
        foreach ($this->providers as $name => $provider) {
            if ($name === 'ollama' && $this->monitor->isProviderHealthy($name)) {
                return $provider;
            }
        }
        return null;
    }

    /**
     * Get completion for specific agent
     */
    public function completeForAgent(string $agentName, LLMRequest $request): LLMResponse
    {
        $agentMapping = $this->config['agent_llm_mapping'][$agentName] ?? null;
        
        if (!$agentMapping) {
            // Use default provider
            $defaultProvider = $this->config['default'] ?? 'openai';
            return $this->complete($request, $defaultProvider);
        }

        $primaryModel = $agentMapping['primary'] ?? null;
        if ($primaryModel) {
            [$providerName, $model] = explode(':', $primaryModel, 2);
            $request->setModel($model);
            return $this->complete($request, $providerName);
        }

        return $this->complete($request);
    }

    /**
     * Stream completion (for real-time responses)
     */
    public function stream(LLMRequest $request, callable $callback, ?string $preferredProvider = null): void
    {
        $provider = $this->selectProvider($request, $preferredProvider);
        
        if (!$provider->supportsStreaming()) {
            throw new LLMException("Provider {$provider->getName()} does not support streaming");
        }
        
        $provider->stream($request, $callback);
    }

    /**
     * Get available models for a provider
     */
    public function getAvailableModels(?string $providerName = null): array
    {
        if ($providerName) {
            $provider = $this->providers[$providerName] ?? null;
            return $provider ? $provider->getAvailableModels() : [];
        }

        $allModels = [];
        foreach ($this->providers as $name => $provider) {
            $allModels[$name] = $provider->getAvailableModels();
        }

        return $allModels;
    }

    /**
     * Get provider status and health information
     */
    public function getProvidersStatus(): array
    {
        $status = [];
        
        foreach ($this->providers as $name => $provider) {
            $status[$name] = [
                'enabled' => true,
                'healthy' => $this->monitor->isProviderHealthy($name),
                'models' => $provider->getAvailableModels(),
                'capabilities' => $provider->getCapabilities(),
                'metrics' => $this->monitor->getProviderMetrics($name),
                'last_health_check' => $this->monitor->getLastHealthCheck($name)
            ];
        }

        return $status;
    }

    /**
     * Get system-wide LLM usage statistics
     */
    public function getUsageStatistics(int $days = 7): array
    {
        return $this->monitor->getUsageStatistics($days);
    }

    /**
     * Get cost analysis
     */
    public function getCostAnalysis(int $days = 30): array
    {
        return $this->monitor->getCostAnalysis($days);
    }

    /**
     * Batch process multiple requests
     */
    public function batchComplete(array $requests, ?string $preferredProvider = null): array
    {
        if (!($this->config['performance']['batching']['enabled'] ?? false)) {
            // Process sequentially if batching is disabled
            $responses = [];
            foreach ($requests as $request) {
                $responses[] = $this->complete($request, $preferredProvider);
            }
            return $responses;
        }

        $provider = $this->selectProvider($requests[0], $preferredProvider);
        
        if (!$provider->supportsBatching()) {
            // Process sequentially if provider doesn't support batching
            $responses = [];
            foreach ($requests as $request) {
                $responses[] = $provider->complete($request);
            }
            return $responses;
        }

        return $provider->batchComplete($requests);
    }

    /**
     * Health check for all providers
     */
    public function healthCheck(): array
    {
        $results = [];
        
        foreach ($this->providers as $name => $provider) {
            try {
                $health = $provider->healthCheck();
                $results[$name] = [
                    'status' => 'healthy',
                    'response_time' => $health['response_time'] ?? null,
                    'details' => $health
                ];
            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'details' => null
                ];
            }
        }
        
        return $results;
    }

    /**
     * Get specific provider instance
     */
    public function getProvider(string $name): ?LLMProviderInterface
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * Add custom provider
     */
    public function addProvider(string $name, LLMProviderInterface $provider): void
    {
        $this->providers[$name] = $provider;
    }

    /**
     * Remove provider
     */
    public function removeProvider(string $name): void
    {
        unset($this->providers[$name]);
    }

    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}