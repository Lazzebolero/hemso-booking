<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantFunction;
use App\Models\ShiftRoleDefault;
use App\Models\WorkShift;
use App\Support\Roles;
use App\Support\ShiftDefaultTimes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RestaurantFunctionController extends Controller
{
    public function index(): View
    {
        $functions = RestaurantFunction::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $roleDefaults = ShiftRoleDefault::keyedByRole();
        $scheduleRoles = [];

        foreach (ShiftRoleDefault::editableRoleSlugs() as $slug) {
            $row = $roleDefaults[$slug] ?? null;
            $scheduleRoles[$slug] = [
                'label' => Roles::labels()[$slug] ?? $slug,
                'default_start_time' => ShiftDefaultTimes::normalize($row?->default_start_time) ?? '10:00',
                'default_end_time' => ShiftDefaultTimes::normalize($row?->default_end_time),
            ];
        }

        return view('admin.settings.restaurant-functions', compact('functions', 'scheduleRoles'));
    }

    public function updateRoleDefaults(Request $request): RedirectResponse
    {
        $rules = [];

        foreach (ShiftRoleDefault::editableRoleSlugs() as $slug) {
            $rules['roles.'.$slug.'.default_start_time'] = ['required', 'date_format:H:i'];
            $rules['roles.'.$slug.'.default_end_time'] = ['nullable', 'date_format:H:i'];
        }

        $data = $request->validate($rules);

        foreach (ShiftRoleDefault::editableRoleSlugs() as $slug) {
            $times = $data['roles'][$slug] ?? [];

            ShiftRoleDefault::query()->updateOrCreate(
                ['role_slug' => $slug],
                [
                    'default_start_time' => ShiftDefaultTimes::normalize($times['default_start_time'] ?? null) ?? '10:00',
                    'default_end_time' => ShiftDefaultTimes::normalize($times['default_end_time'] ?? null),
                ],
            );
        }

        return back()->with('success', 'Standardtider för roller uppdaterade.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        RestaurantFunction::query()->create($data);

        return back()->with('success', 'Restaurangfunktion tillagd.');
    }

    public function update(Request $request, RestaurantFunction $restaurantFunction): RedirectResponse
    {
        $data = $this->validated($request, $restaurantFunction);

        $restaurantFunction->update($data);

        return back()->with('success', 'Restaurangfunktion uppdaterad.');
    }

    public function destroy(RestaurantFunction $restaurantFunction): RedirectResponse
    {
        if ($this->isInUse($restaurantFunction->slug)) {
            return back()
                ->withErrors(['restaurant_function' => 'Funktionen används i schema eller personaldokument och kan inte tas bort. Inaktivera den i stället.']);
        }

        $restaurantFunction->delete();

        return back()->with('success', 'Restaurangfunktion borttagen.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?RestaurantFunction $restaurantFunction = null): array
    {
        $slugRule = Rule::unique('restaurant_functions', 'slug');

        if ($restaurantFunction !== null) {
            $slugRule = $slugRule->ignore($restaurantFunction->id);
        }

        $data = $request->validate([
            'slug' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', $slugRule],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'default_start_time' => ['nullable', 'date_format:H:i'],
            'default_end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $data['slug'] = strtolower(trim($data['slug']));
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        $data['default_start_time'] = ShiftDefaultTimes::normalize($data['default_start_time'] ?? null) ?? '10:00';
        $data['default_end_time'] = ShiftDefaultTimes::normalize($data['default_end_time'] ?? null);

        return $data;
    }

    private function isInUse(string $slug): bool
    {
        if (WorkShift::query()->where('shift_function', $slug)->exists()) {
            return true;
        }

        if (Schema::hasTable('staff_documents')
            && DB::table('staff_documents')->where('shift_function', $slug)->exists()) {
            return true;
        }

        return false;
    }
}
