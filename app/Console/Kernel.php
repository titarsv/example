<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\Sales::class,
        Commands\XMLSitemap::class,
        Commands\Exports::class,
        Commands\CartCleaner::class,
        Commands\TrustpilotReviewsUpdater::class,
        Commands\MyCryptoCheckout::class,
        // GenerateSeoContentCommand, BuildBoughtTogetherRecommendations,
        // BuildProductEmbeddings, TestSemanticSearch, SyncOrderProducts moved
        // to Modules\Ai and are now registered by AiServiceProvider::boot()
        // (only when the Ai module is enabled).
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('xmlsitemap')->daily();
        $schedule->command('generate_exports')->everyMinute();
        $schedule->command('clear_carts')->daily();
        $schedule->command('sales')->everyTenMinutes();
        $schedule->command('mycryptocheckout')->hourly();
        $schedule->command('recommendations:bought-together')->dailyAt('03:00')->when(fn() => module_active('ai'));
        $schedule->command('embeddings:build-products --only-missing')->weekly()->when(fn() => module_active('ai'));
//        $schedule->command('update_trustpilot_reviews')->daily();
//        $schedule->command('seo:generate-content --limit=50')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
