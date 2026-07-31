<?php

namespace Modules\Blog\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Blog';

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    /**
     * Same namespace() pattern as App\Providers\RouteServiceProvider for the
     * core routes/web.php - lets this module's routes keep using plain
     * 'BlogController@action' / 'ContentCategoriesController@action' strings
     * instead of ::class array syntax, matching the app's existing style.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace('Modules\Blog\Http\Controllers')
            ->group(module_path($this->name, '/routes/web.php'));
    }
}
