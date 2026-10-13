<?php

namespace App\Http\Middleware;

use App\Support\AppContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current app for the public API from the {app} route segment,
 * e.g. /api/ngd001/v1/getFrameCategories.
 */
class SetAppFromRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        AppContext::set($request->route('app'));

        return $next($request);
    }
}
