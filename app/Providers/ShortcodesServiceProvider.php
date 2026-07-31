<?php

namespace App\Providers;

use App\Shortcodes\BoldShortcode;
use App\Shortcodes\BlockShortcode;
use Illuminate\Support\ServiceProvider;
use Webwizo\Shortcodes\Shortcode;

class ShortcodesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        \Shortcode::register('block', 'App\Shortcodes\BlockShortcode@register');
//        Shortcode::register('inject', 'App\Shortcodes\InjectShortcode@render');
    }
}
