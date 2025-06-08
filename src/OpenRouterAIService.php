<?php

namespace Laris\LarisAi;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class OpenRouterAIService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://openrouter.ai/api/v1';
    protected string $defaultModel;
    protected int $maxTokens;
    protected float $temperature;
    protected int $timeout = 60;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->defaultModel = config('laris-ai.default_model', 'openai/gpt-4o');
        $this->maxTokens = config('laris-ai.max_tokens', 2000);
        $this->temperature = config('laris-ai.temperature', 0.7);
    }

    public function generateCode(
        string $prompt,
        string $model = null,
        array $options = []
    ): string {
        $cacheKey = $this->getCacheKey($prompt, $model, $options);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'Laris AI'
        ])->timeout($this->timeout)->post($this->baseUrl . '/chat/completions', [
            'model' => $model ?? $this->defaultModel,
            'messages' => $this->prepareMessages($prompt),
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? $this->temperature,
            'top_p' => $options['top_p'] ?? 1,
            'frequency_penalty' => $options['frequency_penalty'] ?? 0,
            'presence_penalty' => $options['presence_penalty'] ?? 0,
            'stream' => false,
        ]);

        if ($response->failed()) {
            $error = $response->json('error.message', $response->body());
            throw new Exception("OpenRouter API Error: " . $error);
        }

        $code = $response->json('choices.0.message.content');
        Cache::put($cacheKey, $code, now()->addHours(24));

        return $code;
    }

    protected function prepareMessages(string $prompt): array
    {
        return [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt()
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
    }

    protected function getSystemPrompt(): string
    {
        return "You are an expert Laravel developer. Generate complete, production-ready PHP code with:
        - Strict type declarations
        - Proper namespacing
        - DocBlock comments
        - Type hints
        - Return type declarations
        - Following Laravel best practices
        - PSR-12 coding standards
        - Security considerations
        - Validation where needed
        - Error handling
        Only respond with the code, no explanations or markdown formatting.";
    }

    protected function getCacheKey(string $prompt, ?string $model, array $options): string
    {
        return 'laris_ai:' . md5($prompt . ($model ?? $this->defaultModel) . json_encode($options));
    }

    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function setDefaultModel(string $model): self
    {
        $this->defaultModel = $model;
        return $this;
    }

    public function setMaxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }

    public function setTemperature(float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }
}