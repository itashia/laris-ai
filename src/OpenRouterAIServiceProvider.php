<?php

namespace Laris\LarisAi;

use Illuminate\Support\ServiceProvider;
use Laris\LarisAi\Commands\MakeAIControllerCommand;

class OpenRouterAIServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(OpenRouterAIService::class, function ($app) {
            return new OpenRouterAIService(config('laris-ai.api_key'));
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeAIControllerCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/laris-ai.php' => config_path('laris-ai.php'),
        ], 'laris-ai');
    }
}