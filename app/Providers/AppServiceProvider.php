<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationEvent;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->forceLocalUrlFromConfig();

        $this->registerSettingHelper();
        $this->applySystemTimezone();
        Event::listen(Login::class, LogAuthenticationEvent::class);
        Event::listen(Failed::class, LogAuthenticationEvent::class);
        Event::listen(Logout::class, LogAuthenticationEvent::class);
    }

    /**
     * Avoid mixed http/https URL detection on local Laragon (breaks session cookies → 419 on POST).
     */
    protected function forceLocalUrlFromConfig(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        $rootUrl = (string) config('app.url');

        if ($rootUrl === '') {
            return;
        }

        URL::forceRootUrl($rootUrl);

        $scheme = parse_url($rootUrl, PHP_URL_SCHEME);

        if (is_string($scheme) && $scheme !== '') {
            URL::forceScheme($scheme);
        }
    }

    protected function registerSettingHelper(): void
    {
        if (! function_exists('setting')) {
            function setting(string $key, $default = null)
            {
                static $settingsCache = null;

                try {
                    if ($settingsCache === null) {
                        if (! Schema::hasTable('settings')) {
                            $settingsCache = [];
                        } else {
                            $settingsCache = DB::table('settings')
                                ->pluck('value', 'key')
                                ->toArray();
                        }
                    }

                    return $settingsCache[$key] ?? $default;
                } catch (\Throwable $e) {
                    return $default;
                }
            }
        }
    }

    protected function applySystemTimezone(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                $this->setTimezone('Europe/Stockholm');

                return;
            }

            $timezone = setting('timezone', 'Europe/Stockholm');

            if (! is_string($timezone) || trim($timezone) === '') {
                $timezone = 'Europe/Stockholm';
            }

            $this->setTimezone($timezone);
        } catch (\Throwable $e) {
            $this->setTimezone('Europe/Stockholm');
        }
    }

    protected function setTimezone(string $timezone): void
    {
        try {
            if (! in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
                $timezone = 'Europe/Stockholm';
            }

            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        } catch (\Throwable $e) {
            config(['app.timezone' => 'Europe/Stockholm']);
            date_default_timezone_set('Europe/Stockholm');
        }
    }
}
