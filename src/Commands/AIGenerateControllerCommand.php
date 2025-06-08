<?php

namespace Arshia\LarisAIGen\Commands;

use Illuminate\Console\Command;
use Arshia\LarisAIGen\Services\OpenRouterAIService;
use Illuminate\Support\Facades\{File, Http, Artisan};
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;

class AIGenerateControllerCommand extends Command
{
    protected $signature = 'laris:ai:make:controller 
        {name : The name of the controller}
        {--prompt= : Description of what the controller should do}
        {--model= : The related model name}
        {--api : Generate API controller with JSON responses}
        {--invokable : Generate single action controller}
        {--resource : Generate resource controller}
        {--force : Overwrite existing files}
        {--test : Generate corresponding test file}
        {--routes : Add routes to routes file}
        {--dry-run : Show output without saving files}
        {--config= : Path to custom YAML config file}
        {--lang= : Generate language files for validation}
        {--cache : Add caching layer}
        {--queue : Add queueable jobs}
        {--auth : Add authentication middleware}
        {--swagger : Generate OpenAPI/Swagger annotations}
        {--version=v1 : API version prefix}
        {--service : Generate corresponding service class}
        {--dto : Generate Data Transfer Objects}
        {--policy : Generate authorization policy}';

    protected $description = 'Generate an advanced AI-powered Laravel controller with all bells and whistles';

    private array $config = [];
    private string $stubPath = __DIR__.'/../Stubs/';

    public function handle(OpenRouterAIService $aiService)
    {
        $this->loadCustomConfig();
        $this->displayAsciiArt();
        
        $controllerData = $this->gatherInputs();
        $generatedFiles = $this->generateController($aiService, $controllerData);

        if ($this->option('dry-run')) {
            $this->displayDryRunResults($generatedFiles);
            return;
        }

        $this->saveGeneratedFiles($generatedFiles);
        $this->generateAdditionalFiles($controllerData);
        $this->finalizeSetup($controllerData);
    }

    private function loadCustomConfig(): void
    {
        if ($this->option('config')) {
            try {
                $this->config = Yaml::parseFile($this->option('config'));
                $this->info('Loaded custom configuration from: '.$this->option('config'));
            } catch (\Exception $e) {
                $this->error('Failed to load config file: '.$e->getMessage());
            }
        }
    }

    private function displayAsciiArt(): void
    {
        $this->line("<fg=magenta>        
  _               _____  _____  _____ 
 | |        /\   |  __ \|_   _|/ ____|
 | |       /  \  | |__) | | | | (___  
 | |      / /\ \ |  _  /  | |  \___ \ 
 | |____ / ____ \| | \ \ _| |_ ____) |
 |______/_/    \_\_|  \_\_____|_____/                 
</>");
        $this->line("<fg=cyan>Laravel AI Code Generator - Controller Wizard</>");
        $this->line("");
    }

    private function gatherInputs(): array
    {
        $name = $this->argument('name');
        $prompt = $this->option('prompt') ?? $this->askWithCompletion(
            'Describe your controller requirements',
            $this->getPromptSuggestions(),
            null
        );

        return [
            'name' => $name,
            'prompt' => $prompt,
            'model' => $this->option('model') ?? $this->askForModel(),
            'is_api' => $this->option('api'),
            'is_invokable' => $this->option('invokable'),
            'is_resource' => $this->option('resource'),
            'version' => $this->option('version'),
            'features' => $this->determineFeatures(),
            'namespace' => $this->getNamespace($name),
            'className' => $this->getClassName($name),
            'path' => $this->getPath($name),
            'route_name' => Str::kebab($this->getClassName($name)),
        ];
    }

    private function askForModel(): ?string
    {
        if ($this->confirm('Would you like to associate a model with this controller?', true)) {
            $models = $this->getAvailableModels();
            return $this->choice('Select a model', $models);
        }
        return null;
    }

    private function getAvailableModels(): array
    {
        $modelPath = app_path('Models');
        $files = File::files($modelPath);
        
        return collect($files)
            ->map(fn($file) => str_replace('.php', '', $file->getFilename()))
            ->prepend('Create new model')
            ->toArray();
    }

