<?php

namespace Modules\ThemeImport\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'ThemeImport';

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace('Modules\ThemeImport\Http\Controllers')
            ->group(module_path($this->name, '/routes/web.php'));
    }
}