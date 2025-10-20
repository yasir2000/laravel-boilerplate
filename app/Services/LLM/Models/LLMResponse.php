<?php

namespace App\Services\LLM\Models;

class LLMResponse
{
    protected string $content;
    protected string $model;
    protected string $provider;
    protected array $usage;
    protected ?array $functionCall = null;
    protected array $metadata = [];
    protected float $responseTime;
    protected ?float $cost = null;
    protected string $finishReason = 'stop';
    protected array $choices = [];

    public function __construct(
        string $content,
        string $model,
        string $provider,
        array $usage = [],
        array $options = []
    ) {
        $this->content = $content;
        $this->model = $model;
        $this->provider = $provider;
        $this->usage = $usage;
        $this->responseTime = $options['response_time'] ?? 0.0;
        $this->cost = $options['cost'] ?? null;
        $this->finishReason = $options['finish_reason'] ?? 'stop';
        $this->functionCall = $options['function_call'] ?? null;
        $this->metadata = $options['metadata'] ?? [];
        $this->choices = $options['choices'] ?? [];
    }

    /**
     * Create successful response
     */
    public static function success(
        string $content,
        string $model,
        string $provider,
        array $usage = [],
        array $options = []
    ): self {
        return new self($content, $model, $provider, $usage, $options);
    }

    /**
     * Create error response
     */
    public static function error(
        string $error,
        string $model,
        string $provider,
        array $options = []
    ): self {
        $options['finish_reason'] = 'error';
        $options['metadata']['error'] = $error;
        return new self($error, $model, $provider, [], $options);
    }

    // Getters
    public function getContent(): string
    {
        return $this->content;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getUsage(): array
    {
        return $this->usage;
    }

    public function getTokensUsed(): int
    {
        return ($this->usage['prompt_tokens'] ?? 0) + ($this->usage['completion_tokens'] ?? 0);
    }

    public function getPromptTokens(): int
    {
        return $this->usage['prompt_tokens'] ?? 0;
    }

    public function getCompletionTokens(): int
    {
        return $this->usage['completion_tokens'] ?? 0;
    }

    public function getFunctionCall(): ?array
    {
        return $this->functionCall;
    }

    public function hasFunctionCall(): bool
    {
        return $this->functionCall !== null;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getResponseTime(): float
    {
        return $this->responseTime;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function getFinishReason(): string
    {
        return $this->finishReason;
    }

    public function getChoices(): array
    {
        return $this->choices;
    }

    public function isError(): bool
    {
        return $this->finishReason === 'error';
    }

    public function isSuccess(): bool
    {
        return !$this->isError();
    }

    // Setters
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function setUsage(array $usage): self
    {
        $this->usage = $usage;
        return $this;
    }

    public function setFunctionCall(?array $functionCall): self
    {
        $this->functionCall = $functionCall;
        return $this;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    public function setResponseTime(float $responseTime): self
    {
        $this->responseTime = $responseTime;
        return $this;
    }

    public function setCost(?float $cost): self
    {
        $this->cost = $cost;
        return $this;
    }

    public function setFinishReason(string $finishReason): self
    {
        $this->finishReason = $finishReason;
        return $this;
    }

    public function setChoices(array $choices): self
    {
        $this->choices = $choices;
        return $this;
    }

    /**
     * Get response quality score (0-100)
     */
    public function getQualityScore(): int
    {
        $score = 100;
        
        // Deduct points for various issues
        if ($this->isError()) {
            return 0;
        }
        
        if ($this->finishReason === 'length') {
            $score -= 20; // Response was cut off
        }
        
        if ($this->responseTime > 10) {
            $score -= 10; // Slow response
        }
        
        if (strlen($this->content) < 10) {
            $score -= 15; // Very short response
        }
        
        return max(0, $score);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'model' => $this->model,
            'provider' => $this->provider,
            'usage' => $this->usage,
            'function_call' => $this->functionCall,
            'metadata' => $this->metadata,
            'response_time' => $this->responseTime,
            'cost' => $this->cost,
            'finish_reason' => $this->finishReason,
            'choices' => $this->choices,
            'quality_score' => $this->getQualityScore(),
            'is_error' => $this->isError(),
            'tokens_used' => $this->getTokensUsed(),
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Get summary string
     */
    public function getSummary(): string
    {
        $summary = "{$this->provider}:{$this->model} - ";
        $summary .= $this->getTokensUsed() . " tokens - ";
        $summary .= number_format($this->responseTime, 2) . "s";
        
        if ($this->cost !== null) {
            $summary .= " - $" . number_format($this->cost, 4);
        }
        
        return $summary;
    }

    /**
     * Create a streaming response chunk
     */
    public static function streamChunk(
        string $content,
        string $model,
        string $provider,
        array $options = []
    ): self {
        $options['streaming'] = true;
        return new self($content, $model, $provider, [], $options);
    }

    /**
     * Check if this is a streaming chunk
     */
    public function isStreamingChunk(): bool
    {
        return $this->metadata['streaming'] ?? false;
    }

    /**
     * Merge with another response (for streaming)
     */
    public function merge(LLMResponse $other): self
    {
        $this->content .= $other->getContent();
        
        // Merge usage if available
        if (!empty($other->getUsage())) {
            $this->usage = $other->getUsage();
        }
        
        // Update metadata
        $this->metadata = array_merge($this->metadata, $other->getMetadata());
        
        return $this;
    }

    /**
     * Get excerpt of content (for logging)
     */
    public function getExcerpt(int $length = 100): string
    {
        if (strlen($this->content) <= $length) {
            return $this->content;
        }
        
        return substr($this->content, 0, $length) . '...';
    }

    /**
     * Check if response contains specific keywords
     */
    public function contains(string $keyword): bool
    {
        return stripos($this->content, $keyword) !== false;
    }

    /**
     * Check if response matches pattern
     */
    public function matches(string $pattern): bool
    {
        return preg_match($pattern, $this->content) === 1;
    }

    /**
     * Extract structured data if response is JSON
     */
    public function extractJson(): ?array
    {
        $decoded = json_decode($this->content, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}