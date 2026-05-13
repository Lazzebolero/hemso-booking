<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Listeners\LogAuthenticationEvent;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerSettingHelper();
        $this->applySystemTimezone();
		Event::listen(Login::class, LogAuthenticationEvent::class);
		Event::listen(Failed::class, LogAuthenticationEvent::class);
		Event::listen(Logout::class, LogAuthenticationEvent::class);
    }

    protected function registerSettingHelper(): void
    {
        if (!function_exists('setting')) {
            function setting(string $key, $default = null)
            {
                static $settingsCache = null;

                try {
                    if ($settingsCache === null) {
                        if (!Schema::hasTable('settings')) {
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
            if (!Schema::hasTable('settings')) {
                $this->setTimezone('Europe/Stockholm');
                return;
            }

            $timezone = setting('timezone', 'Europe/Stockholm');

            if (!is_string($timezone) || trim($timezone) === '') {
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
            if (!in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
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