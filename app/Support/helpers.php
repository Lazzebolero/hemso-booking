<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        static $cache = [];

        if (! app()->runningUnitTests() && array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $value = Setting::where('key', $key)->value('value');

        if (! app()->runningUnitTests()) {
            $cache[$key] = $value ?? $default;
        }

        return $value ?? $default;
    }
}
