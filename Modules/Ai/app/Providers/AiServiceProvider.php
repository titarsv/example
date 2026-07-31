<?php

namespace Modules\Ai\Providers;

use App\Support\Modules\GuardedModuleServiceProvider;
use Modules\Ai\Services\AiServiceInterface;
use Modules\Ai\Services\OllamaService;
use Modules\Ai\Services\GeminiService;
use Modules\Ai\Console\Commands\BuildProductEmbeddings;
use Modules\Ai\Console\Commands\TestSemanticSearch;
use Modules\Ai\Console\Commands\BuildBoughtTogetherRecommendations;
use Modules\Ai\Console\Commands\SyncOrderProducts;
use Modules\Ai\Console\Commands\BatchGenerateMetadata;
use Modules\Ai\Console\Commands\GenerateSeoContentCommand;
use Modules\Ai\Console\Commands\BatchGenerateTranslates;

class AiServiceProvider extends GuardedModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Ai';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'ai';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // Bound regardless of module status - AiServiceInterface itself must
        // stay resolvable even when disabled, since a handful of core guard
        // sites (Product::semanticSearch() etc.) may still reference the
        // interface type before their own module_active('ai') check runs.
        $this->app->singleton(AiServiceInterface::class, function () {
            if (config('services.ai_provider') === 'ollama') {
                return new OllamaService();
            }
            return new GeminiService();
        });
    }

    public function boot(): void
    {
        parent::boot();

        if (!\Nwidart\Modules\Facades\Module::isEnabled($this->name)) {
            return;
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildProductEmbeddings::class,
                TestSemanticSearch::class,
                BuildBoughtTogetherRecommendations::class,
                SyncOrderProducts::class,
                BatchGenerateMetadata::class,
                GenerateSeoContentCommand::class,
                BatchGenerateTranslates::class,
            ]);
        }
    }
}
