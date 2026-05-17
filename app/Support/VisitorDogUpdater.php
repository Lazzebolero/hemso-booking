<?php

namespace App\Support;

use App\Http\Requests\UpdateVisitorDogRequest;
use App\Models\VisitorDog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VisitorDogUpdater
{
    public static function apply(UpdateVisitorDogRequest $request, VisitorDog $visitorDog): void
    {
        $validated = $request->validated();
        $oldValues = VisitorDogActivityLogger::snapshot($visitorDog);

        $photoPath = $visitorDog->photo_path;

        if ($request->boolean('remove_photo') && $photoPath !== null && $photoPath !== '') {
            if (Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = null;
        }

        $uploaded = VisitorDogSupport::uploadedPhotoFromRequest($request);
        if ($uploaded instanceof UploadedFile && $uploaded->isValid()) {
            if ($photoPath !== null && $photoPath !== '' && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            $stored = VisitorDogSupport::storeUploadedPhoto($uploaded);
            if ($stored !== null) {
                $photoPath = $stored;
            }
        }

        $visitorDog->update([
            'dog_name' => $validated['dog_name'],
            'breed' => $validated['breed'] ?? null,
            'owner_phone' => $validated['owner_phone'] ?? null,
            'visit_date' => $validated['visit_date'],
            'tour_start_time' => $validated['tour_start_time'] ?? null,
            'photo_path' => $photoPath,
        ]);

        VisitorDogActivityLogger::logUpdated($visitorDog, $oldValues);
    }

    public static function deletePhotoFile(VisitorDog $visitorDog): void
    {
        if ($visitorDog->photo_path === null || $visitorDog->photo_path === '') {
            return;
        }

        if (Storage::disk('public')->exists($visitorDog->photo_path)) {
            Storage::disk('public')->delete($visitorDog->photo_path);
        }
    }
}
