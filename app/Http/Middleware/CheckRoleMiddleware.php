<?php

namespace App\Http\Middleware;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Closure;

class CheckRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param $request
     * @param Closure $next
     * @param $role1
     * @param $role2
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|mixed
     */
    public function handle($request, Closure $next, $role1 = null, $role2 = null)
    {
        $user = Sentinel::check();
        $roles = [];
        if(!empty($role1)){
            $roles[] = $role1;
        }
        if(!empty($role2)){
            $roles[] = $role2;
        }
        if(!$user->hasAnyAccess($roles)){
            return Response(view('errors.403'));
        }

        return $next($request);
    }
}
