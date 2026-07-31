<?php

namespace App\Http\Middleware;

use Closure;

use Cartalyst\Sentinel\Native\Facades\Sentinel;

class UserMiddleware
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
        if ($user = Sentinel::check()){
            // User is logged in and assigned to the `$user` variable.
            if(Sentinel::inRole('user') or Sentinel::inRole('marketer') or Sentinel::inRole('moderator') or Sentinel::inRole('manager') or Sentinel::inRole('admin')){
                return $next($request);
            }else{
                return redirect('/');
            }
        }else{
            // User is not logged in
            return redirect('/');
        }
    }
}
