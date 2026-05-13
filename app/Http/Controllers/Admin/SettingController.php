<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'default_tour_capacity' => setting('default_tour_capacity', 25),
            'timezone' => setting('timezone', 'Europe/Stockholm'),
            'auto_generate_tour_title' => (bool) setting('auto_generate_tour_title', 1),
            'auto_generate_booking_name' => (bool) setting('auto_generate_booking_name', 1),

            'staffing_goal_guides_weekday' => (int) setting('staffing_goal_guides_weekday', 2),
            'staffing_goal_guides_weekend' => (int) setting('staffing_goal_guides_weekend', 3),
            'staffing_goal_hosts' => (int) setting('staffing_goal_hosts', 1),

            'staffing_goal_kock' => (int) setting('staffing_goal_kock', 1),
            'staffing_goal_kallskank' => (int) setting('staffing_goal_kallskank', 0),
            'staffing_goal_kassa' => (int) setting('staffing_goal_kassa', 1),
            'staffing_goal_disk' => (int) setting('staffing_goal_disk', 0),
            'staffing_goal_glassbar' => (int) setting('staffing_goal_glassbar', 0),
            'staffing_goal_servering' => (int) setting('staffing_goal_servering', 1),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'default_tour_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'timezone' => ['required', 'string', 'max:100'],
            'auto_generate_tour_title' => ['nullable', 'boolean'],
            'auto_generate_booking_name' => ['nullable', 'boolean'],

            'staffing_goal_guides_weekday' => ['nullable', 'integer', 'min:0', 'max:20'],
            'staffing_goal_guides_weekend' => ['nullable', 'integer', 'min:0', 'max:20'],
            'staffing_goal_hosts' => ['nullable', 'integer', 'min:0', 'max:20'],

            'staffing_goal_kock' => ['nullable', 'integer', 'min:0', 'max:20'],
            'staffing_goal_kallskank' => ['nullable', 'integer', 'min:0', 'max:20'],
            'staffing_goal_kassa' => ['nullable', 'integer', 'min:0', 'max:20'],
            'staffing_goal_disk' => ['nullable', 'integer', 'min:0', 'max:20'],
            'staffing_goal_glassbar' => ['nullable', 'integer', 'min:0', 'max:20'],
            'staffing_goal_servering' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        $data['auto_generate_tour_title'] = $request->boolean('auto_generate_tour_title') ? '1' : '0';
        $data['auto_generate_booking_name'] = $request->boolean('auto_generate_booking_name') ? '1' : '0';
        $data['default_tour_capacity'] = (string) $data['default_tour_capacity'];

        $integerKeys = [
            'staffing_goal_guides_weekday',
            'staffing_goal_guides_weekend',
            'staffing_goal_hosts',
            'staffing_goal_kock',
            'staffing_goal_kallskank',
            'staffing_goal_kassa',
            'staffing_goal_disk',
            'staffing_goal_glassbar',
            'staffing_goal_servering',
        ];

        foreach ($integerKeys as $key) {
            $data[$key] = (string) ((int) ($data[$key] ?? 0));
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