<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index()
    {
        $languages = Language::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.settings.languages', compact('languages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:languages,code'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $data['code'] = strtolower(trim($data['code']));
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            Language::query()->update(['is_default' => false]);
        }

        Language::create($data);

        return back()->with('success', 'Språk tillagt.');
    }

    public function update(Request $request, Language $language)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:languages,code,' . $language->id],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $data['code'] = strtolower(trim($data['code']));
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            Language::query()->update(['is_default' => false]);
        }

        if (!$data['is_default'] && $language->is_default) {
            $hasAnotherDefault = Language::where('id', '!=', $language->id)->where('is_default', true)->exists();
            if (!$hasAnotherDefault) {
                $data['is_default'] = true;
            }
        }

        $language->update($data);

        return back()->with('success', 'Språk uppdaterat.');
    }

    public function destroy(Language $language)
    {
        $wasDefault = $language->is_default;
        $language->delete();

        if ($wasDefault) {
            $newDefault = Language::orderBy('sort_order')->orderBy('name')->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return back()->with('success', 'Språk borttaget.');
    }
}