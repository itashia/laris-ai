<?php

namespace Laris\AiGeneration\Services;

class OpenRouterClient
{
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = env('LARIS_OPENROUTER_API_KEY');
        $this->model = env('LARIS_MODEL');
    }

    public function generateCode(string $prompt): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return $response['choices'][0]['message']['content'] ?? "// Error: No content";
    }
}
