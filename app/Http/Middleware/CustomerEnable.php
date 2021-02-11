<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerEnable
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('customers')->user()->is_enable) {
            Auth::guard('customers')->logout();
            return response()->json(['message' => 'Your accout is disabled. Contact support for more information.'], 403);
        }

        return $next($request);
    }
}
