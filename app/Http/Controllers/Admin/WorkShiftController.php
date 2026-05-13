<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\ActiveRole;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkShiftController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->get('date'))
            : now();

        $shifts = WorkShift::with('user')
            ->forDate($date->toDateString())
            ->orderBy('shift_role')
            ->orderBy('shift_function')
            ->orderBy('start_time')
            ->get();

        $users = User::with('roles')
            ->orderBy('name')
            ->get();

        $workShift = new WorkShift([
            'shift_date' => $date->toDateString(),
            'status' => 'planned',
            'shift_role' => 'guide',
            'shift_function' => null,
            'start_time' => '',
            'end_time' => '',
        ]);

        return view('admin.work-shifts.index', [
            'viewMode' => 'day',
            'selectedDate' => $date,
            'shifts' => $shifts,
            'workShift' => $workShift,
            'users' => $users,
            'shiftRoles' => $this->shiftRoles(),
            'statuses' => $this->statuses(),
            'restaurantFunctions' => WorkShift::restaurantFunctions(),
        ]);
    }
public function person(Request $request): View
{
    $selectedUser = null;

    if ($request->filled('user_id')) {
        $selectedUser = User::with('roles')->find($request->get('user_id'));
    }

    $users = User::with('roles')
        ->orderBy('name')
        ->get();

    $upcomingShifts = collect();

    if ($selectedUser) {
        $upcomingShifts = WorkShift::with('user')
            ->where('user_id', $selectedUser->id)
            ->whereDate('shift_date', '>=', now()->toDateString())
            ->orderBy('shift_date')
            ->orderBy('start_time')
            ->get();
    }

    return view('admin.work-shifts.person', [
        'selectedUser' => $selectedUser,
        'users' => $users,
        'upcomingShifts' => $upcomingShifts,
        'shiftRoles' => $this->shiftRoles(),
        'statuses' => $this->statuses(),
        'restaurantFunctions' => WorkShift::restaurantFunctions(),
    ]);
}
public function staffing(Request $request): View
{
    $date = $request->filled('date')
        ? \Carbon\Carbon::parse($request->get('date'))
        : now();

    $shifts = WorkShift::with('user')
        ->whereDate('shift_date', $date->toDateString())
        ->whereNotIn('status', ['cancelled'])
        ->orderBy('shift_role')
        ->orderBy('shift_function')
        ->orderBy('start_time')
        ->get();

    $restaurantFunctions = WorkShift::restaurantFunctions();

    $groupedShifts = $shifts->groupBy(function ($shift) {
        if ($shift->shift_role === 'restaurant') {
            return 'restaurant:' . ($shift->shift_function ?: 'ovrigt');
        }

        return $shift->shift_role;
    });

    return view('admin.work-shifts.staffing', [
        'selectedDate' => $date,
        'shifts' => $shifts,
        'groupedShifts' => $groupedShifts,
        'restaurantFunctions' => $restaurantFunctions,
        'shiftRoles' => $this->shiftRoles(),
        'statuses' => $this->statuses(),
    ]);
}
public function storePerson(Request $request): RedirectResponse
{
    $data = $this->validated($request);

    $user = User::with('roles')->findOrFail($data['user_id']);

    $this->ensureUserCanWorkAs($user, $data['shift_role']);

    $data['created_by'] = auth()->id();
    $data['updated_by'] = auth()->id();

    WorkShift::create($data);

    $warning = $this->tourConflictMessage($data);

    $redirect = redirect()
        ->route($this->routePrefix() . '.work-shifts.person', [
            'user_id' => $data['user_id'],
        ])
        ->with('success', 'Arbetspass skapades.');

    if ($warning) {
        $redirect->with('warning', $warning);
    }

    return $redirect;
}
    public function create(Request $request): View
    {
        return $this->index($request);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $user = User::with('roles')->findOrFail($data['user_id']);
        $this->ensureUserCanWorkAs($user, $data['shift_role']);

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        WorkShift::create($data);

        $warning = $this->tourConflictMessage($data);

        $redirect = redirect()
            ->route($this->routePrefix() . '.work-shifts.index', [
                'date' => $data['shift_date'],
            ])
            ->with('success', 'Arbetspass skapades.');

        if ($warning) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    public function edit(WorkShift $workShift): View
    {
        $users = User::with('roles')
            ->orderBy('name')
            ->get();

        return view('admin.work-shifts.edit', [
            'workShift' => $workShift,
            'users' => $users,
            'shiftRoles' => $this->shiftRoles(),
            'statuses' => $this->statuses(),
            'restaurantFunctions' => WorkShift::restaurantFunctions(),
        ]);
    }

    public function update(Request $request, WorkShift $workShift): RedirectResponse
    {
        $data = $this->validated($request);

        $user = User::with('roles')->findOrFail($data['user_id']);
        $this->ensureUserCanWorkAs($user, $data['shift_role']);

        $data['updated_by'] = auth()->id();

        $workShift->update($data);

        $warning = $this->tourConflictMessage($data, $workShift->id);

        $redirect = redirect()
            ->route($this->routePrefix() . '.work-shifts.index', [
                'date' => $data['shift_date'],
            ])
            ->with('success', 'Arbetspass uppdaterades.');

        if ($warning) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    public function destroy(WorkShift $workShift): RedirectResponse
    {
        $date = optional($workShift->shift_date)->format('Y-m-d');

        $workShift->delete();

        return redirect()
            ->route($this->routePrefix() . '.work-shifts.index', [
                'date' => $date ?: now()->toDateString(),
            ])
            ->with('success', 'Arbetspass togs bort.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'shift_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'shift_role' => ['required', Rule::in(array_keys($this->shiftRoles()))],
            'shift_function' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'notes' => ['nullable', 'string'],
        ]);

        if (($data['shift_role'] ?? null) === 'restaurant') {
            if (blank($data['shift_function'] ?? null)) {
                throw ValidationException::withMessages([
                    'shift_function' => 'Välj funktion för restaurangpass.',
                ]);
            }

            if (! array_key_exists($data['shift_function'], WorkShift::restaurantFunctions())) {
                throw ValidationException::withMessages([
                    'shift_function' => 'Ogiltig restaurangfunktion.',
                ]);
            }
        } else {
            $data['shift_function'] = null;
        }

        return $data;
    }

    private function ensureUserCanWorkAs(User $user, string $shiftRole): void
    {
        abort_unless($user->hasRole($shiftRole), 422, 'Användaren har inte denna roll.');
    }

    private function shiftRoles(): array
    {
        return [
            'guide' => 'Guide',
            'host' => 'Värd',
            'restaurant' => 'Restaurang',
            'admin' => 'Admin',
        ];
    }

    private function statuses(): array
    {
        return [
            'planned' => 'Planerat',
            'confirmed' => 'Bekräftat',
            'changed' => 'Ändrat',
            'cancelled' => 'Inställt',
        ];
    }

    private function tourConflictMessage(array $data, ?int $ignoreShiftId = null): ?string
    {
        if (($data['shift_role'] ?? null) !== 'guide') {
            return null;
        }

        $userId = $data['user_id'] ?? null;
        $shiftDate = $data['shift_date'] ?? null;
        $startTime = $data['start_time'] ?? null;
        $endTime = $data['end_time'] ?? null;

        if (! $userId || ! $shiftDate || ! $startTime) {
            return null;
        }

        $shiftStart = substr($startTime, 0, 5);
        $shiftEnd = $endTime ? substr($endTime, 0, 5) : '23:59';

        $conflictingTours = Tour::query()
            ->where('guide_id', $userId)
            ->whereDate('tour_date', $shiftDate)
            ->when($ignoreShiftId, fn ($query) => $query)
            ->where(function ($query) use ($shiftStart, $shiftEnd) {
                $query->where('start_time', '<', $shiftEnd)
                    ->whereRaw('COALESCE(end_time, start_time) > ?', [$shiftStart]);
            })
            ->orderBy('start_time')
            ->get();

        if ($conflictingTours->isEmpty()) {
            return null;
        }

        $tourList = $conflictingTours
            ->map(function ($tour) {
                $start = $tour->start_time ? substr($tour->start_time, 0, 5) : '--:--';

                return ($tour->title ?: 'Tur') . ' kl ' . $start;
            })
            ->implode(', ');

        return 'Varning: Guiden är redan tilldelad tur samma tid: ' . $tourList . '.';
    }

    private function routePrefix(): string
    {
        return ActiveRole::routePrefix();
    }
}