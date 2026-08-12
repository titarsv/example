<?php

namespace Modules\PageImport\Providers;

use App\Support\Modules\GuardedModuleServiceProvider;

class PageImportServiceProvider extends GuardedModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'PageImport';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'pageimport';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}