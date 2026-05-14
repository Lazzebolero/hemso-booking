<?php

namespace App\Mail;

use App\Models\FacilityReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewFacilityReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FacilityReport $report) {}

    public function build(): self
    {
        return $this
            ->subject('Ny felrapport: '.$this->report->title)
            ->view('emails.new-facility-report')
            ->with([
                'report' => $this->report,
                'showUrl' => route('admin.reports.show', $this->report, absolute: true),
            ]);
    }
}
