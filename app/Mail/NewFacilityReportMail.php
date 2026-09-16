<?php

namespace App\Mail;

use App\Models\FacilityReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewFacilityReportMail extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public FacilityReport $report) {}

    public function build(): self
    {
        $mail = $this
            ->subject('Ny felrapport: '.$this->report->title)
            ->view('emails.new-facility-report')
            ->with([
                'report' => $this->report,
                'showUrl' => route('admin.reports.show', $this->report, absolute: true),
                'attachmentCount' => $this->report->resolvedAttachments()->count(),
            ]);

        foreach ($this->report->resolvedAttachments() as $index => $attachment) {
            if (! $attachment->existsOnDisk()) {
                continue;
            }

            $path = $attachment->path;
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg');
            $asName = sprintf(
                'felrapport-%d-%d.%s',
                $this->report->id,
                $index + 1,
                $extension
            );

            $mail->attachFromStorageDisk('public', $path, $asName, [
                'mime' => Storage::disk('public')->mimeType($path) ?: $this->guessMime($extension),
            ]);
        }

        return $mail;
    }

    private function guessMime(string $extension): string
    {
        return match (Str::lower($extension)) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'heic', 'heif' => 'image/heic',
            default => 'image/jpeg',
        };
    }
}
