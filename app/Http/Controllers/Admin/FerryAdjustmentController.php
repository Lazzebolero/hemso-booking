<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Services\FerryScheduleService;
use App\Services\TourFerryAdjustmentService;
use App\Support\ActiveRole;
use App\Support\FerryDirections;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class FerryAdjustmentController extends Controller
{
    public function __construct(
        private TourFerryAdjustmentService $ferryAdjustmentService,
        private FerryScheduleService $ferrySchedule,
    ) {}

    public function index(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->string('date'))->toDateString()
            : now()->toDateString();

        $tours = $this->ferryAdjustmentService->toursForDate($date);

        return view('admin.ferry-adjustments.index', [
            'selectedDate' => $date,
            'tours' => $tours,
            'ferrySnapshot' => $this->ferrySchedule->daySnapshot(Carbon::parse($date), FerryDirections::TO_ISLAND),
        ]);
    }

    public function shift(Request $request, Tour $tour): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'minutes' => ['required', 'integer', 'in:10,-10'],
        ]);

        try {
            $this->ferryAdjustmentService->adjust(
                $tour,
                (int) $data['minutes'],
                auth()->id()
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route($this->routePrefix().'.ferry-adjustments.index', ['date' => $data['date']])
                ->withErrors(['tour' => $exception->getMessage()]);
        }

        $direction = (int) $data['minutes'] > 0 ? 'framåt' : 'bakåt';
        $tour->refresh();

        return redirect()
            ->route($this->routePrefix().'.ferry-adjustments.index', ['date' => $data['date']])
            ->with('success', sprintf(
                'Tiden för "%s" flyttades %s %d min (%s–%s).',
                $tour->title,
                $direction,
                abs((int) $data['minutes']),
                $tour->displayTime($tour->start_time),
                $tour->displayTime($tour->end_time),
            ));
    }

    public function reset(Request $request, Tour $tour): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        try {
            $this->ferryAdjustmentService->reset($tour, auth()->id());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route($this->routePrefix().'.ferry-adjustments.index', ['date' => $data['date']])
                ->withErrors(['tour' => $exception->getMessage()]);
        }

        $tour->refresh();

        return redirect()
            ->route($this->routePrefix().'.ferry-adjustments.index', ['date' => $data['date']])
            ->with('success', sprintf(
                'Ursprunglig tid återställd för "%s" (%s–%s).',
                $tour->title,
                $tour->displayTime($tour->start_time),
                $tour->displayTime($tour->end_time),
            ));
    }

    private function routePrefix(): string
    {
        return ActiveRole::routePrefix();
    }
}
