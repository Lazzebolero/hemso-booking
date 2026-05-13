<?php

namespace App\Listeners;

use App\Models\LoginEvent;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthenticationEvent
{
    public function handle(object $event): void
    {
        try {
            if ($event instanceof Login) {
                LoginEvent::create([
                    'user_id' => $event->user?->id,
                    'email' => $event->user?->email,
                    'event_type' => 'login',
                    'ip_address' => request()?->ip(),
                    'user_agent' => substr((string) request()?->userAgent(), 0, 1000),
                    'occurred_at' => now(),
                ]);

                return;
            }

            if ($event instanceof Failed) {
                LoginEvent::create([
                    'user_id' => $event->user?->id,
                    'email' => $event->credentials['email'] ?? null,
                    'event_type' => 'failed',
                    'ip_address' => request()?->ip(),
                    'user_agent' => substr((string) request()?->userAgent(), 0, 1000),
                    'occurred_at' => now(),
                ]);

                return;
            }

            if ($event instanceof Logout) {
                LoginEvent::create([
                    'user_id' => $event->user?->id,
                    'email' => $event->user?->email,
                    'event_type' => 'logout',
                    'ip_address' => request()?->ip(),
                    'user_agent' => substr((string) request()?->userAgent(), 0, 1000),
                    'occurred_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}