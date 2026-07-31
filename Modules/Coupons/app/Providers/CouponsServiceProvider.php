<?php

namespace Modules\Coupons\Providers;

use App\Support\Modules\GuardedModuleServiceProvider;

class CouponsServiceProvider extends GuardedModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Coupons';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'coupons';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
