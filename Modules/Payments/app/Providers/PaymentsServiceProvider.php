<?php

namespace Modules\Payments\Providers;

use App\Support\Modules\GuardedModuleServiceProvider;

class PaymentsServiceProvider extends GuardedModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Payments';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'payments';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}