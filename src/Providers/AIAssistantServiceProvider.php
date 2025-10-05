<?php

namespace LaravelAIAssistant\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use LaravelAIAssistant\Services\SchemaAnalyzer;
use LaravelAIAssistant\Services\AIMetadataGenerator;
use LaravelAIAssistant\Services\AIToolDiscovery;
use LaravelAIAssistant\Services\DynamicAPIGenerator;

class AIAssistantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/ai-assistant.php', 'ai-assistant');

        $this->app->singleton(SchemaAnalyzer::class);
        $this->app->singleton(AIMetadataGenerator::class);
        $this->app->singleton(AIToolDiscovery::class);
        $this->app->singleton(DynamicAPIGenerator::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutes();
        $this->publishConfig();
        $this->publishMigrations();
        $this->registerCommands();
    }

    /**
     * Load package routes.
     */
    private function loadRoutes(): void
    {
        // Public metadata endpoint (no authentication required)
        Route::prefix(config('ai-assistant.api.prefix', 'api/ai'))
            ->middleware(['throttle:60,1']) // Only rate limiting
            ->group(function () {
                Route::get('/metadata', [\LaravelAIAssistant\Controllers\AIDynamicController::class, 'getMetadata']);
            });

        // Protected API endpoints (authentication required)
        Route::prefix(config('ai-assistant.api.prefix', 'api/ai'))
            ->middleware(config('ai-assistant.api.middleware', ['auth:sanctum', 'ai.security']))
            ->group(function () {
                // Dynamic model operations
                Route::any('/models/{modelName}', [\LaravelAIAssistant\Controllers\AIDynamicController::class, 'handleModelOperation']);
                
                // Authentication endpoints
                Route::post('/auth/token', [\LaravelAIAssistant\Controllers\AIAuthController::class, 'generateToken']);
                Route::post('/auth/validate', [\LaravelAIAssistant\Controllers\AIAuthController::class, 'validateToken']);
                
                // Conversation management
                Route::get('/conversations', [\LaravelAIAssistant\Controllers\AIConversationController::class, 'index']);
                Route::post('/conversations', [\LaravelAIAssistant\Controllers\AIConversationController::class, 'store']);
                Route::get('/conversations/{id}', [\LaravelAIAssistant\Controllers\AIConversationController::class, 'show']);
                Route::delete('/conversations/{id}', [\LaravelAIAssistant\Controllers\AIConversationController::class, 'destroy']);
            });
    }

    /**
     * Publish configuration file.
     */
    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/ai-assistant.php' => config_path('ai-assistant.php'),
        ], 'ai-assistant-config');
    }

    /**
     * Publish migration files.
     */
    private function publishMigrations(): void
    {
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'ai-assistant-migrations');
    }

    /**
     * Register Artisan commands.
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \LaravelAIAssistant\Commands\GenerateAIMetadataCommand::class,
                \LaravelAIAssistant\Commands\ClearAICacheCommand::class,
                \LaravelAIAssistant\Commands\InstallAICommand::class,
            ]);
        }
    }
}
