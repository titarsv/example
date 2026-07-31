<?php

namespace Modules\Reviews\Providers;

use App\Support\Modules\GuardedModuleServiceProvider;

class ReviewsServiceProvider extends GuardedModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Reviews';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'reviews';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
