<?php

namespace App\Services;

use App\Models\User;
use App\Models\VisitorDog;
use App\Support\VisitorDogActivityLogger;
use App\Support\VisitorDogSupport;
use Illuminate\Http\UploadedFile;

class VisitorDogRegistrationService
{
    /**
     * @param  array{
     *     dog_name: string,
     *     breed?: string|null,
     *     owner_phone?: string|null,
     *     visit_date: string,
     *     tour_start_time?: string|null,
     * }  $validated
     */
    public function register(
        array $validated,
        User $user,
        string $registeredAsRole,
        ?UploadedFile $photo = null,
    ): VisitorDog {
        $dog = VisitorDog::query()->create([
            'dog_name' => $validated['dog_name'],
            'breed' => $validated['breed'] ?? null,
            'owner_phone' => $validated['owner_phone'] ?? null,
            'visit_date' => $validated['visit_date'],
            'tour_start_time' => $validated['tour_start_time'] ?? null,
            'photo_path' => VisitorDogSupport::storeUploadedPhoto($photo),
            'registered_by' => $user->id,
            'registered_as_role' => $registeredAsRole,
        ]);

        VisitorDogActivityLogger::logCreated($dog);

        return $dog;
    }
}
