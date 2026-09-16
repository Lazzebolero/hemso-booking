<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('scheduler:heartbeat')->everyMinute();

Schedule::command('bookings:send-reminders')->hourly();
Schedule::command('security:check-login-alerts')->everyFiveMinutes();
Schedule::command('tours:auto-complete')->everyMinute();
Schedule::command('ferry:sync-traffic')->everyThreeMinutes();
Schedule::command('system-health:send-daily-report')
    ->dailyAt(config('services.system_health.daily_report_time', '07:00'));

Schedule::command('weather:sync-observations --recent')
    ->dailyAt('06:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
