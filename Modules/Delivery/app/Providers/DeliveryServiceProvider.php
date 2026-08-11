<?php

namespace Modules\Delivery\Providers;

use App\Support\Modules\GuardedModuleServiceProvider;

class DeliveryServiceProvider extends GuardedModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Delivery';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'delivery';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}