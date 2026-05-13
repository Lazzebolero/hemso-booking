<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkShift;
use App\Models\WorkShiftTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Carbon\Carbon;

class WorkShiftTemplateController extends Controller
{
    public function index()
{
    $templates = \App\Models\WorkShiftTemplate::with('user')
        ->orderBy('weekday')
        ->orderBy('start_time')
        ->get();

    $users = \App\Models\User::orderBy('name')->get();

    // Roller (anpassade efter ditt system)
    $shiftRoles = [
        'guide' => 'Guide',
        'host' => 'Värd',
        'restaurant' => 'Restaurang',
        'admin' => 'Admin',
    ];

    // Status
    $statuses = [
        'planned' => 'Planerad',
        'confirmed' => 'Bekräftad',
        'cancelled' => 'Inställd',
    ];

    // Restaurangfunktioner (det du lade till tidigare)
    $restaurantFunctions = [
        'kock' => 'Kock',
        'kallskank' => 'Kallskänk',
        'kassa' => 'Kassa',
        'disk' => 'Disk',
        'glassbar' => 'Glassbar',
        'servering' => 'Servering',
    ];

    return view('admin.work-shift-templates.index', compact(
        'templates',
        'users',
        'shiftRoles',
        'statuses',
        'restaurantFunctions'
    ));
}

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $user = User::with('roles')->findOrFail($data['user_id']);
        $this->ensureUserCanWorkAs($user, $data['shift_role']);

        WorkShiftTemplate::create([
            ...$data,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Schemamall skapades.');
    }

    public function update(Request $request, WorkShiftTemplate $workShiftTemplate): RedirectResponse
    {
        $data = $this->validated($request);

        $user = User::with('roles')->findOrFail($data['user_id']);
        $this->ensureUserCanWorkAs($user, $data['shift_role']);

        $workShiftTemplate->update([
            ...$data,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Schemamall uppdaterades.');
    }

    public function destroy(WorkShiftTemplate $workShiftTemplate): RedirectResponse
    {
        $workShiftTemplate->delete();

        return back()->with('success', 'Schemamall togs bort.');
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
        ]);

        $weekStart = Carbon::parse($data['start_date'])->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $templates = WorkShiftTemplate::query()
            ->where('is_active', true)
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($templates as $template) {
            $shiftDate = $weekStart->copy()->addDays($template->weekday - 1);

            if ($shiftDate->lt($weekStart) || $shiftDate->gt($weekEnd)) {
                continue;
            }

            $exists = WorkShift::query()
                ->where('user_id', $template->user_id)
                ->whereDate('shift_date', $shiftDate->toDateString())
                ->where('shift_role', $template->shift_role)
                ->where('start_time', $template->start_time)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            WorkShift::create([
                'user_id' => $template->user_id,
                'shift_date' => $shiftDate->toDateString(),
                'start_time' => $template->start_time,
                'end_time' => $template->end_time,
                'shift_role' => $template->shift_role,
                'shift_function' => $template->shift_function,
                'status' => $template->status ?: 'planned',
                'notes' => $template->notes,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $created++;
        }

        return back()->with('success', "Schema genererat från mallar. {$created} skapades, {$skipped} hoppades över.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'weekday' => ['required', 'integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'shift_role' => ['required', Rule::in(array_keys($this->shiftRoles()))],
            'shift_function' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
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

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

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
}