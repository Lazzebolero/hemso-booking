<?php

namespace App\Http\Controllers;

use App\Models\WorkShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->get('date'))
            : now();

        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $date->copy()->endOfWeek(Carbon::SUNDAY);

        $weekDays = collect(range(0, 6))->map(function ($offset) use ($startOfWeek) {
            return $startOfWeek->copy()->addDays($offset);
        });

        $shifts = WorkShift::query()
            ->where('user_id', auth()->id())
            ->whereBetween('shift_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->orderBy('shift_date')
            ->orderBy('start_time')
            ->get();

        $upcomingShifts = WorkShift::query()
            ->where('user_id', auth()->id())
            ->whereDate('shift_date', '>=', now()->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('shift_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        return view('schedule.index', [
            'selectedDate' => $date,
            'weekDays' => $weekDays,
            'shifts' => $shifts,
            'upcomingShifts' => $upcomingShifts,
        ]);
    }
}