<?php

namespace Modules\Compare\Providers;

use App\Support\Modules\GuardedModuleServiceProvider;

class CompareServiceProvider extends GuardedModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Compare';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'compare';
}