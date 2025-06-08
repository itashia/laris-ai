<?php

namespace Arshia\LarisAIGen;

use Illuminate\Support\ServiceProvider;

class LarisAIGenServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laris-ai.php', 'laris-ai'
        );
        
        $this->app->singleton('laris-ai', function ($app) {
            return new Services\OpenRouterAIService(
                config('laris-ai-gen.openrouter.api_key'),
                config('laris-ai-gen.openrouter.base_url'),
                config('laris-ai-gen.openrouter.default_model')
            );
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\AIGenerateControllerCommand::class,
                Commands\AIGenerateModelCommand::class,
            ]);
            
            $this->publishes([
                __DIR__.'/../config/laris-ai-gen.php' => config_path('laris-ai-gen.php'),
            ], 'laris-ai-gen-config');
        }
    }
}