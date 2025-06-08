<?php

return [
    'openrouter' => [
        'api_key' => env('LARIS_OPENROUTER_API_KEY'),
        'base_url' => env('LARIS_BASE_URL') ?? 'https://openrouter.ai/api/v1',
        'default_model' => env('LARIS_MODEL') ?? 'deepseek/deepseek-r1-0528-qwen3-8b:free',
        'temperature' => env('LARIS_TEMPERTURE') ??0.7,
        'max_tokens' => env('LARIS_MAX_TOKEN') ??2000,
    ],
    
    'code_templates' => [
        'controller' => [
            'prompt' => env('LARIS_CONTROLLER_PROPMT') ?? "Generate a Laravel controller for {name} with the following requirements: {prompt}. " .
                        "Include proper namespace, use statements, and methods. " .
                        "Follow Laravel best practices and PSR standards.",
        ],
        'model' => [
            'prompt' => env('LARIS_MODEL_PROMPT') ?? "Generate a Laravel Eloquent model for {name} with the following fields: {fields}. " .
                        "Include fillable, casts, relationships ({relations}), and any other necessary configurations. " .
                        "Follow Laravel best practices.",
        ],
    ],
];