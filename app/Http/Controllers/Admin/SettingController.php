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
        ]);

        $data['auto_generate_tour_title'] = $request->boolean('auto_generate_tour_title') ? '1' : '0';
        $data['auto_generate_booking_name'] = $request->boolean('auto_generate_booking_name') ? '1' : '0';
        $data['default_tour_capacity'] = (string) $data['default_tour_capacity'];

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        return back()->with('success', 'Inställningar uppdaterade.');
    }
}
