<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportCategory;
use App\Models\ReportLocation;
use App\Models\ReportPriority;
use App\Models\ReportStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportSettingsController extends Controller
{
    public function index()
    {
        $categories = ReportCategory::orderBy('sort_order')->orderBy('name')->get();
        $priorities = ReportPriority::orderBy('sort_order')->orderBy('name')->get();
        $statuses = ReportStatus::orderBy('sort_order')->orderBy('name')->get();
        $locations = ReportLocation::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.settings.reports.index', compact(
            'categories',
            'priorities',
            'statuses',
            'locations'
        ));
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('report_categories', 'code')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        ReportCategory::create($data);

        return back()->with('success', 'Kategori tillagd.');
    }

    public function updateCategory(Request $request, ReportCategory $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('report_categories', 'code')->ignore($category->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        return back()->with('success', 'Kategori uppdaterad.');
    }

    public function destroyCategory(ReportCategory $category)
    {
        if ($category->reports()->exists()) {
            return back()->withErrors('Kategorin används i felrapporter och kan inte tas bort.');
        }

        $category->delete();

        return back()->with('success', 'Kategori borttagen.');
    }

    public function storePriority(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('report_priorities', 'code')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        ReportPriority::create($data);

        return back()->with('success', 'Prioritet tillagd.');
    }

    public function updatePriority(Request $request, ReportPriority $priority)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('report_priorities', 'code')->ignore($priority->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        $priority->update($data);

        return back()->with('success', 'Prioritet uppdaterad.');
    }

    public function destroyPriority(ReportPriority $priority)
    {
        if ($priority->reports()->exists()) {
            return back()->withErrors('Prioriteten används i felrapporter och kan inte tas bort.');
        }

        $priority->delete();

        return back()->with('success', 'Prioritet borttagen.');
    }

    public function storeStatus(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('report_statuses', 'code')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        ReportStatus::create($data);

        return back()->with('success', 'Status tillagd.');
    }

    public function updateStatus(Request $request, ReportStatus $status)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('report_statuses', 'code')->ignore($status->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        $status->update($data);

        return back()->with('success', 'Status uppdaterad.');
    }

    public function destroyStatus(ReportStatus $status)
    {
        if ($status->reports()->exists()) {
            return back()->withErrors('Statusen används i felrapporter och kan inte tas bort.');
        }

        $status->delete();

        return back()->with('success', 'Status borttagen.');
    }

    public function storeLocation(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('report_locations', 'code')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        ReportLocation::create($data);

        return back()->with('success', 'Plats tillagd.');
    }

    public function updateLocation(Request $request, ReportLocation $location)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('report_locations', 'code')->ignore($location->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        $location->update($data);

        return back()->with('success', 'Plats uppdaterad.');
    }

    public function destroyLocation(ReportLocation $location)
    {
        if ($location->reports()->exists()) {
            return back()->withErrors('Platsen används i felrapporter och kan inte tas bort.');
        }

        $location->delete();

        return back()->with('success', 'Plats borttagen.');
    }
}