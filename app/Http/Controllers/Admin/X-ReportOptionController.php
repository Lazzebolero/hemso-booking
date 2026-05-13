<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportOption;
use Illuminate\Http\Request;

class ReportOptionController extends Controller
{
    public function index()
    {
        $categories = ReportOption::where('type', 'category')->orderBy('sort_order')->orderBy('name')->get();
        $priorities = ReportOption::where('type', 'priority')->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.settings.report-options', compact('categories', 'priorities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:category,priority'],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        ReportOption::create($data);

        return back()->with('success', 'Alternativ tillagt.');
    }

    public function update(Request $request, ReportOption $reportOption)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        $reportOption->update($data);

        return back()->with('success', 'Alternativ uppdaterat.');
    }

    public function destroy(ReportOption $reportOption)
    {
        $reportOption->delete();

        return back()->with('success', 'Alternativ borttaget.');
    }
}
