<?php

namespace App\Services\LLM\Models;

class LLMRequest
{
    protected string $prompt;
    protected ?string $model = null;
    protected array $messages = [];
    protected float $temperature = 0.7;
    protected ?int $maxTokens = null;
    protected array $functions = [];
    protected ?string $systemPrompt = null;
    protected array $parameters = [];
    protected string $type = 'completion'; // completion, chat, function_calling
    protected array $context = [];

    public function __construct(string $prompt = '', array $options = [])
    {
        $this->prompt = $prompt;
        $this->setOptions($options);
    }

    /**
     * Set options from array
     */
    public function setOptions(array $options): self
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * Create chat request
     */
    public static function chat(array $messages, array $options = []): self
    {
        $request = new self('', $options);
        $request->setMessages($messages);
        $request->setType('chat');
        return $request;
    }

    /**
     * Create completion request
     */
    public static function completion(string $prompt, array $options = []): self
    {
        $request = new self($prompt, $options);
        $request->setType('completion');
        return $request;
    }

    /**
     * Create function calling request
     */
    public static function functionCall(array $messages, array $functions, array $options = []): self
    {
        $request = new self('', $options);
        $request->setMessages($messages);
        $request->setFunctions($functions);
        $request->setType('function_calling');
        return $request;
    }

    // Getters
    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getSystemPrompt(): ?string
    {
        return $this->systemPrompt;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    // Setters
    public function setPrompt(string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function setMessages(array $messages): self
    {
        $this->messages = $messages;
        return $this;
    }

    public function addMessage(string $role, string $content): self
    {
        $this->messages[] = ['role' => $role, 'content' => $content];
        return $this;
    }

    public function setTemperature(float $temperature): self
    {
        $this->temperature = max(0.0, min(2.0, $temperature));
        return $this;
    }

    public function setMaxTokens(?int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }

    public function setFunctions(array $functions): self
    {
        $this->functions = $functions;
        return $this;
    }

    public function setSystemPrompt(?string $systemPrompt): self
    {
        $this->systemPrompt = $systemPrompt;
        return $this;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function setParameter(string $key, $value): self
    {
        $this->parameters[$key] = $value;
        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function addContext(string $key, $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Get estimated token count for prompt/messages
     */
    public function getEstimatedTokenCount(): int
    {
        $text = $this->prompt;
        
        if (!empty($this->messages)) {
            $text = '';
            foreach ($this->messages as $message) {
                $text .= ($message['content'] ?? '') . ' ';
            }
        }
        
        if ($this->systemPrompt) {
            $text = $this->systemPrompt . ' ' . $text;
        }
        
        // Rough estimate: ~4 characters per token
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Create cache key for this request
     */
    public function getCacheKey(): string
    {
        $data = [
            'prompt' => $this->prompt,
            'messages' => $this->messages,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'system_prompt' => $this->systemPrompt,
            'type' => $this->type,
            'functions' => $this->functions
        ];
        
        return 'llm_request_' . md5(json_encode($data));
    }

    /**
     * Convert to array for API calls
     */
    public function toArray(): array
    {
        $data = [
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];

        if ($this->model) {
            $data['model'] = $this->model;
        }

        if ($this->type === 'chat' || !empty($this->messages)) {
            $messages = $this->messages;
            if ($this->systemPrompt) {
                array_unshift($messages, ['role' => 'system', 'content' => $this->systemPrompt]);
            }
            $data['messages'] = $messages;
        } else {
            $prompt = $this->prompt;
            if ($this->systemPrompt) {
                $prompt = $this->systemPrompt . "\n\n" . $prompt;
            }
            $data['prompt'] = $prompt;
        }

        if (!empty($this->functions)) {
            $data['functions'] = $this->functions;
            $data['function_call'] = 'auto';
        }

        // Add any additional parameters
        foreach ($this->parameters as $key => $value) {
            if (!isset($data[$key])) {
                $data[$key] = $value;
            }
        }

        return array_filter($data, fn($value) => $value !== null);
    }

    /**
     * Create a similar request for comparison
     */
    public function getSimilarityHash(): string
    {
        // Create hash based on semantic content, ignoring minor variations
        $content = strtolower(trim($this->prompt));
        
        if (!empty($this->messages)) {
            $content = '';
            foreach ($this->messages as $message) {
                $content .= strtolower(trim($message['content'] ?? '')) . ' ';
            }
        }
        
        // Remove extra whitespace and normalize
        $content = preg_replace('/\s+/', ' ', $content);
        
        return hash('sha256', $content . $this->type . ($this->systemPrompt ?? ''));
    }
}