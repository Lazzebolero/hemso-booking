<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\GuideShift;
use App\Models\Tour;
use App\Services\LogService;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return view('guide.dashboard', [
            'tours' => Tour::with('bookings')->where('guide_id', $user->id)->whereDate('tour_date', '>=', now()->toDateString())->orderBy('tour_date')->orderBy('start_time')->get(),
            'shifts' => GuideShift::where('guide_id', $user->id)->orderBy('shift_date')->take(10)->get(),
        ]);
    }

    public function startTour(Tour $tour)
    {
        abort_unless($tour->guide_id === auth()->id() || auth()->user()->isAdmin(), 403);
        $tour->update(['status' => 'started', 'started_at' => now()]);
        LogService::log('tour', $tour->id, 'started', null, ['status' => 'started'], 'Guiden startade tur');
        return back()->with('success', 'Tur startad.');
    }

    public function completeTour(Tour $tour)
    {
        abort_unless($tour->guide_id === auth()->id() || auth()->user()->isAdmin(), 403);
        $tour->update(['status' => 'completed', 'ended_at' => now()]);
        LogService::log('tour', $tour->id, 'completed', null, ['status' => 'completed'], 'Guiden avslutade tur');
        return back()->with('success', 'Tur avslutad.');
    }
}
