<?php
namespace App\Http\Middleware;

use Closure;

class EnforceJson
{
    /**
     * Enforce json
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $request->headers->set('Accept', 'application/json; charset=utf-8');
        return $next($request);
    }
}
