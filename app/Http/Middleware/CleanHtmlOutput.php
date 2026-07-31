<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CleanHtmlOutput
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if the response is actual HTML
        if (method_exists($response, 'getContent') && str_contains($response->headers->get('Content-Type'), 'text/html')) {
            $content = $response->getContent();

            // This regex removes autocomplete="off" ONLY from hidden input tags
            // This fixes the W3C error: "input element with a type attribute whose value is hidden must not have an autocomplete attribute"
            $content = preg_replace(
                '/(<input[^>]*type=["\']hidden["\'][^>]*)\sautocomplete=["\'](?:on|off)["\']([^>]*>)/i',
                '$1$2',
                $content
            );

            $response->setContent($content);
        }

        return $response;
    }
}
