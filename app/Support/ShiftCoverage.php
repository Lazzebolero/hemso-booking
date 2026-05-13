<?php

namespace App\Support;

use App\Models\WorkShift;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShiftCoverage
{
    public static function requirements(Carbon $date): array
    {
        $isWeekend = in_array($date->dayOfWeekIso, [6, 7], true);

        $defaults = [
            'staffing_goal_guides_weekday' => 2,
            'staffing_goal_guides_weekend' => 3,
            'staffing_goal_hosts' => 1,

            'staffing_goal_kock' => 1,
            'staffing_goal_kallskank' => 0,
            'staffing_goal_kassa' => 1,
            'staffing_goal_disk' => 0,
            'staffing_goal_glassbar' => 0,
            'staffing_goal_servering' => 1,
        ];

        $stored = DB::table('settings')
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->toArray();

        $settings = array_merge($defaults, $stored);

        $restaurantRequirements = [];

        foreach (WorkShift::restaurantFunctions() as $key => $label) {
            $settingKey = 'staffing_goal_' . $key;
            $minimum = (int) ($settings[$settingKey] ?? 0);

            if ($minimum > 0) {
                $restaurantRequirements[$key] = [
                    'label' => $label,
                    'minimum' => $minimum,
                ];
            }
        }

        return [
            'guide' => [
                'label' => 'Guider',
                'minimum' => (int) ($isWeekend
                    ? $settings['staffing_goal_guides_weekend']
                    : $settings['staffing_goal_guides_weekday']),
            ],
            'host' => [
                'label' => 'Värdar',
                'minimum' => (int) $settings['staffing_goal_hosts'],
            ],
            'restaurant_functions' => $restaurantRequirements,
        ];
    }

    public static function evaluate(Collection $shifts, Carbon $date): array
    {
        $requirements = self::requirements($date);

        $guideCount = $shifts->where('shift_role', 'guide')->count();
        $hostCount = $shifts->where('shift_role', 'host')->count();

        $restaurantShifts = $shifts->where('shift_role', 'restaurant');

        $restaurantCounts = $restaurantShifts
            ->groupBy('shift_function')
            ->map(fn ($items) => $items->count());

        $items = [];

        $items[] = self::buildStatusItem(
            label: $requirements['guide']['label'],
            actual: $guideCount,
            required: $requirements['guide']['minimum']
        );

        $items[] = self::buildStatusItem(
            label: $requirements['host']['label'],
            actual: $hostCount,
            required: $requirements['host']['minimum']
        );

        foreach ($requirements['restaurant_functions'] as $function => $config) {
            $items[] = self::buildStatusItem(
                label: $config['label'],
                actual: (int) ($restaurantCounts[$function] ?? 0),
                required: $config['minimum']
            );
        }

        $overall = collect($items)->contains(fn ($item) => $item['status'] === 'red')
            ? 'red'
            : (collect($items)->contains(fn ($item) => $item['status'] === 'yellow') ? 'yellow' : 'green');

        return [
            'overall' => $overall,
            'items' => $items,
        ];
    }

    private static function buildStatusItem(string $label, int $actual, int $required): array
    {
        if ($required <= 0) {
            return [
                'label' => $label,
                'actual' => $actual,
                'required' => $required,
                'status' => 'green',
                'text' => "{$label}: {$actual}/{$required}",
            ];
        }

        if ($actual >= $required) {
            $status = 'green';
        } elseif ($actual > 0) {
            $status = 'yellow';
        } else {
            $status = 'red';
        }

        return [
            'label' => $label,
            'actual' => $actual,
            'required' => $required,
            'status' => $status,
            'text' => "{$label}: {$actual}/{$required}",
        ];
    }
}