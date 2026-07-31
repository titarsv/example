<?php

namespace Modules\Wishlist\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class WishlistServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Wishlist';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'wishlist';
}
