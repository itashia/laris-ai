<?php 
namespace Laris\AiGeneration\Commands;

use Illuminate\Console\Command;
use Laris\AiGeneration\Services\OpenRouterClient;

class MakeAIController extends Command
{
    protected $signature = 'laris:ai:make:controller {name}';
    protected $description = 'Generate a controller using AI';

    public function handle()
    {
        $name = $this->argument('name');
        $prompt = "Create a Laravel controller named {$name}Controller that handles basic CRUD operations. Add route definitions and model usage if needed.";

        $ai = new OpenRouterClient();
        $code = $ai->generateCode($prompt);

        // مسیر ساخت فایل
        $path = app_path("Http/Controllers/{$name}Controller.php");
        file_put_contents($path, $code);

        $this->info("Controller {$name}Controller created using AI.");
    }
}
