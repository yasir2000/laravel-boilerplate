<?php

namespace App\Services\LLM\Providers;

use App\Services\LLM\Models\LLMRequest;
use App\Services\LLM\Models\LLMResponse;
use App\Services\LLM\Exceptions\LLMException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements LLMProviderInterface
{
    protected array $config;
    protected string $apiKey;
    protected string $apiBase;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->apiKey = $config['api_key'] ?? '';
        $this->apiBase = $config['api_base'] ?? 'https://api.openai.com/v1';

        if (empty($this->apiKey)) {
            throw new LLMException('OpenAI API key is required');
        }
    }

    public function getName(): string
    {
        return 'openai';
    }

    public function complete(LLMRequest $request): LLMResponse
    {
        $startTime = microtime(true);
        
        try {
            $model = $request->getModel() ?? $this->config['default_model'] ?? 'gpt-4o-mini';
            $payload = $this->buildPayload($request, $model);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->config['timeout'] ?? 60)
            ->post($this->apiBase . '/chat/completions', $payload);

            if (!$response->successful()) {
                throw new LLMException("OpenAI API error: " . $response->body());
            }

            $data = $response->json();
            $responseTime = microtime(true) - $startTime;
            
            return $this->parseResponse($data, $model, $responseTime);
            
        } catch (\Exception $e) {
            if ($e instanceof LLMException) {
                throw $e;
            }
            throw new LLMException("OpenAI completion failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function stream(LLMRequest $request, callable $callback): void
    {
        $model = $request->getModel() ?? $this->config['default_model'] ?? 'gpt-4o-mini';
        $payload = $this->buildPayload($request, $model);
        $payload['stream'] = true;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout($this->config['timeout'] ?? 60)
        ->post($this->apiBase . '/chat/completions', $payload);

        if (!$response->successful()) {
            throw new LLMException("OpenAI streaming API error: " . $response->body());
        }

        // Parse SSE stream
        $buffer = '';
        $body = $response->body();
        
        foreach (explode("\n", $body) as $line) {
            if (strpos($line, 'data: ') === 0) {
                $data = substr($line, 6);
                
                if ($data === '[DONE]') {
                    break;
                }
                
                $decoded = json_decode($data, true);
                if ($decoded && isset($decoded['choices'][0]['delta']['content'])) {
                    $content = $decoded['choices'][0]['delta']['content'];
                    $chunk = LLMResponse::streamChunk($content, $model, 'openai');
                    $callback($chunk);
                }
            }
        }
    }

    public function batchComplete(array $requests): array
    {
        // OpenAI doesn't have native batch API for real-time, so process sequentially
        $responses = [];
        foreach ($requests as $request) {
            $responses[] = $this->complete($request);
        }
        return $responses;
    }

    public function getAvailableModels(): array
    {
        return array_keys($this->config['models'] ?? []);
    }

    public function getCapabilities(): array
    {
        return [
            'text_generation' => true,
            'chat' => true,
            'function_calling' => true,
            'json_mode' => true,
            'vision' => true,
            'streaming' => true,
            'batching' => false,
            'fine_tuning' => true,
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->timeout(10)
            ->get($this->apiBase . '/models');

            $responseTime = microtime(true) - $startTime;

            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'response_time' => $responseTime,
                    'models_available' => count($response->json()['data'] ?? [])
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
        return $this->config['models'][$model] ?? null;
    }

    public function estimateCost(LLMRequest $request): float
    {
        $model = $request->getModel() ?? $this->config['default_model'] ?? 'gpt-4o-mini';
        $modelInfo = $this->getModelInfo($model);
        
        if (!$modelInfo || !isset($modelInfo['cost_per_1k_tokens'])) {
            return 0.0;
        }

        $inputTokens = $request->getEstimatedTokenCount();
        $outputTokens = $request->getMaxTokens() ?? 1000; // Estimate if not specified

        $inputCost = ($inputTokens / 1000) * $modelInfo['cost_per_1k_tokens']['input'];
        $outputCost = ($outputTokens / 1000) * $modelInfo['cost_per_1k_tokens']['output'];

        return $inputCost + $outputCost;
    }

    /**
     * Build API payload
     */
    protected function buildPayload(LLMRequest $request, string $model): array
    {
        $payload = [
            'model' => $model,
            'temperature' => $request->getTemperature(),
        ];

        if ($request->getMaxTokens()) {
            $payload['max_tokens'] = $request->getMaxTokens();
        }

        // Handle messages vs prompt
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

        // Add function calling if present
        if (!empty($request->getFunctions())) {
            $payload['functions'] = $request->getFunctions();
            $payload['function_call'] = 'auto';
        }

        // Add additional parameters
        foreach ($request->getParameters() as $key => $value) {
            if (!isset($payload[$key])) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    /**
     * Parse OpenAI API response
     */
    protected function parseResponse(array $data, string $model, float $responseTime): LLMResponse
    {
        $choice = $data['choices'][0] ?? [];
        $message = $choice['message'] ?? [];
        
        $content = $message['content'] ?? '';
        $finishReason = $choice['finish_reason'] ?? 'stop';
        
        $usage = $data['usage'] ?? [];
        $functionCall = $message['function_call'] ?? null;
        
        // Calculate cost
        $cost = null;
        if (!empty($usage)) {
            $modelInfo = $this->getModelInfo($model);
            if ($modelInfo && isset($modelInfo['cost_per_1k_tokens'])) {
                $inputCost = ($usage['prompt_tokens'] ?? 0) / 1000 * $modelInfo['cost_per_1k_tokens']['input'];
                $outputCost = ($usage['completion_tokens'] ?? 0) / 1000 * $modelInfo['cost_per_1k_tokens']['output'];
                $cost = $inputCost + $outputCost;
            }
        }

        return LLMResponse::success($content, $model, 'openai', $usage, [
            'response_time' => $responseTime,
            'cost' => $cost,
            'finish_reason' => $finishReason,
            'function_call' => $functionCall,
            'choices' => $data['choices'] ?? [],
            'metadata' => [
                'id' => $data['id'] ?? null,
                'created' => $data['created'] ?? null,
                'system_fingerprint' => $data['system_fingerprint'] ?? null,
            ]
        ]);
    }
}