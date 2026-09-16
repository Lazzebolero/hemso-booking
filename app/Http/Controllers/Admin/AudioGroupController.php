<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioGroup;
use App\Services\AudioFleetPlaybackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AudioGroupController extends Controller
{
    public function index(): View
    {
        $groups = AudioGroup::query()
            ->withCount('devices')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.audio.groups.index', compact('groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        AudioGroup::create($data);

        return back()->with('success', 'Grupp skapad.');
    }

    public function show(AudioGroup $group): View
    {
        $group->load([
            'devices.loudspeakers.sound',
        ]);

        return view('admin.audio.groups.show', compact('group'));
    }

    public function update(Request $request, AudioGroup $group): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        $group->update($data);

        return back()->with('success', 'Grupp uppdaterad.');
    }

    public function destroy(AudioGroup $group): RedirectResponse
    {
        $group->delete();

        return redirect()
            ->route('admin.audio.groups.index')
            ->with('success', 'Grupp borttagen. Enheterna är kvar men utan grupp.');
    }

    public function play(AudioGroup $group, AudioFleetPlaybackService $playback): RedirectResponse
    {
        $started = $playback->playGroup($group);

        if ($started === 0) {
            return back()->withErrors(['sound' => 'Inga kanaler i gruppen har ljud tilldelat.']);
        }

        return back()->with('success', "Uppspelning startad på {$started} kanaler i gruppen.");
    }

    public function stop(AudioGroup $group, AudioFleetPlaybackService $playback): RedirectResponse
    {
        $playback->stopGroup($group);

        return back()->with('success', 'Alla kanaler i gruppen stoppade.');
    }
}
