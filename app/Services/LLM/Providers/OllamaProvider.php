<?php

namespace App\Services\LLM\Providers;

use App\Services\LLM\Models\LLMRequest;
use App\Services\LLM\Models\LLMResponse;
use App\Services\LLM\Exceptions\LLMException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaProvider implements LLMProviderInterface
{
    protected array $config;
    protected string $apiBase;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->apiBase = $config['api_base'] ?? 'http://localhost:11434';
    }

    public function getName(): string
    {
        return 'ollama';
    }

    public function complete(LLMRequest $request): LLMResponse
    {
        $startTime = microtime(true);
        
        try {
            $model = $request->getModel() ?? $this->config['default_model'] ?? 'llama3.2:latest';
            
            // Ensure model is available
            $this->ensureModelAvailable($model);
            
            $payload = $this->buildPayload($request, $model);
            
            $response = Http::timeout($this->config['timeout'] ?? 120)
                ->post($this->apiBase . '/api/chat', $payload);

            if (!$response->successful()) {
                throw new LLMException("Ollama API error: " . $response->body());
            }

            $data = $response->json();
            $responseTime = microtime(true) - $startTime;
            
            return $this->parseResponse($data, $model, $responseTime);
            
        } catch (\Exception $e) {
            if ($e instanceof LLMException) {
                throw $e;
            }
            throw new LLMException("Ollama completion failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function stream(LLMRequest $request, callable $callback): void
    {
        $model = $request->getModel() ?? $this->config['default_model'] ?? 'llama3.2:latest';
        
        // Ensure model is available
        $this->ensureModelAvailable($model);
        
        $payload = $this->buildPayload($request, $model);
        $payload['stream'] = true;

        $response = Http::timeout($this->config['timeout'] ?? 120)
            ->post($this->apiBase . '/api/chat', $payload);

        if (!$response->successful()) {
            throw new LLMException("Ollama streaming API error: " . $response->body());
        }

        // Parse JSONL stream
        $lines = explode("\n", $response->body());
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $data = json_decode($line, true);
            if ($data && isset($data['message']['content'])) {
                $content = $data['message']['content'];
                $chunk = LLMResponse::streamChunk($content, $model, 'ollama');
                $callback($chunk);
                
                if ($data['done'] ?? false) {
                    break;
                }
            }
        }
    }

    public function batchComplete(array $requests): array
    {
        // Process sequentially for Ollama
        $responses = [];
        foreach ($requests as $request) {
            $responses[] = $this->complete($request);
        }
        return $responses;
    }

    public function getAvailableModels(): array
    {
        try {
            $response = Http::timeout(10)->get($this->apiBase . '/api/tags');
            
            if ($response->successful()) {
                $data = $response->json();
                return array_column($data['models'] ?? [], 'name');
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch Ollama models: " . $e->getMessage());
        }
        
        // Fallback to configured models
        return array_keys($this->config['models'] ?? []);
    }

    public function getCapabilities(): array
    {
        return [
            'text_generation' => true,
            'chat' => true,
            'function_calling' => true,
            'json_mode' => false,
            'vision' => false, // Depends on model
            'streaming' => true,
            'batching' => false,
            'fine_tuning' => false,
            'local_execution' => true,
            'offline_capable' => true,
        ];
    }

    public function supportsStreaming(): bool
    {
        return true;
    }

    public function supportsBatching(): bool
    {
        return false;
    }

    public function healthCheck(): array
    {
        $startTime = microtime(true);
        
        try {
            $response = Http::timeout(10)->get($this->apiBase . '/api/tags');
            $responseTime = microtime(true) - $startTime;

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'status' => 'healthy',
                    'response_time' => $responseTime,
                    'models_available' => count($data['models'] ?? []),
                    'version' => $this->getOllamaVersion(),
                ];
            }

            return [
                'status' => 'unhealthy',
                'response_time' => $responseTime,
                'error' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'response_time' => microtime(true) - $startTime,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getModelInfo(string $model): ?array
    {
        $configInfo = $this->config['models'][$model] ?? null;
        
        // Try to get live model info from Ollama
        try {
            $response = Http::timeout(10)
                ->post($this->apiBase . '/api/show', ['name' => $model]);
            
            if ($response->successful()) {
                $data = $response->json();
                $liveInfo = [
                    'size' => $data['size'] ?? null,
                    'digest' => $data['digest'] ?? null,
                    'modified_at' => $data['modified_at'] ?? null,
                    'details' => $data['details'] ?? [],
                ];
                
                return $configInfo ? array_merge($configInfo, $liveInfo) : $liveInfo;
            }
        } catch (\Exception $e) {
            Log::debug("Failed to get live Ollama model info: " . $e->getMessage());
        }
        
        return $configInfo;
    }

    public function estimateCost(LLMRequest $request): float
    {
        // Local models have no API costs
        return 0.0;
    }

    /**
     * Ensure model is available, pull if needed
     */
    protected function ensureModelAvailable(string $model): void
    {
        if (!$this->config['auto_pull'] ?? true) {
            return;
        }
        
        try {
            // Check if model exists
            $response = Http::timeout(10)
                ->post($this->apiBase . '/api/show', ['name' => $model]);
            
            if ($response->successful()) {
                return; // Model exists
            }
            
            // Model doesn't exist, try to pull it
            Log::info("Pulling Ollama model: {$model}");
            
            $pullResponse = Http::timeout(300) // 5 minutes for model pull
                ->post($this->apiBase . '/api/pull', ['name' => $model]);
            
            if (!$pullResponse->successful()) {
                throw new LLMException("Failed to pull Ollama model {$model}: " . $pullResponse->body());
            }
            
            Log::info("Successfully pulled Ollama model: {$model}");
            
        } catch (\Exception $e) {
            Log::error("Error ensuring Ollama model availability: " . $e->getMessage());
            // Don't throw here, let the completion request fail naturally
        }
    }

    /**
     * Get Ollama version
     */
    protected function getOllamaVersion(): ?string
    {
        try {
            $response = Http::timeout(5)->get($this->apiBase . '/api/version');
            if ($response->successful()) {
                return $response->json()['version'] ?? null;
            }
        } catch (\Exception $e) {
            // Ignore version check failures
        }
        
        return null;
    }

    /**
     * Build API payload for Ollama
     */
    protected function buildPayload(LLMRequest $request, string $model): array
    {
        $payload = [
            'model' => $model,
            'options' => [
                'temperature' => $request->getTemperature(),
            ],
        ];

        if ($request->getMaxTokens()) {
            $payload['options']['num_predict'] = $request->getMaxTokens();
        }

        // Set keep alive
        if ($this->config['keep_alive'] ?? null) {
            $payload['keep_alive'] = $this->config['keep_alive'];
        }

        // Handle messages
        if (!empty($request->getMessages())) {
            $messages = $request->getMessages();
            
            // Add system message if provided
            if ($request->getSystemPrompt()) {
                array_unshift($messages, [
                    'role' => 'system',
                    'content' => $request->getSystemPrompt()
                ]);
            }
            
            $payload['messages'] = $messages;
        } else {
            // Convert prompt to messages format
            $messages = [];
            
            if ($request->getSystemPrompt()) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $request->getSystemPrompt()
                ];
            }
            
            $messages[] = [
                'role' => 'user',
                'content' => $request->getPrompt()
            ];
            
            $payload['messages'] = $messages;
        }

        // Add function calling if supported by model
        if (!empty($request->getFunctions())) {
            $modelInfo = $this->getModelInfo($model);
            if ($modelInfo && in_array('function_calling', $modelInfo['capabilities'] ?? [])) {
                $payload['tools'] = $request->getFunctions();
            }
        }

        // Add additional options
        foreach ($request->getParameters() as $key => $value) {
            if (!isset($payload['options'][$key])) {
                $payload['options'][$key] = $value;
            }
        }

        return $payload;
    }

    /**
     * Parse Ollama API response
     */
    protected function parseResponse(array $data, string $model, float $responseTime): LLMResponse
    {
        $message = $data['message'] ?? [];
        $content = $message['content'] ?? '';
        
        // Extract usage information
        $usage = [];
        if (isset($data['prompt_eval_count'])) {
            $usage['prompt_tokens'] = $data['prompt_eval_count'];
        }
        if (isset($data['eval_count'])) {
            $usage['completion_tokens'] = $data['eval_count'];
        }
        
        $finishReason = $data['done'] ? 'stop' : 'length';
        
        // Check for function calls
        $functionCall = null;
        if (isset($message['tool_calls'])) {
            $functionCall = $message['tool_calls'][0] ?? null;
        }

        return LLMResponse::success($content, $model, 'ollama', $usage, [
            'response_time' => $responseTime,
            'cost' => 0.0, // Local model, no cost
            'finish_reason' => $finishReason,
            'function_call' => $functionCall,
            'metadata' => [
                'total_duration' => $data['total_duration'] ?? null,
                'load_duration' => $data['load_duration'] ?? null,
                'prompt_eval_duration' => $data['prompt_eval_duration'] ?? null,
                'eval_duration' => $data['eval_duration'] ?? null,
                'context' => $data['context'] ?? null,
            ]
        ]);
    }

    /**
     * Pull a specific model
     */
    public function pullModel(string $model): bool
    {
        try {
            Log::info("Pulling Ollama model: {$model}");
            
            $response = Http::timeout(600) // 10 minutes for large models
                ->post($this->apiBase . '/api/pull', ['name' => $model]);
            
            if ($response->successful()) {
                Log::info("Successfully pulled Ollama model: {$model}");
                return true;
            }
            
            Log::error("Failed to pull Ollama model {$model}: " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error("Error pulling Ollama model {$model}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a model
     */
    public function removeModel(string $model): bool
    {
        try {
            $response = Http::timeout(30)
                ->delete($this->apiBase . '/api/delete', ['name' => $model]);
            
            return $response->successful();
            
        } catch (\Exception $e) {
            Log::error("Error removing Ollama model {$model}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * List running models
     */
    public function getRunningModels(): array
    {
        try {
            $response = Http::timeout(10)->get($this->apiBase . '/api/ps');
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['models'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning("Failed to get running Ollama models: " . $e->getMessage());
        }
        
        return [];
    }
}