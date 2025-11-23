<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Commands\ClearEmbeddingsCommand;
use App\Commands\HealthCheckCommand;
use App\Commands\IngestDocumentsCommand;
use App\Commands\ListModelsCommand;
use App\Commands\PullModelCommand;
use App\Commands\RemoveModelCommand;
use App\Services\CoquiService;
use App\Services\OllamaService;
use App\Services\QdrantService;
use App\Services\WhisperService;

class LocalAIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/local-ai.php',
            'local-ai'
        );

        // Register singleton services
        $this->app->singleton(OllamaService::class, function ($app) {
            return new OllamaService(
                config('local-ai.ollama.host'),
                config('local-ai.ollama')
            );
        });

        $this->app->singleton(QdrantService::class, function ($app) {
            return new QdrantService(
                config('local-ai.qdrant.host'),
                config('local-ai.qdrant')
            );
        });

        $this->app->singleton(WhisperService::class, function ($app) {
            return new WhisperService(
                config('local-ai.whisper.host'),
                config('local-ai.whisper')
            );
        });

        $this->app->singleton(CoquiService::class, function ($app) {
            return new CoquiService(
                config('local-ai.coqui.host'),
                config('local-ai.coqui')
            );
        });

        // Register facade accessor
        $this->app->singleton('local-ai', function ($app) {
            return new LocalAI(
                $app->make(OllamaService::class),
                $app->make(QdrantService::class),
                $app->make(WhisperService::class),
                $app->make(CoquiService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/local-ai.php' => config_path('local-ai.php'),
        ], 'local-ai-config');

        // Publish docker files
        $this->publishes([
            __DIR__.'/../docker' => base_path('docker/local-ai'),
        ], 'local-ai-docker');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'local-ai-migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
//                PullModelCommand::class,
//                ListModelsCommand::class,
//                RemoveModelCommand::class,
//                IngestDocumentsCommand::class,
//                HealthCheckCommand::class,
//                ClearEmbeddingsCommand::class,
            ]);
        }

        // Load routes if needed
        // $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'local-ai',
            OllamaService::class,
            QdrantService::class,
            WhisperService::class,
            CoquiService::class,
        ];
    }
}