    private function determineFeatures(): array
    {
        $features = [];
        
        if ($this->option('cache')) $features[] = 'caching';
        if ($this->option('queue')) $features[] = 'queues';
        if ($this->option('auth')) $features[] = 'authentication';
        if ($this->option('swagger')) $features[] = 'swagger';
        if ($this->option('service')) $features[] = 'service-layer';
        if ($this->option('dto')) $features[] = 'data-transfer-objects';
        if ($this->option('policy')) $features[] = 'authorization-policies';
        
        if (empty($features)) {
            $features = $this->choice(
                'Select additional features (comma separated)',
                [
                    'caching',
                    'queues',
                    'authentication',
                    'swagger',
                    'service-layer',
                    'data-transfer-objects',
                    'authorization-policies'
                ],
                null,
                null,
                true
            );
        }
        
        return $features;
    }

    private function generateController(OpenRouterAIService $aiService, array $data): array
    {
        $this->info("🧠 Generating smart controller with AI...");
        $this->newLine();
        
        $template = $this->getEnhancedPromptTemplate($data);
        $fullPrompt = $this->buildFullPrompt($template, $data);

        $this->line("<fg=yellow>🤖 AI Prompt:</>");
        $this->line("<fg=gray>$fullPrompt</>");
        $this->newLine();

        $code = $aiService->generateCode($fullPrompt);
        $processedCode = $this->postProcessCode($code, $data);

        return [
            'controller' => [
                'path' => $data['path'],
                'content' => "<?php\n\n{$processedCode}",
                'type' => 'controller'
            ],
            'metadata' => $this->extractMetadata($processedCode)
        ];
    }

    private function getEnhancedPromptTemplate(array $data): string
    {
        $baseTemplate = config('laris-ai-gen.code_templates.controller.prompt');
        
        $template = "You are a senior Laravel developer. Generate a complete controller with these requirements:\n";
        $template .= "Controller Name: {name}\n";
        $template .= "Purpose: {prompt}\n";
        
        if ($data['model']) {
            $template .= "Related Model: {model}\n";
        }
        
        if ($data['is_api']) {
            $template .= "Type: API Controller (JSON responses)\n";
            $template .= "API Version: {version}\n";
        } else {
            $template .= "Type: Web Controller\n";
        }
        
        if ($data['is_invokable']) {
            $template .= "Style: Single Action (invokable)\n";
        } elseif ($data['is_resource']) {
            $template .= "Style: Resource Controller\n";
        }
        
        if (!empty($data['features'])) {
            $template .= "Features: " . implode(', ', $data['features']) . "\n";
        }
        
        $template .= "\nRequirements:\n";
        $template .= "- Use strict typing\n";
        $template .= "- Follow PSR-12 coding standards\n";
        $template .= "- Include PHPDoc blocks for all methods\n";
        $template .= "- Add proper validation\n";
        $template .= "- Implement error handling\n";
        
        if (in_array('swagger', $data['features'])) {
            $template .= "- Include OpenAPI/Swagger annotations\n";
        }
        
        $template .= "\nOutput the complete controller code only, no explanations.\n";
        
        return $template;
    }

