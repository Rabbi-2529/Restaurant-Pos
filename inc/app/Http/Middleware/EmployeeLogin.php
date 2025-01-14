<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class EmployeeLogin
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
        if(Auth::guard('employee')->check()) {
            return $next($request);
        }
        return redirect(route('login'));
    }
}
