<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SystemHealthDailyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, array{key: string, status: string, title: string, message: string, items: array<string, string>, groups: list<array{title: string, items: array<string, string>}>}>  $checks
     */
    public function __construct(
        public string $overallStatus,
        public array $checks,
        public string $checkedAt,
        public string $dashboardUrl,
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine())
            ->view('emails.system-health-daily-report')
            ->with([
                'appName' => config('app.name'),
                'environment' => app()->environment(),
                'overallStatusLabel' => $this->statusLabel($this->overallStatus),
                'summary' => $this->summary(),
            ]);
    }

    private function subjectLine(): string
    {
        return sprintf(
            '%s: daglig systemstatus - %s',
            config('app.name'),
            $this->statusLabel($this->overallStatus),
        );
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'ok' => 'OK',
            'warning' => 'Varning',
            'error' => 'Fel',
            default => 'Okänd',
        };
    }

    /**
     * @return array{ok: int, warning: int, error: int}
     */
    private function summary(): array
    {
        $summary = ['ok' => 0, 'warning' => 0, 'error' => 0];

        foreach ($this->checks as $check) {
            $status = $check['status'] ?? 'ok';

            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        return $summary;
    }
}
