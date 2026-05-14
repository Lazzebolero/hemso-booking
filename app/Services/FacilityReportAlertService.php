<?php

namespace App\Services;

use App\Models\FacilityReport;
use App\Models\ReportStatus;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class FacilityReportAlertService
{
    /**
     * Antal öppna felrapporter skapade efter att användaren senast öppnade rapportlistan.
     */
    public static function countNewOpenSinceAcknowledgmentForUser(?User $user): int
    {
        if ($user === null) {
            return 0;
        }

        if (! Schema::hasTable('facility_reports')) {
            return 0;
        }

        $openStatusId = ReportStatus::query()->where('code', 'open')->value('id');

        if (! $openStatusId) {
            return 0;
        }

        $since = $user->facility_reports_acknowledged_at;

        return (int) FacilityReport::query()
            ->where('status_id', $openStatusId)
            ->when($since, fn ($query) => $query->where('created_at', '>', $since))
            ->count();
    }
}