    private function buildFullPrompt(string $template, array $data): string
    {
        $replacements = [
            '{name}' => $data['className'],
            '{prompt}' => $data['prompt'],
            '{model}' => $data['model'],
            '{version}' => $data['version'],
            '{namespace}' => $data['namespace'],
            '{features}' => implode(', ', $data['features']),
        ];
        
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    private function postProcessCode(string $code, array $data): string
    {
        // Add namespace if missing
        if (!Str::contains($code, 'namespace')) {
            $code = "namespace {$data['namespace']};\n\n" . $code;
        }
        
        // Ensure proper class name
        $code = preg_replace(
            '/class\s+\w+/',
            "class {$data['className']}",
            $code
        );
        
        // Add strict types
        if (!Str::startsWith($code, '<?php declare(strict_types=1);')) {
            $code = str_replace('<?php', '<?php declare(strict_types=1);', $code);
        }
        
        return $code;
    }

    private function extractMetadata(string $code): array
    {
        $metadata = [
            'methods' => [],
            'routes' => [],
            'dependencies' => []
        ];
        
        // Extract methods
        preg_match_all('/public function (\w+)\(/', $code, $matches);
        $metadata['methods'] = $matches[1] ?? [];
        
        // Extract used classes
        preg_match_all('/use ([\w\\\\]+);/', $code, $matches);
        $metadata['dependencies'] = $matches[1] ?? [];
        
        return $metadata;
    }

    private function saveGeneratedFiles(array $generatedFiles): void
    {
        $this->ensureDirectoryExists($generatedFiles['controller']['path']);
        
        File::put(
            $generatedFiles['controller']['path'],
            $generatedFiles['controller']['content']
        );
        
        $this->info("✅ Controller generated successfully at:");
        $this->line("<fg=green>{$generatedFiles['controller']['path']}</>");
    }

    private function generateAdditionalFiles(array $data): void
    {
        if ($this->option('test')) {
            $this->generateTestFile($data);
        }
        
        if ($this->option('routes')) {
            $this->addRoutes($data);
        }
        
        if ($this->option('lang')) {
            $this->generateLangFiles($data);
        }
        
        if ($this->option('service')) {
            $this->generateServiceClass($data);
        }
        
        if ($this->option('dto')) {
            $this->generateDTOs($data);
        }
        
        if ($this->option('policy')) {
            $this->generatePolicy($data);
        }
    }

    private function generateTestFile(array $data): void
    {
        $testPath = base_path("tests/Feature/Controllers/{$data['className']}Test.php");
        
        $testContent = $this->generateTestContent($data);
        
        File::put($testPath, $testContent);
        $this->info("✅ Test file generated at: {$testPath}");
    }

    private function generateTestContent(array $data): string
    {
        // This could be enhanced with AI as well
        $stub = File::get($this->stubPath.'controller-test.stub');
        
        $replacements = [
            '{{ namespace }}' => 'Tests\\Feature\\Controllers',
            '{{ class }}' => "{$data['className']}Test",
            '{{ controller }}' => "{$data['namespace']}\\{$data['className']}",
        ];
        
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );
    }

    private function addRoutes(array $data): void
    {
        $routeFile = $data['is_api'] ? 'api.php' : 'web.php';
        $routePath = base_path("routes/{$routeFile}");
        
        $routeContent = $this->generateRouteContent($data);
        
        File::append($routePath, "\n".$routeContent);
        $this->info("✅ Routes added to: {$routePath}");
    }

    private function generateRouteContent(array $data): string
    {
        $controllerClass = "{$data['namespace']}\\{$data['className']}";
        
        if ($data['is_invokable']) {
            return "Route::post('/".Str::kebab($data['className'])."', {$controllerClass}::class);";
        }
        
        if ($data['is_resource']) {
            return "Route::resource('".Str::plural($data['route_name'])."', {$controllerClass}::class);";
        }
        
        // Custom routes based on methods
        $routes = [];
        foreach ($data['metadata']['methods'] ?? [] as $method) {
            $verb = $this->getHttpVerbForMethod($method);
            $uri = $this->getUriForMethod($method, $data);
            $routes[] = "Route::{$verb}('{$uri}', [{$controllerClass}::class, '{$method}']);";
        }
        
        return implode("\n", $routes);
    }

    private function getHttpVerbForMethod(string $method): string
    {
        return match (true) {
            Str::startsWith($method, 'store') => 'post',
            Str::startsWith($method, 'update') => 'put',
            Str::startsWith($method, 'destroy') => 'delete',
            default => 'get',
        };
    }

    private function getUriForMethod(string $method, array $data): string
    {
        $base = Str::plural($data['route_name']);
        
        return match ($method) {
            'index' => $base,
            'show' => "$base/{id}",
            'edit' => "$base/{id}/edit",
            'update' => "$base/{id}",
            'destroy' => "$base/{id}",
            default => "$base/$method",
        };
    }

