<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourPhoto;
use App\Services\LogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TourPhotoController extends Controller
{
    public function show(Tour $tour, TourPhoto $tourPhoto): BinaryFileResponse
    {
        $this->ensurePhotoBelongsToTour($tour, $tourPhoto);

        return $this->streamPhoto($tourPhoto);
    }

    public function download(Tour $tour, TourPhoto $tourPhoto): BinaryFileResponse
    {
        $this->ensurePhotoBelongsToTour($tour, $tourPhoto);
        $absolutePath = $this->photoPath($tourPhoto);
        $filename = $tourPhoto->original_name ?: basename($tourPhoto->image_path);

        return response()->download($absolutePath, $filename);
    }

    public function destroy(Tour $tour, TourPhoto $tourPhoto): RedirectResponse
    {
        $this->ensurePhotoBelongsToTour($tour, $tourPhoto);

        $old = $tourPhoto->toArray();

        if (Storage::disk('public')->exists($tourPhoto->image_path)) {
            Storage::disk('public')->delete($tourPhoto->image_path);
        }

        $tourPhoto->delete();

        if (class_exists(LogService::class)) {
            LogService::log(
                'tour_photo',
                $tourPhoto->id,
                'deleted',
                $old,
                null,
                'Tog bort turbild'
            );
        }

        return redirect()
            ->route('admin.tours.show', $tour)
            ->with('success', 'Bilden togs bort.');
    }

    private function ensurePhotoBelongsToTour(Tour $tour, TourPhoto $tourPhoto): void
    {
        if ((int) $tourPhoto->tour_id !== (int) $tour->id) {
            abort(404);
        }
    }

    private function streamPhoto(TourPhoto $tourPhoto): BinaryFileResponse
    {
        return response()->file($this->photoPath($tourPhoto));
    }

    private function photoPath(TourPhoto $tourPhoto): string
    {
        if (! Storage::disk('public')->exists($tourPhoto->image_path)) {
            abort(404);
        }

        $absolutePath = Storage::disk('public')->path($tourPhoto->image_path);

        if (! is_file($absolutePath)) {
            abort(404);
        }

        return $absolutePath;
    }
}
