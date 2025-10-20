<?php

namespace App\Services\LLM\Providers;

use App\Services\LLM\Models\LLMRequest;
use App\Services\LLM\Models\LLMResponse;

interface LLMProviderInterface
{
    /**
     * Get provider name
     */
    public function getName(): string;

    /**
     * Complete a text generation request
     */
    public function complete(LLMRequest $request): LLMResponse;

    /**
     * Stream completion with real-time callback
     */
    public function stream(LLMRequest $request, callable $callback): void;

    /**
     * Process multiple requests in batch
     */
    public function batchComplete(array $requests): array;

    /**
     * Get available models
     */
    public function getAvailableModels(): array;

    /**
     * Get provider capabilities
     */
    public function getCapabilities(): array;

    /**
     * Check if provider supports streaming
     */
    public function supportsStreaming(): bool;

    /**
     * Check if provider supports batching
     */
    public function supportsBatching(): bool;

    /**
     * Health check
     */
    public function healthCheck(): array;

    /**
     * Get model information
     */
    public function getModelInfo(string $model): ?array;

    /**
     * Calculate estimated cost for request
     */
    public function estimateCost(LLMRequest $request): float;
}