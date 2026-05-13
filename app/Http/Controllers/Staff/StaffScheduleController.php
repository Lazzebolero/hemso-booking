<?php

namespace App\Http\Controllers\Staff;

use App\Models\WorkShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffScheduleController extends StaffBaseController
{
    public function index(Request $request): View
    {
        $this->authorizeStaffAccess();

        $user = auth()->user();

        $date = $request->filled('date')
            ? Carbon::parse($request->get('date'))
            : now();

        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $date->copy()->endOfWeek(Carbon::SUNDAY);

        $shifts = WorkShift::query()
            ->where('user_id', $user->id)
            ->whereBetween('shift_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->orderBy('shift_date')
            ->orderBy('start_time')
            ->get();

        return view('staff.schedule.index', [
            'selectedDate' => $date,
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
            'shifts' => $shifts,
        ]);
    }
}