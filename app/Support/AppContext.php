<?php

namespace App\Support;

use App\Models\App;

/**
 * Holds the "current app" for the request. Resolved by middleware:
 *  - web: the logged-in user's app_code, or (for a super-admin) the app chosen in session.
 *  - api: the {app} route segment.
 */
class AppContext
{
    protected static ?string $code = null;

    public static function set(?string $code): void
    {
        if ($code === '001') {
            $code = 'ngd001';
        }
        self::$code = $code ?: null;
    }

    public static function code(): ?string
    {
        return (self::$code === '001' || empty(self::$code)) ? 'ngd001' : self::$code;
    }

    /** All active apps (for the super-admin switcher). */
    public static function all()
    {
        return App::where('status', 1)->orderBy('code')->get();
    }
}