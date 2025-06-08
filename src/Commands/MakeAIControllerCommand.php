<?php

namespace Laris\LarisAi\Commands;

use Illuminate\Console\Command;
use Laris\LarisAi\OpenRouterAIService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeAIControllerCommand extends Command
{
    protected $signature = 'laris:ai:make:controller 
                            {name : The name of the controller}
                            {--model= : The related model if any}
                            {--route= : The related route if any}
                            {--api : Generate an API controller}
                            {--invokable : Generate a single action controller}
                            {--resource : Generate a resource controller}
                            {--all : Generate controller with all CRUD methods}
                            {--force : Overwrite existing files}
                            {--test : Generate corresponding test file}
                            {--views : Generate corresponding views}
                            {--middleware= : Specify middleware to apply}
                            {--no-progress : Disable progress animation}';

    protected $description = 'Create a new AI-generated controller with advanced options';

    private $loadingChars = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
    private $loadingIndex = 0;
    private $shouldStopLoading = false;

    public function handle(OpenRouterAIService $aiService)
    {
        $name = $this->argument('name');
        $model = $this->option('model');
        $route = $this->option('route');

        // Validate controller name
        if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $name)) {
            $this->error('Invalid controller name!');
            return;
        }

        // Build advanced prompt
        $this->info('Building prompt...');
        $prompt = $this->buildPrompt($name, $model, $route);

        // Generate code with loading indicator
        $this->info('Generating code using AI...');
        $loading = !$this->option('no-progress');

        if ($loading) {
            $this->startLoading('Generating controller code');
        }

        try {
            $code = $aiService->generateCode($prompt);

            if ($loading) {
                $this->stopLoading();
                $this->line(''); // New line after loading
            }

            // Save controller
            $this->saveController($name, $code);

            // Generate additional files if requested
            $this->generateAdditionalFiles($aiService, $name, $model);

            $this->newLine();
            $this->line('<fg=green>Controller generated successfully!</>');
        } catch (\Exception $e) {
            if ($loading) {
                $this->stopLoading();
            }
            $this->error('Error: ' . $e->getMessage());
            return;
        }
    }

    protected function startLoading(string $message): void
    {
        $this->shouldStopLoading = false;

        $this->output->write("  $message ");

        while (!$this->shouldStopLoading) {
            $this->output->write("\x0D  $message " . $this->loadingChars[$this->loadingIndex]);
            $this->loadingIndex = ($this->loadingIndex + 1) % count($this->loadingChars);
            usleep(100000); // 0.1 second
        }
    }

    protected function stopLoading(): void
    {
        $this->shouldStopLoading = true;
        $this->output->write("\x0D" . str_repeat(' ', 50) . "\x0D"); // Clear line
    }


    protected function buildPrompt(string $name, ?string $model, ?string $route): string
    {
        $prompt = "Generate a complete Laravel controller class named {$name} with namespace.";

        // Add type specification
        $type = 'standard';
        if ($this->option('api')) $type = 'API';
        if ($this->option('invokable')) $type = 'invokable';
        if ($this->option('resource')) $type = 'resource';

        $prompt .= " Controller type: {$type}.";

        // Add model relationship
        if ($model) {
            $prompt .= " The controller should work with {$model} model.";
            $prompt .= " Include model type-hinting and dependency injection.";
        }

        // Add route information
        if ($route) {
            $prompt .= " The controller should handle route '{$route}'.";
        }

        // Add CRUD options
        if ($this->option('all')) {
            $prompt .= " Include full CRUD operations: index, create, store, show, edit, update, destroy.";
        }

        // Add middleware
        if ($middleware = $this->option('middleware')) {
            $prompt .= " Apply these middleware: {$middleware}.";
        }

        // Add best practices
        $prompt .= " Follow Laravel best practices including:";
        $prompt .= " - Proper request validation";
        $prompt .= " - Correct HTTP status codes";
        $prompt .= " - Authorization checks";
        $prompt .= " - Clean, maintainable code";
        $prompt .= " - DocBlock comments for all methods";
        $prompt .= " - Type hints and return types";

        // Add response format for API
        if ($this->option('api')) {
            $prompt .= " Format responses as JSON using Laravel resources or fractal.";
        }

        return $prompt;
    }

    protected function saveController(string $name, string $code): void
    {
        $path = app_path('Http/Controllers/' . $name . '.php');

        // Check if file exists
        if (File::exists($path) && !$this->option('force')) {
            if (!$this->confirm("Controller [{$name}] already exists. Overwrite?")) {
                $this->info('Controller creation canceled.');
                return;
            }
        }

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));

        File::put($path, $code);
        $this->info("Controller created successfully at: {$path}");
    }

    protected function generateAdditionalFiles(OpenRouterAIService $aiService, string $name, ?string $model): void
    {
        // Generate test file
        if ($this->option('test')) {
            $this->generateTestFile($aiService, $name, $model);
        }

        // Generate views
        if ($this->option('views') && !$this->option('api')) {
            $this->generateViews($aiService, $name, $model);
        }
    }

    protected function generateTestFile(OpenRouterAIService $aiService, string $name, ?string $model): void
    {
        $prompt = "Generate a PHPUnit test for Laravel controller {$name}";

        if ($model) {
            $prompt .= " that works with {$model} model";
        }

        $prompt .= ". Include tests for all public methods.";
        $prompt .= " Use Laravel's testing helpers and best practices.";

        $testCode = $aiService->generateCode($prompt);
        $testPath = base_path('tests/Feature/Controllers/' . $name . 'Test.php');

        File::ensureDirectoryExists(dirname($testPath));
        File::put($testPath, $testCode);
        $this->info("Test created successfully at: {$testPath}");
    }

    protected function generateViews(OpenRouterAIService $aiService, string $name, ?string $model): void
    {
        $views = ['index', 'create', 'show', 'edit'];
        $viewPath = resource_path('views/' . Str::kebab($name));

        foreach ($views as $view) {
            $prompt = "Generate a Blade view for {$name} controller's {$view} action";

            if ($model) {
                $prompt .= " displaying {$model} data";
            }

            $prompt .= ". Use Bootstrap 5 for styling and Laravel directives.";

            $viewCode = $aiService->generateCode($prompt);
            $fullPath = $viewPath . '/' . $view . '.blade.php';

            File::ensureDirectoryExists($viewPath);
            File::put($fullPath, $viewCode);
            $this->info("View created: {$fullPath}");
        }
    }
}
