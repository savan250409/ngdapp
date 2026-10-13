<?php

namespace App\Http\Middleware;

use App\Models\App;
use App\Support\AppContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetAppContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $apps = App::where('status', 1)->orderBy('code')->get();

        if ($user && $user->isSuperAdmin()) {
            // Super-admin: current app is the one chosen in session (default: first app).
            $chosen = session('current_app');
            if (! $chosen || ! $apps->contains('code', $chosen)) {
                $chosen = optional($apps->first())->code;
                session(['current_app' => $chosen]);
            }
            $code = $chosen;
        } else {
            // App-scoped admin: always their own app.
            $code = $user?->app_code;
        }

        AppContext::set($code);

        View::share('appCode', $code);
        View::share('currentApps', $apps);
        View::share('isSuperAdmin', $user ? $user->isSuperAdmin() : false);

        return $next($request);
    }
}
