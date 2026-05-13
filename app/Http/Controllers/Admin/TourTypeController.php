<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TourType;
use Illuminate\Http\Request;

class TourTypeController extends Controller
{
    public function index()
    {
        $tourTypes = TourType::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.settings.tour-types', compact('tourTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            TourType::query()->update(['is_default' => false]);
        }

        TourType::create($data);

        return back()->with('success', 'Turtyp tillagd.');
    }

    public function update(Request $request, TourType $tourType)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            TourType::query()->update(['is_default' => false]);
        }

        if (!$data['is_default'] && $tourType->is_default) {
            $hasAnotherDefault = TourType::where('id', '!=', $tourType->id)->where('is_default', true)->exists();
            if (!$hasAnotherDefault) {
                $data['is_default'] = true;
            }
        }

        $tourType->update($data);

        return back()->with('success', 'Turtyp uppdaterad.');
    }

    public function destroy(TourType $tourType)
    {
        $wasDefault = $tourType->is_default;
        $tourType->delete();

        if ($wasDefault) {
            $newDefault = TourType::orderBy('sort_order')->orderBy('name')->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return back()->with('success', 'Turtyp borttagen.');
    }
}
