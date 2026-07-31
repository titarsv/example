<?php

namespace App\Http\Middleware;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Closure;

class AdminMiddleware
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
        if($user = Sentinel::check()){
            if(Sentinel::inRole('admin') or Sentinel::inRole('manager') or Sentinel::inRole('moderator') or Sentinel::inRole('marketer')){
                return $next($request);
            }elseif(Sentinel::inRole('user')){
                return redirect('/user');
            }else{
                return redirect('/');
            }
        }else{
            return redirect('/login');
        }
    }
}
