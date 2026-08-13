<?php

namespace Modules\ThemeImport\Providers;

use App\Support\Modules\GuardedModuleServiceProvider;

class ThemeImportServiceProvider extends GuardedModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'ThemeImport';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'themeimport';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}