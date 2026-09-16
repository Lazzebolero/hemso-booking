<?php

namespace App\Support;

final class SessionCookie
{
    public static function shouldBeSecure(mixed $secureFlag, string $appEnv, ?string $appUrl): bool
    {
        if ($secureFlag !== null && $secureFlag !== '') {
            return filter_var($secureFlag, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
        }

        if ($appEnv === 'production') {
            return true;
        }

        return parse_url((string) $appUrl, PHP_URL_SCHEME) === 'https';
    }
}
