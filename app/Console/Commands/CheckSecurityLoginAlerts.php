<?php

namespace App\Console\Commands;

use App\Mail\SecurityLoginAlertMail;
use App\Models\LoginEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class CheckSecurityLoginAlerts extends Command
{
    protected $signature = 'security:check-login-alerts';

    protected $description = 'Checks failed login attempts and sends a security alert email if threshold is exceeded.';

    public function handle(): int
    {
        $email = config('services.security_alerts.email');
        $threshold = (int) config('services.security_alerts.failed_threshold', 8);
        $minutes = (int) config('services.security_alerts.minutes', 15);
        $cooldownMinutes = (int) config('services.security_alerts.cooldown_minutes', 60);

        if (! $email) {
            $this->warn('No SECURITY_ALERT_EMAIL configured.');
            return self::SUCCESS;
        }

        $since = now()->subMinutes($minutes);

        $failedCount = LoginEvent::query()
            ->where('event_type', 'failed')
            ->where('occurred_at', '>=', $since)
            ->count();

        if ($failedCount < $threshold) {
            $this->info("No alert needed. Failed attempts: {$failedCount}.");
            return self::SUCCESS;
        }

        if ($this->isInCooldown($cooldownMinutes)) {
            $this->info('Alert threshold reached, but cooldown is active.');
            return self::SUCCESS;
        }

        $uniqueIps = LoginEvent::query()
            ->where('event_type', 'failed')
            ->where('occurred_at', '>=', $since)
            ->whereNotNull('ip_address')
            ->distinct('ip_address')
            ->count('ip_address');

        $topIps = LoginEvent::query()
            ->select('ip_address', DB::raw('COUNT(*) as attempts'))
            ->where('event_type', 'failed')
            ->where('occurred_at', '>=', $since)
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->orderByDesc('attempts')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'ip_address' => $row->ip_address,
                'attempts' => $row->attempts,
            ])
            ->values()
            ->all();

        $topEmails = LoginEvent::query()
            ->select('email', DB::raw('COUNT(*) as attempts'))
            ->where('event_type', 'failed')
            ->where('occurred_at', '>=', $since)
            ->whereNotNull('email')
            ->groupBy('email')
            ->orderByDesc('attempts')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'email' => $row->email,
                'attempts' => $row->attempts,
            ])
            ->values()
            ->all();

        $alertData = [
            'failed_count' => $failedCount,
            'unique_ips' => $uniqueIps,
            'minutes' => $minutes,
            'threshold' => $threshold,
            'checked_at' => now()->format('Y-m-d H:i:s'),
            'top_ips' => $topIps,
            'top_emails' => $topEmails,
        ];

        Mail::to($email)->send(new SecurityLoginAlertMail($alertData));

        $this->writeCooldown();

        $this->info("Security alert sent to {$email}.");

        return self::SUCCESS;
    }

    private function cooldownPath(): string
    {
        return storage_path('app/security-login-alert-cooldown.json');
    }

    private function isInCooldown(int $cooldownMinutes): bool
    {
        $path = $this->cooldownPath();

        if (! File::exists($path)) {
            return false;
        }

        $data = json_decode(File::get($path), true);

        if (empty($data['sent_at'])) {
            return false;
        }

        return now()->diffInMinutes(\Carbon\Carbon::parse($data['sent_at'])) < $cooldownMinutes;
    }

    private function writeCooldown(): void
    {
        $path = $this->cooldownPath();

        File::ensureDirectoryExists(dirname($path));

        File::put($path, json_encode([
            'sent_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));
    }
}