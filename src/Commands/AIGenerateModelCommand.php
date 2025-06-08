<?php

namespace Arshia\LarisAIGen\Commands;

use Illuminate\Console\Command;
use Arshia\LarisAIGen\Services\OpenRouterAIService;
use Illuminate\Support\Facades\File;

class AIGenerateModelCommand extends Command
{
    protected $signature = 'laris:ai:make:model {name} {--fields=} {--relations=} {--migration} {--force}';

    protected $description = 'Generate a model using AI';

    public function handle(OpenRouterAIService $aiService)
    {
        $name = $this->argument('name');
        $fields = $this->option('fields') ?? $this->ask('Enter fields (format: name:string,price:decimal)');
        $relations = $this->option('relations') ?? $this->ask('Enter relations (format: belongsTo:User,hasMany:Post)', false);
        $migration = $this->option('migration');
        $force = $this->option('force');

        $fullPath = app_path('Models/' . $name . '.php');

        if (File::exists($fullPath) && !$force) {
            $this->error("Model already exists at {$fullPath}. Use --force to overwrite.");
            return;
        }

        $template = config('laris-ai-gen.code_templates.model.prompt');
        $fullPrompt = str_replace(
            ['{name}', '{fields}', '{relations}'],
            [$name, $fields, $relations],
            $template
        );

        $this->info("Generating model with AI...");

        try {
            $code = $aiService->generateCode($fullPrompt);
            
            File::ensureDirectoryExists(dirname($fullPath));
            File::put($fullPath, "<?php\n\n{$code}");

            $this->info("Model created successfully at: {$fullPath}");
            $this->line($code);

            if ($migration) {
                $this->call('make:migration', [
                    'name' => "create_" . strtolower(Str::plural($name)) . "_table",
                    '--create' => strtolower(Str::plural($name)),
                ]);
            }
        } catch (\Exception $e) {
            $this->error("Failed to generate model: " . $e->getMessage());
        }
    }
}