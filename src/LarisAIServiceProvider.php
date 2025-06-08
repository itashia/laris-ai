<?php

namespace Laris\AiGeneration;

use Illuminate\Support\ServiceProvider;
use Laris\AiGeneration\Commands\MakeAIController;

class LarisAIServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            MakeAIController::class,
        ]);
    }
}
