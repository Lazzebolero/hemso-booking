<?php

namespace App\Listeners;

use App\Events\FacilityReportCreated;
use App\Mail\NewFacilityReportMail;
use App\Services\FacilityReportNotificationService;
use Illuminate\Support\Facades\Mail;

/**
 * Skickar e-post om ny felrapport till aktiva admin-användare
 * samt eventuella extra adresser från inställningarna.
 */
class SendNewFacilityReportAdminMail
{
    public function __construct(
        private FacilityReportNotificationService $notifications,
    ) {}

    public function handle(FacilityReportCreated $event): void
    {
        $report = $event->report->loadMissing([
            'reporter',
            'category',
            'priority',
            'location',
            'statusRelation',
            'attachments',
        ]);

        foreach ($this->notifications->recipientEmails() as $email) {
            Mail::to($email)->send(new NewFacilityReportMail($report));
        }
    }
}
