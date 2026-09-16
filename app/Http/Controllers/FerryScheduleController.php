<?php

namespace App\Http\Controllers;

use App\Services\FerryScheduleService;
use App\Support\FerryDirections;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FerryScheduleController extends Controller
{
    public function __construct(
        private FerryScheduleService $ferrySchedule,
    ) {}

    public function index(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->string('date')->toString())
            : now();

        $activeSnapshot = $this->ferrySchedule->daySnapshot($date, FerryDirections::TO_ISLAND);

        return view('ferry-schedule.index', [
            'selectedDate' => $date,
            'activeSnapshot' => $activeSnapshot,
            'commuteHint' => $this->ferrySchedule->commuteHintForUser(
                auth()->user(),
                $date,
                FerryDirections::TO_ISLAND,
            ),
        ]);
    }
}
