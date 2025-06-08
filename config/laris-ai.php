<?php

return [
    'api_key' => env('LARIS_OPENROUTER_API_KEY'),
    'default_model' => env('LARIS_MODEL') ?? 'deepseek/deepseek-r1-0528-qwen3-8b:free',
    'temperture' => env('LARIS_TEMPERTURE') ?? 0.7,
    'max_token' => env('LARIS_MAX_TOKEN') ?? 2000,
];
