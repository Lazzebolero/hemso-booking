<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyGuideOrder;
use App\Services\DailyGuideOrderService;
use App\Support\ActiveRole;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class DailyGuideOrderController extends Controller
{
    public function __construct(
        private DailyGuideOrderService $dailyGuideOrderService
    ) {}

    public function index(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->string('date'))->toDateString()
            : now()->toDateString();

        $wasInitialized = $this->dailyGuideOrderService->ensureInitialized(
            $date,
            auth()->id()
        );

        $orders = $this->dailyGuideOrderService->orderedForDate($date);
        $shiftStarts = $this->dailyGuideOrderService->shiftStartsForDate($date);
        $availableGuides = $this->dailyGuideOrderService->availableGuidesToAdd($date);

        return view('admin.daily-guide-orders.index', [
            'selectedDate' => $date,
            'orders' => $orders,
            'shiftStarts' => $shiftStarts,
            'availableGuides' => $availableGuides,
            'wasInitialized' => $wasInitialized,
        ]);
    }

    public function syncFromSchedule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $count = $this->dailyGuideOrderService->syncFromSchedule(
            Carbon::parse($data['date'])->toDateString(),
            auth()->id()
        );

        return redirect()
            ->route($this->routePrefix().'.daily-guide-orders.index', ['date' => $data['date']])
            ->with('success', "Guideordning uppdaterad från schema. {$count} guider i listan.");
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $date = Carbon::parse($data['date'])->toDateString();

        try {
            $this->dailyGuideOrderService->addManualGuide(
                $date,
                (int) $data['user_id'],
                auth()->id()
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['user_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route($this->routePrefix().'.daily-guide-orders.index', ['date' => $date])
            ->with('success', 'Guide tillagd i dagens lista.');
    }

    public function destroy(Request $request, DailyGuideOrder $dailyGuideOrder): RedirectResponse
    {
        $date = $dailyGuideOrder->guide_date->toDateString();

        $this->dailyGuideOrderService->removeGuide($date, (int) $dailyGuideOrder->user_id);

        return redirect()
            ->route($this->routePrefix().'.daily-guide-orders.index', ['date' => $date])
            ->with('success', 'Guide borttagen från dagens lista.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $date = Carbon::parse($data['date'])->toDateString();

        try {
            $this->dailyGuideOrderService->reorder(
                $date,
                array_map('intval', $data['user_ids']),
                auth()->id()
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['user_ids' => $exception->getMessage()]);
        }

        return redirect()
            ->route($this->routePrefix().'.daily-guide-orders.index', ['date' => $date])
            ->with('success', 'Guideordning sparad.');
    }

    public function move(Request $request, DailyGuideOrder $dailyGuideOrder): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ]);

        $date = $dailyGuideOrder->guide_date->toDateString();

        try {
            $this->dailyGuideOrderService->moveGuide(
                $date,
                (int) $dailyGuideOrder->user_id,
                $data['direction'],
                auth()->id()
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['order' => $exception->getMessage()]);
        }

        return redirect()
            ->route($this->routePrefix().'.daily-guide-orders.index', ['date' => $date])
            ->with('success', 'Guide flyttad.');
    }

    private function routePrefix(): string
    {
        return ActiveRole::routePrefix();
    }
}
