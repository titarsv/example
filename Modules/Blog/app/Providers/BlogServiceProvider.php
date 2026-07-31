<?php

namespace Modules\Blog\Providers;

use App\Support\Modules\GuardedModuleServiceProvider;

class BlogServiceProvider extends GuardedModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Blog';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'blog';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
