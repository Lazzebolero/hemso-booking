<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\WorkShift;
use App\Support\CountryCatalog;
use App\Support\ShiftCoverage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $restaurantFunctions = WorkShift::restaurantFunctions();
        $restaurantGoals = [];

        foreach (ShiftCoverage::restaurantGoalDefaults() as $key => $default) {
            $restaurantGoals[$key] = (int) setting($key, $default);
        }

        $settings = [
            'default_tour_capacity' => setting('default_tour_capacity', 25),
            'timezone' => setting('timezone', 'Europe/Stockholm'),
            'auto_generate_tour_title' => (bool) setting('auto_generate_tour_title', 1),
            'auto_generate_booking_name' => (bool) setting('auto_generate_booking_name', 1),

            'staffing_goal_guides_weekday' => (int) setting('staffing_goal_guides_weekday', 2),
            'staffing_goal_guides_weekend' => (int) setting('staffing_goal_guides_weekend', 3),
            'staffing_goal_hosts' => (int) setting('staffing_goal_hosts', 1),
            ...$restaurantGoals,

            'country_quick_pick_max' => CountryCatalog::maxQuickPicks(),
            'tour_wait_warning_minutes' => (int) setting('tour_wait_warning_minutes', 45),
        ];

        return view('admin.settings.index', compact('settings', 'restaurantFunctions'));
    }

    public function update(Request $request)
    {
        $restaurantGoalKeys = array_keys(ShiftCoverage::restaurantGoalDefaults());
        $restaurantGoalRules = collect($restaurantGoalKeys)
            ->mapWithKeys(fn (string $key) => [$key => ['nullable', 'integer', 'min:0', 'max:20']])
            ->all();

        $data = $request->validate([
            'default_tour_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'timezone' => ['required', 'string', 'max:100'],
            'auto_generate_tour_title' => ['nullable', 'boolean'],
            'auto_generate_booking_name' => ['nullable', 'boolean'],

            'staffing_goal_guides_weekday' => ['nullable', 'integer', 'min:0', 'max:20'],
            'staffing_goal_guides_weekend' => ['nullable', 'integer', 'min:0', 'max:20'],
            'staffing_goal_hosts' => ['nullable', 'integer', 'min:0', 'max:20'],
            ...$restaurantGoalRules,

            'country_quick_pick_max' => ['nullable', 'integer', 'min:1', 'max:'.CountryCatalog::ABSOLUTE_MAX_QUICK_PICKS],
            'tour_wait_warning_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
        ]);

        $data['auto_generate_tour_title'] = $request->boolean('auto_generate_tour_title') ? '1' : '0';
        $data['auto_generate_booking_name'] = $request->boolean('auto_generate_booking_name') ? '1' : '0';
        $data['default_tour_capacity'] = (string) $data['default_tour_capacity'];

        $integerKeys = [
            'staffing_goal_guides_weekday',
            'staffing_goal_guides_weekend',
            'staffing_goal_hosts',
            ...$restaurantGoalKeys,
        ];

        foreach ($integerKeys as $key) {
            $data[$key] = (string) ((int) ($data[$key] ?? 0));
        }

        if ($request->has('tour_wait_warning_minutes')) {
            $data['tour_wait_warning_minutes'] = (string) max(0, min(180, (int) $request->input('tour_wait_warning_minutes')));
        } else {
            unset($data['tour_wait_warning_minutes']);
        }

        if ($request->has('country_quick_pick_max')) {
            $data['country_quick_pick_max'] = (string) max(
                1,
                min(CountryCatalog::ABSOLUTE_MAX_QUICK_PICKS, (int) $request->input('country_quick_pick_max'))
            );
        } else {
            unset($data['country_quick_pick_max']);
        }

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value]
            );
        }

        return back()->with('success', 'Inställningar uppdaterade.');
    }
}
