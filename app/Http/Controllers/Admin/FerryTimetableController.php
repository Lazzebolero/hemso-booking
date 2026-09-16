<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Trafikverket\FerryTrafficService;
use App\Support\FerryDayTypes;
use App\Support\FerryStrinningenTimetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FerryTimetableController extends Controller
{
    public function __construct(
        private FerryTrafficService $ferryTraffic,
    ) {}

    public function index(Request $request): View
    {
        $dayType = $request->string('day_type')->toString();
        if (! in_array($dayType, FerryDayTypes::all(), true)) {
            $dayType = FerryDayTypes::WEEKDAY;
        }

        $marginMinutes = Setting::query()
            ->where('key', 'ferry_adjustment_margin_minutes')
            ->value('value') ?? '30';

        $cachedDay = $this->ferryTraffic->cachedDay(now());

        return view('admin.ferry-timetable.index', [
            'dayType' => $dayType,
            'departures' => FerryStrinningenTimetable::departuresFor($dayType),
            'timetableRevision' => FerryStrinningenTimetable::revision(),
            'marginMinutes' => $marginMinutes,
            'trafficLive' => $this->ferryTraffic->isEnabled() && is_array($cachedDay),
            'trafficFetchedAt' => is_array($cachedDay) ? ($cachedDay['fetched_at'] ?? null) : null,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ferry_adjustment_margin_minutes' => ['required', 'integer', 'min:1', 'max:180'],
            'day_type' => ['required', 'in:'.implode(',', FerryDayTypes::all())],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'ferry_adjustment_margin_minutes'],
            ['value' => (string) $data['ferry_adjustment_margin_minutes']],
        );

        return redirect()
            ->route('admin.ferry-timetable.index', ['day_type' => $data['day_type']])
            ->with('success', 'Inställningar sparade.');
    }
}
