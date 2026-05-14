<?php

namespace App\Services;

use App\Models\TimeEntry;

class PayrollReadinessService
{
    /**
     * @param  array<string, mixed>  $period  PayrollPeriodService period array
     * @return array{
     *     pdf_ready: bool,
     *     entry_count: int,
     *     open_count: int,
     *     submitted_count: int,
     *     corrected_count: int,
     *     blocking_deviation_entry_count: int,
     * }
     */
    public static function assessPeriod(array $period, ?int $userId = null): array
    {
        $query = TimeEntry::query()
            ->whereBetween('work_date', [$period['start_date'], $period['end_date']])
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId));

        $entries = TimeEntryDeviationService::appendOverlapDeviations($query->get());

        $openEntries = $entries->where('status', TimeEntry::STATUS_OPEN)->values();
        $submittedEntries = $entries->where('status', TimeEntry::STATUS_SUBMITTED)->values();
        $correctedEntries = $entries->where('status', TimeEntry::STATUS_CORRECTED)->values();

        $blockingDeviations = $entries->filter(function (TimeEntry $entry) {
            return collect($entry->deviations ?? [])
                ->whereIn('severity', ['danger', 'warning'])
                ->isNotEmpty();
        });

        $mustConfirm = $entries->isNotEmpty()
            && (
                $openEntries->isNotEmpty()
                || $submittedEntries->isNotEmpty()
                || $correctedEntries->isNotEmpty()
                || $blockingDeviations->isNotEmpty()
            );

        return [
            'pdf_ready' => ! $mustConfirm,
            'entry_count' => $entries->count(),
            'open_count' => $openEntries->count(),
            'submitted_count' => $submittedEntries->count(),
            'corrected_count' => $correctedEntries->count(),
            'blocking_deviation_entry_count' => $blockingDeviations->count(),
        ];
    }
}
