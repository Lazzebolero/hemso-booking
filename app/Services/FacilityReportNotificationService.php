<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Collection;

class FacilityReportNotificationService
{
    public const SETTING_KEY = 'facility_report_notification_emails';

    public const MAX_ATTACHMENTS = 5;

    /**
     * @return list<string>
     */
    public function recipientEmails(): array
    {
        return $this->recipientEmailsCollection()->all();
    }

    /**
     * @return Collection<int, string>
     */
    public function recipientEmailsCollection(): Collection
    {
        $adminEmails = User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->whereHas('roles', function ($query): void {
                $query->where('slug', Roles::ADMIN);
            })
            ->pluck('email');

        $configuredEmails = collect($this->parseEmails((string) setting(self::SETTING_KEY, '')));

        return $adminEmails
            ->merge($configuredEmails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn (string $email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();
    }

    /**
     * @return list<string>
     */
    public function parseEmails(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        return collect(preg_split('/[\s,;]+/', $raw) ?: [])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn (string $email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    public function configuredEmailsRaw(): string
    {
        return (string) setting(self::SETTING_KEY, '');
    }

    public function saveConfiguredEmails(string $raw): void
    {
        $emails = $this->parseEmails($raw);

        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => implode("\n", $emails)]
        );
    }
}
