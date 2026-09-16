<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantFunction;
use App\Models\WorkShift;
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

        return view('admin.settings.restaurant-functions', compact('functions'));
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
        ]);

        $data['slug'] = strtolower(trim($data['slug']));
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

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
