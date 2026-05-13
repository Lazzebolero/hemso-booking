<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        static $cache = [];

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $value = Setting::where('key', $key)->value('value');
        $cache[$key] = $value ?? $default;

        return $cache[$key];
    }
}
