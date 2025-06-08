<?php

namespace Arshia\LarisAIGen\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class OpenRouterAIService
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;

    public function __construct(string $apiKey, string $baseUrl, string $defaultModel)
    {
        $this->apiKey = $apiKey ?? config('laris-ai-gen.openrouter.api_key');
        $this->baseUrl = $baseUrl ?? config('laris-ai-gen.openrouter.base_url');
        $this->defaultModel = $defaultModel ?? config('laris-ai-gen.openrouter.default_model');
        $this->client = new Client(['base_uri' => $this->baseUrl]);
    }

    public function generateCode(string $prompt, ?string $model = null): string
    {
        $model = $model ?? $this->defaultModel;

        try {
            $response = $this->client->post('/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => config('laris-ai-gen.openrouter.temperature'),
                    'max_tokens' => config('laris-ai-gen.openrouter.max_tokens'),
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['choices'][0]['message']['content'] ?? '';
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to generate code: ' . $e->getMessage());
        }
    }
}