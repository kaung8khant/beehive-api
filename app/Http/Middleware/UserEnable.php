<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserEnable
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
        if (!Auth::guard('users')->user()->is_enable) {
            Auth::guard('users')->logout();
            return response()->json(['message' => 'Your accout is disabled. Contact admin for more information.'], 403);
        }

        return $next($request);
    }
}
