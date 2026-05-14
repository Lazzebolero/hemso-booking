<?php

namespace App\Http\Controllers;

use App\Models\VisitorDog;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class VisitorDogController extends Controller
{
    public function create(): View
    {
        $activeRole = session('active_role');

        $viewName = $activeRole === Roles::GUIDE
            ? 'visitor-dogs.guide-form'
            : 'visitor-dogs.host-form';

        return view($viewName, [
            'defaultVisitDate' => now()->format('Y-m-d'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dog_name' => ['required', 'string', 'max:120'],
            'breed' => ['nullable', 'string', 'max:120'],
            'owner_phone' => ['nullable', 'string', 'max:40'],
            'visit_date' => ['required', 'date'],
            'tour_start_time' => ['nullable', 'date_format:H:i'],
            'photo' => [
                'nullable',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'])
                    ->max(10240),
            ],
        ], [
            'dog_name.required' => 'Ange hundens namn.',
            'visit_date.required' => 'Ange datum.',
            'photo.max' => 'Bilden får vara högst 10 MB.',
        ]);

        $photoPath = null;
        $uploaded = $request->file('photo');
        if ($uploaded instanceof UploadedFile && $uploaded->isValid()) {
            $stored = $uploaded->store(
                'visitor_dogs/'.now()->format('Y/m'),
                'public'
            );
            $photoPath = $stored !== false ? $stored : null;
        }

        $activeRole = session('active_role');
        if (! is_string($activeRole) || ! in_array($activeRole, [Roles::GUIDE, Roles::HOST], true)) {
            abort(403);
        }

        VisitorDog::query()->create([
            'dog_name' => $validated['dog_name'],
            'breed' => $validated['breed'] ?? null,
            'owner_phone' => $validated['owner_phone'] ?? null,
            'visit_date' => $validated['visit_date'],
            'tour_start_time' => $validated['tour_start_time'] ?? null,
            'photo_path' => $photoPath,
            'registered_by' => $request->user()->id,
            'registered_as_role' => $activeRole,
        ]);

        return redirect()
            ->route('visitor-dogs.create')
            ->with('success', 'Hunden är registrerad.');
    }
}
