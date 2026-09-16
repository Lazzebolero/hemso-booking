<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class TourPhotoController extends Controller
{
    public function create(Tour $tour): View
    {
        $this->ensureGuideOwnsTour($tour);

        $tour->load('tourType');

        return view('guide.tour-photo-create', [
            'tour' => $tour,
        ]);
    }

    public function store(Request $request, Tour $tour): RedirectResponse
    {
        $this->ensureGuideOwnsTour($tour);

        $validated = $request->validate([
            'photo' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp'])
                    ->max(10240),
            ],
            'caption' => ['nullable', 'string', 'max:255'],
        ], [
            'photo.required' => 'Ta en bild eller välj en bild först.',
            'photo.max' => 'Bilden får vara högst 10 MB.',
        ]);

        $uploaded = $request->file('photo');
        if (! $uploaded instanceof UploadedFile || ! $uploaded->isValid()) {
            return back()->withErrors(['photo' => 'Bilden kunde inte läsas.'])->withInput();
        }

        $storedPath = $uploaded->store('tour_photos/'.now()->format('Y/m'), 'public');

        $photoData = [
            'tour_id' => $tour->id,
            'uploaded_by' => $request->user()->id,
            'path' => $storedPath,
            'original_name' => $uploaded->getClientOriginalName(),
            'mime_type' => $uploaded->getClientMimeType(),
            'size' => $uploaded->getSize(),
            'caption' => $validated['caption'] ?? null,
        ];

        if (Schema::hasColumn('tour_photos', 'image_path')) {
            $photoData['image_path'] = $storedPath;
        }

        TourPhoto::query()->create($photoData);

        return redirect()
            ->route('guide.tours.show', $tour)
            ->with('success', 'Bilden är uppladdad.');
    }

    public function destroy(Request $request, Tour $tour, TourPhoto $tourPhoto): RedirectResponse
    {
        $this->ensureGuideOwnsTour($tour);
        abort_unless($tourPhoto->tour_id === $tour->id, 404);

        Storage::disk('public')->delete($tourPhoto->path);
        $tourPhoto->delete();

        return back()->with('success', 'Bilden är borttagen.');
    }

    private function ensureGuideOwnsTour(Tour $tour): void
    {
        abort_unless((int) $tour->guide_id === (int) auth()->id(), 403);
    }
}
