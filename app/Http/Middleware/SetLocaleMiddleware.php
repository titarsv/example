<?php

namespace App\Http\Middleware;

use Closure;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $locale = $request->segment(1);
        if(in_array($locale, config()->get('app.locales'))){
            if($locale == env('APP_LOCALE')){
                return redirect(substr($request->path(), 2), 301);
            }
            app()->setLocale($locale);
        }elseif(!empty($locale)){
            abort(404);
        }
        return $next($request);
    }
}
