<?php

namespace App\Services\LLM\Providers;

use App\Services\LLM\Models\LLMRequest;
use App\Services\LLM\Models\LLMResponse;
use App\Services\LLM\Exceptions\LLMException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicProvider implements LLMProviderInterface
{
    protected array $config;
    protected string $apiKey;
    protected string $apiBase;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->apiKey = $config['api_key'] ?? '';
        $this->apiBase = $config['api_base'] ?? 'https://api.anthropic.com/v1';

        if (empty($this->apiKey)) {
            throw new LLMException('Anthropic API key is required');
        }
    }

    public function getName(): string
    {
        return 'anthropic';
    }

    public function complete(LLMRequest $request): LLMResponse
    {
        $startTime = microtime(true);
        
        try {
            $model = $request->getModel() ?? $this->config['default_model'] ?? 'claude-3-5-haiku-20241022';
            $payload = $this->buildPayload($request, $model);
            
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ])
            ->timeout($this->config['timeout'] ?? 60)
            ->post($this->apiBase . '/messages', $payload);

            if (!$response->successful()) {
                throw new LLMException("Anthropic API error: " . $response->body());
            }

            $data = $response->json();
            $responseTime = microtime(true) - $startTime;
            
            return $this->parseResponse($data, $model, $responseTime);
            
        } catch (\Exception $e) {
            if ($e instanceof LLMException) {
                throw $e;
            }
            throw new LLMException("Anthropic completion failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function stream(LLMRequest $request, callable $callback): void
    {
        $model = $request->getModel() ?? $this->config['default_model'] ?? 'claude-3-5-haiku-20241022';
        $payload = $this->buildPayload($request, $model);
        $payload['stream'] = true;

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])
        ->timeout($this->config['timeout'] ?? 60)
        ->post($this->apiBase . '/messages', $payload);

        if (!$response->successful()) {
            throw new LLMException("Anthropic streaming API error: " . $response->body());
        }

        // Parse SSE stream
        $body = $response->body();
        
        foreach (explode("\n", $body) as $line) {
            if (strpos($line, 'data: ') === 0) {
                $data = substr($line, 6);
                
                if ($data === '[DONE]') {
                    break;
                }
                
                $decoded = json_decode($data, true);
                if ($decoded && $decoded['type'] === 'content_block_delta') {
                    $content = $decoded['delta']['text'] ?? '';
                    if ($content) {
                        $chunk = LLMResponse::streamChunk($content, $model, 'anthropic');
                        $callback($chunk);
                    }
                }
            }
        }
    }

    public function batchComplete(array $requests): array
    {
        // Anthropic doesn't have native batch API, process sequentially
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
            'tool_use' => true,
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
            // Test with a minimal request
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ])
            ->timeout(10)
            ->post($this->apiBase . '/messages', [
                'model' => $this->config['default_model'] ?? 'claude-3-5-haiku-20241022',
                'max_tokens' => 10,
                'messages' => [
                    ['role' => 'user', 'content' => 'Hi']
                ]
            ]);

            $responseTime = microtime(true) - $startTime;

            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'response_time' => $responseTime,
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
        $model = $request->getModel() ?? $this->config['default_model'] ?? 'claude-3-5-haiku-20241022';
        $modelInfo = $this->getModelInfo($model);
        
        if (!$modelInfo || !isset($modelInfo['cost_per_1k_tokens'])) {
            return 0.0;
        }

        $inputTokens = $request->getEstimatedTokenCount();
        $outputTokens = $request->getMaxTokens() ?? 1000;

        $inputCost = ($inputTokens / 1000) * $modelInfo['cost_per_1k_tokens']['input'];
        $outputCost = ($outputTokens / 1000) * $modelInfo['cost_per_1k_tokens']['output'];

        return $inputCost + $outputCost;
    }

    /**
     * Build API payload for Anthropic
     */
    protected function buildPayload(LLMRequest $request, string $model): array
    {
        $payload = [
            'model' => $model,
            'max_tokens' => $request->getMaxTokens() ?? 1000,
        ];

        // Handle messages
        $messages = [];
        $systemPrompt = null;

        if (!empty($request->getMessages())) {
            foreach ($request->getMessages() as $message) {
                if ($message['role'] === 'system') {
                    $systemPrompt = $message['content'];
                } else {
                    $messages[] = $message;
                }
            }
        } else {
            $messages[] = [
                'role' => 'user',
                'content' => $request->getPrompt()
            ];
        }

        // Add system prompt from request if not found in messages
        if (!$systemPrompt && $request->getSystemPrompt()) {
            $systemPrompt = $request->getSystemPrompt();
        }

        $payload['messages'] = $messages;

        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        // Add temperature if not default
        if ($request->getTemperature() !== 0.7) {
            $payload['temperature'] = $request->getTemperature();
        }

        // Add tools if present (Anthropic's function calling)
        if (!empty($request->getFunctions())) {
            $payload['tools'] = $this->convertFunctionsToTools($request->getFunctions());
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
     * Convert OpenAI-style functions to Anthropic tools
     */
    protected function convertFunctionsToTools(array $functions): array
    {
        $tools = [];
        
        foreach ($functions as $function) {
            $tools[] = [
                'name' => $function['name'],
                'description' => $function['description'] ?? '',
                'input_schema' => $function['parameters'] ?? []
            ];
        }
        
        return $tools;
    }

    /**
     * Parse Anthropic API response
     */
    protected function parseResponse(array $data, string $model, float $responseTime): LLMResponse
    {
        $content = '';
        $toolCalls = [];
        
        // Extract content from content blocks
        foreach ($data['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $content .= $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $toolCalls[] = [
                    'name' => $block['name'],
                    'arguments' => json_encode($block['input']),
                    'id' => $block['id'] ?? null
                ];
            }
        }

        $usage = [
            'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
            'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
        ];
        $usage['total_tokens'] = $usage['prompt_tokens'] + $usage['completion_tokens'];

        $finishReason = $data['stop_reason'] ?? 'stop';
        
        // Calculate cost
        $cost = null;
        if (!empty($usage)) {
            $modelInfo = $this->getModelInfo($model);
            if ($modelInfo && isset($modelInfo['cost_per_1k_tokens'])) {
                $inputCost = $usage['prompt_tokens'] / 1000 * $modelInfo['cost_per_1k_tokens']['input'];
                $outputCost = $usage['completion_tokens'] / 1000 * $modelInfo['cost_per_1k_tokens']['output'];
                $cost = $inputCost + $outputCost;
            }
        }

        return LLMResponse::success($content, $model, 'anthropic', $usage, [
            'response_time' => $responseTime,
            'cost' => $cost,
            'finish_reason' => $finishReason,
            'function_call' => !empty($toolCalls) ? $toolCalls[0] : null,
            'tool_calls' => $toolCalls,
            'metadata' => [
                'id' => $data['id'] ?? null,
                'type' => $data['type'] ?? null,
                'role' => $data['role'] ?? null,
                'model' => $data['model'] ?? null,
            ]
        ]);
    }
}