<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuideShift;
use App\Models\Tour;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\Request;

class GuideShiftController extends Controller
{
    public function index()
    {
        $shifts = GuideShift::with('guide', 'tour')->orderBy('shift_date')->paginate(20);
        return view('admin.shifts.index', compact('shifts'));
    }

    public function create()
    {
        return view('admin.shifts.form', [
            'shift' => new GuideShift(),
            'guides' => User::where('role', 'guide')->orderBy('name')->get(),
            'tours' => Tour::orderBy('tour_date')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $shift = GuideShift::create($data);
        LogService::log('guide_shift', $shift->id, 'created', null, $shift->toArray(), 'Skapade schemapost');
        return redirect()->route('admin.shifts.index')->with('success', 'Schemapost skapad.');
    }

    public function edit(GuideShift $shift)
    {
        return view('admin.shifts.form', [
            'shift' => $shift,
            'guides' => User::where('role', 'guide')->orderBy('name')->get(),
            'tours' => Tour::orderBy('tour_date')->get(),
        ]);
    }

    public function update(Request $request, GuideShift $shift)
    {
        $old = $shift->toArray();
        $data = $this->validated($request);
        $data['updated_by'] = auth()->id();
        $shift->update($data);
        LogService::log('guide_shift', $shift->id, 'updated', $old, $shift->fresh()->toArray(), 'Uppdaterade schemapost');
        return redirect()->route('admin.shifts.index')->with('success', 'Schemapost uppdaterad.');
    }

    public function destroy(GuideShift $shift)
    {
        $old = $shift->toArray();
        $shift->delete();
        LogService::log('guide_shift', $shift->id, 'deleted', $old, null, 'Tog bort schemapost');
        return back()->with('success', 'Schemapost borttagen.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'guide_id' => 'required|exists:users,id',
            'tour_id' => 'nullable|exists:tours,id',
            'shift_type' => 'required|in:tour,work_shift,meeting,maintenance,blocked',
            'title' => 'required|string|max:255',
            'shift_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'notes' => 'nullable|string',
        ]);
    }
}
