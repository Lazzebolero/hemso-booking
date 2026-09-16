<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailySystemHealthReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *     generated_at: Carbon,
     *     overall_status: string,
     *     summary: array{ok: int, warning: int, error: int},
     *     checks: list<array{status: string, title: string, message: string, items: array<string, string>}>
     * }  $report
     */
    public function __construct(
        public array $report,
        public string $systemHealthUrl,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Daglig systemstatus')
            ->view('emails.daily-system-health-report')
            ->with([
                'report' => $this->report,
                'systemHealthUrl' => $this->systemHealthUrl,
            ]);
    }
}
