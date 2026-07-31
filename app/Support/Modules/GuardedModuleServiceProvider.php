<?php

namespace App\Support\Modules;

use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * nwidart/laravel-modules caches the eager-provider list to
 * bootstrap/cache/modules.php (Illuminate\Foundation\ProviderRepository) and
 * only rewrites it when the freshly computed provider list differs from what
 * is cached. Nwidart\Modules\ModuleManifest itself also keeps a `static`
 * snapshot of which modules are enabled for the lifetime of the PHP worker
 * process. Under a persistent worker (PHP-FPM), that means toggling a module
 * off in the admin "Модули" screen does not necessarily stop that module's
 * routes/views from being registered on the very next request - only once
 * the worker recycles or the cache is rebuilt.
 *
 * Every module's own ServiceProvider must therefore re-check the (uncached)
 * activator status itself on every register()/boot() call, which is what
 * actually gates routes/views on a per-request basis regardless of whether
 * the outer provider-list cache is stale. Module service providers should
 * extend this instead of Nwidart\Modules\Support\ModuleServiceProvider
 * directly.
 */
abstract class GuardedModuleServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        if (!Module::isEnabled($this->name)) {
            return;
        }

        parent::register();
    }

    public function boot(): void
    {
        if (!Module::isEnabled($this->name)) {
            return;
        }

        parent::boot();
    }
}