    private function generateLangFiles(array $data): void
    {
        $langPath = resource_path("lang/{$this->option('lang')}/{$data['route_name']}.php");
        
        $content = "<?php\n\nreturn [\n    'validation' => [\n        // Validation messages\n    ],\n];";
        
        File::put($langPath, $content);
        $this->info("✅ Language file generated at: {$langPath}");
    }

    private function generateServiceClass(array $data): void
    {
        $servicePath = app_path("Services/{$data['className']}Service.php");
        
        $content = "<?php\n\nnamespace App\\Services;\n\nclass {$data['className']}Service\n{\n    // Service logic here\n}";
        
        File::put($servicePath, $content);
        $this->info("✅ Service class generated at: {$servicePath}");
    }

    private function generateDTOs(array $data): void
    {
        $dtoPath = app_path("DTOs/{$data['className']}DTO.php");
        
        $content = "<?php\n\nnamespace App\\DTOs;\n\nclass {$data['className']}DTO\n{\n    // DTO properties and methods\n}";
        
        File::put($dtoPath, $content);
        $this->info("✅ DTO generated at: {$dtoPath}");
    }

    private function generatePolicy(array $data): void
    {
        Artisan::call('make:policy', [
            'name' => "{$data['className']}Policy",
            '--model' => $data['model']
        ]);
        
        $this->info("✅ Policy generated: {$data['className']}Policy");
    }

    private function finalizeSetup(array $data): void
    {
        $this->newLine(2);
        $this->line("<fg=magenta>🎉 Controller generation completed!</>");
        $this->newLine();
        
        $this->line("<options=bold>Next Steps:</>");
        $this->line("- Review the generated code");
        $this->line("- Customize as needed");
        $this->line("- Run tests to verify functionality");
        
        if ($this->option('routes')) {
            $this->line("- Test your new routes");
        }
        
        $this->newLine();
        $this->line("<fg=cyan>Happy coding! 🚀</>");
    }

    private function displayDryRunResults(array $generatedFiles): void
    {
        $this->line("<fg=yellow>⚠️ Dry Run Results (no files were saved)</>");
        $this->newLine();
        
        $this->line("<options=bold>Controller Code:</>");
        $this->line($generatedFiles['controller']['content']);
        
        $this->newLine();
        $this->line("<options=bold>Metadata:</>");
        $this->table(
            ['Type', 'Details'],
            [
                ['Methods', implode(', ', $generatedFiles['metadata']['methods'])],
                ['Dependencies', implode(', ', $generatedFiles['metadata']['dependencies'])],
            ]
        );
    }

    private function getPromptSuggestions(): array
    {
        return [
            'RESTful API controller with CRUD operations',
            'Controller with authentication and authorization',
            'Single action controller for specific task',
            'Controller with caching and queued jobs',
            'API resource controller with pagination',
            'Controller with validation and error handling',
            'Controller with Swagger/OpenAPI documentation',
        ];
    }

    protected function getNamespace(string $name): string
    {
        $segments = explode('/', $name);
        array_pop($segments);
        
        $namespace = !empty($segments) 
            ? 'App\\Http\\Controllers\\' . implode('\\', $segments)
            : 'App\\Http\\Controllers';
            
        if ($this->option('api')) {
            $namespace .= '\\' . ucfirst($this->option('version'));
        }
        
        return $namespace;
    }

    protected function getClassName(string $name): string
    {
        $className = class_basename($name);
        
        if (!Str::endsWith($className, 'Controller')) {
            $className .= 'Controller';
        }
        
        return $className;
    }

    protected function getPath(string $name): string
    {
        $name = Str::replaceFirst('App/', '', $name);
        $path = app_path(str_replace('\\', '/', $name) . '.php');
        
        if (!Str::endsWith($name, 'Controller')) {
            $path = str_replace('.php', 'Controller.php', $path);
        }
        
        return $path;
    }

    protected function ensureDirectoryExists(string $path): void
    {
        $directory = dirname($path);
        
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }
}