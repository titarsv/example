<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/admin/media/upload',
        '/admin/upload_attribute_image',
        '/admin/products/getattributevalues',
        '/livesearch',
        '/wishlist/update',
        '/cart/update',
        '/cart/updateAll',
        '/cart/get',
        '/get_models',
        '/get_years',
        '/checkout/cities',
        '/checkout/warehouses',
        '/checkout/delivery',
        '/checkout/confirm',
        '/subscribe',
        '/sendmail',
        '/admin/async-upload',
        '/ajax/mcc-init',
        '/api/mcc_callback/',
        '/apply_coupon',
        '/api/webhooks/btcpay',
        '/admin/metadata/generate',
        '/admin/seo-content/generate',
        '/admin/gemini-translate/generate',
        '/admin/gemini-translate/generate-type',
    ];
}
