<?php

namespace App\Services;

use App\Models\TimeEntry;
use Illuminate\Support\Collection;

class TimeEntryDeviationService
{
    public static function forEntry(TimeEntry $entry): array
    {
        $deviations = [];

        if ($entry->status === TimeEntry::STATUS_OPEN) {
            $deviations[] = self::item('open_shift', 'Öppet pass', 'warning', 'Passet är fortfarande öppet.');
        }

        if ($entry->start_at && ! $entry->end_at) {
            $deviations[] = self::item('missing_end', 'Saknar sluttid', 'danger', 'Rapporterad sluttid saknas.');
        }

        if ($entry->clock_in_at_original && ! $entry->clock_out_at_original && $entry->status !== TimeEntry::STATUS_OPEN) {
            $deviations[] = self::item('missing_clock_out', 'Saknar original ut', 'warning', 'Original utstämpling saknas.');
        }

        if ($entry->status === TimeEntry::STATUS_OPEN && $entry->clock_in_at_original) {
            if ($entry->clock_in_at_original->diffInMinutes(now()) > 720) {
                $deviations[] = self::item('open_more_than_12h', 'Öppet > 12h', 'danger', 'Passet har varit öppet längre än 12 timmar.');
            }
        }

        if ($entry->worked_minutes > 960) {
            $deviations[] = self::item('shift_more_than_16h', 'Pass > 16h', 'danger', 'Arbetspasset är längre än 16 timmar.');
        } elseif ($entry->worked_minutes > 720) {
            $deviations[] = self::item('shift_more_than_12h', 'Pass > 12h', 'warning', 'Arbetspasset är längre än 12 timmar.');
        }

        if ($entry->worked_minutes > 0 && $entry->worked_minutes < 15) {
            $deviations[] = self::item('very_short_shift', 'Mycket kort pass', 'info', 'Arbetspasset är kortare än 15 minuter.');
        }

        if ($entry->status === TimeEntry::STATUS_CORRECTED) {
            $deviations[] = self::item('admin_corrected', 'Adminkorrigerat', 'info', 'Passet har korrigerats av admin.');
        }

        return self::unique($deviations);
    }

    public static function appendOverlapDeviations(Collection $entries): Collection
    {
        $overlappingIds = collect();

        $groups = $entries->groupBy(function (TimeEntry $entry) {
            return $entry->user_id . ':' . optional($entry->work_date)->format('Y-m-d');
        });

        foreach ($groups as $group) {
            $sorted = $group
                ->filter(fn (TimeEntry $entry) => $entry->start_at && $entry->end_at)
                ->sortBy('start_at')
                ->values();

            for ($i = 0; $i < $sorted->count(); $i++) {
                for ($j = $i + 1; $j < $sorted->count(); $j++) {
                    $a = $sorted[$i];
                    $b = $sorted[$j];

                    if ($b->start_at->lt($a->end_at)) {
                        $overlappingIds->push($a->id);
                        $overlappingIds->push($b->id);
                    }
                }
            }
        }

        return $entries->map(function (TimeEntry $entry) use ($overlappingIds) {
            $deviations = self::forEntry($entry);

            if ($overlappingIds->contains($entry->id)) {
                $deviations[] = self::item('overlapping_shift', 'Överlappande pass', 'danger', 'Passet överlappar med ett annat pass samma dag.');
            }

            $entry->setAttribute('deviations', self::unique($deviations));

            return $entry;
        });
    }

    private static function item(string $code, string $label, string $severity, string $description): array
    {
        return compact('code', 'label', 'severity', 'description');
    }

    private static function unique(array $deviations): array
    {
        return collect($deviations)->unique('code')->values()->all();
    }
}
