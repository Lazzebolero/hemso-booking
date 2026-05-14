<?php

namespace App\Listeners;

use App\Events\FacilityReportCreated;
use App\Mail\NewFacilityReportMail;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Mail;

/**
 * Skickar e-post om ny felrapport endast till aktiva användare med admin-roll.
 * Värdar undantas medvetet — ansvaret för uppföljning ligger hos admin.
 */
class SendNewFacilityReportAdminMail
{
    public function handle(FacilityReportCreated $event): void
    {
        $report = $event->report->loadMissing(['reporter', 'category', 'priority']);

        User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->whereHas('roles', function ($query): void {
                $query->where('slug', Roles::ADMIN);
            })
            ->each(function (User $admin) use ($report): void {
                Mail::to($admin->email)->send(new NewFacilityReportMail($report));
            });
    }
}
