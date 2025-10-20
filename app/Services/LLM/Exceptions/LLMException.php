<?php

namespace App\Services\LLM\Exceptions;

use Exception;

class LLMException extends Exception
{
    protected string $provider;
    protected ?string $model = null;
    protected array $context = [];

    public function __construct(
        string $message = "",
        int $code = 0,
        ?Exception $previous = null,
        string $provider = 'unknown',
        ?string $model = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->provider = $provider;
        $this->model = $model;
        $this->context = $context;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'provider' => $this->provider,
            'model' => $this->model,
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}

class LLMProviderException extends LLMException
{
    //
}

class LLMConfigurationException extends LLMException
{
    //
}

class LLMRateLimitException extends LLMException
{
    protected int $retryAfter = 0;

    public function setRetryAfter(int $seconds): self
    {
        $this->retryAfter = $seconds;
        return $this;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}

class LLMCostLimitException extends LLMException
{
    protected float $currentCost = 0.0;
    protected float $limit = 0.0;

    public function setCostDetails(float $currentCost, float $limit): self
    {
        $this->currentCost = $currentCost;
        $this->limit = $limit;
        return $this;
    }

    public function getCurrentCost(): float
    {
        return $this->currentCost;
    }

    public function getLimit(): float
    {
        return $this->limit;
    }
}