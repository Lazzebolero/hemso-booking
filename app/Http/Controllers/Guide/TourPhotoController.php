<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TourPhotoController extends Controller
{
    public function store(Request $request, Tour $tour): RedirectResponse
    {
        $this->ensureGuideOwnsTour($tour);

        $request->validate([
            'photo' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'])
                    ->max(10240),
            ],
            'caption' => ['nullable', 'string', 'max:255'],
        ], [
            'photo.required' => 'Välj en bild att ladda upp.',
            'photo.max' => 'Bilden får vara högst 10 MB.',
        ]);

        $uploaded = $request->file('photo');

        if (! $uploaded instanceof UploadedFile || ! $uploaded->isValid()) {
            return back()->withErrors(['photo' => 'Bilden kunde inte laddas upp.']);
        }

        $stored = $uploaded->store('tour_photos/'.now()->format('Y/m'), 'public');

        if ($stored === false) {
            return back()->withErrors(['photo' => 'Bilden kunde inte sparas.']);
        }

        $tour->photos()->create([
            'uploaded_by' => $request->user()?->id,
            'image_path' => $stored,
            'original_name' => $uploaded->getClientOriginalName(),
            'caption' => $request->filled('caption') ? $request->string('caption')->toString() : null,
            'mime_type' => $uploaded->getMimeType(),
            'size' => $uploaded->getSize(),
            'taken_at' => now(),
        ]);

        return redirect()
            ->route('guide.tours.show', $tour)
            ->with('success', 'Bilden laddades upp.');
    }

    public function show(Tour $tour, TourPhoto $tourPhoto): BinaryFileResponse
    {
        $this->ensureGuideOwnsTour($tour);
        $this->ensurePhotoBelongsToTour($tour, $tourPhoto);

        return $this->streamPhoto($tourPhoto);
    }

    private function ensureGuideOwnsTour(Tour $tour): void
    {
        if ((int) $tour->guide_id !== (int) auth()->id()) {
            abort(403);
        }
    }

    private function ensurePhotoBelongsToTour(Tour $tour, TourPhoto $tourPhoto): void
    {
        if ((int) $tourPhoto->tour_id !== (int) $tour->id) {
            abort(404);
        }
    }

    private function streamPhoto(TourPhoto $tourPhoto): BinaryFileResponse
    {
        if (! Storage::disk('public')->exists($tourPhoto->image_path)) {
            abort(404);
        }

        $absolutePath = Storage::disk('public')->path($tourPhoto->image_path);

        if (! is_file($absolutePath)) {
            abort(404);
        }

        return response()->file($absolutePath);
    }
}
