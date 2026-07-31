<?php

namespace Modules\Wishlist\Providers;

use App\Support\Modules\GuardedModuleServiceProvider;

class WishlistServiceProvider extends GuardedModuleServiceProvider
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
