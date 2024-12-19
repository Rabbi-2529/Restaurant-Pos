<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class MenuPermission
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
        if(in_array('3', explode('-', Auth::user()->permission))) {
            return $next($request);
        }
        return redirect()->back();
    }
}
