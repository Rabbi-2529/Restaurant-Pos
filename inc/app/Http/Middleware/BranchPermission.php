<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class BranchPermission
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
        if(in_array('1', explode('-', Auth::user()->permission))) {
            return $next($request);
        }
        return redirect()->back();
    }
}
