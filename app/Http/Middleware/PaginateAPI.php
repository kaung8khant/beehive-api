<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PaginateAPI
{
    /**
     * Removes unused data from paginated requests.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $data = $response->getData(true);

        if (isset($data['links'])) {
            unset($data['links']);
        }

        if (isset($data['meta'], $data['meta']['links'])) {
            unset($data['meta']['links']);
        }

        $response->setData($data);
        return $response;
    }
}
