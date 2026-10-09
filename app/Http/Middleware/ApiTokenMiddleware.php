<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lightweight Bearer-token guard for the public frame API.
 *
 * Send:  Authorization: Bearer <token>
 * where <token> matches API_TOKEN in the .env file.
 */
class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.api_token');
        $provided = $request->bearerToken();

        if (empty($expected) || ! hash_equals($expected, (string) $provided)) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized',
                'data'    => [],
            ], 401);
        }

        return $next($request);
    }
}
