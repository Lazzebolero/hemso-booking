<?php

namespace App\Support;

use App\Models\VisitorDog;
use App\Services\LogService;
use Illuminate\Support\Str;

class VisitorDogActivityLogger
{
    public const ENTITY_TYPE = 'visitor_dog';

    public static function logCreated(VisitorDog $dog): void
    {
        LogService::log(
            self::ENTITY_TYPE,
            $dog->id,
            'created',
            null,
            self::snapshot($dog),
            sprintf('Registrerade besökshund %s', $dog->dog_name),
        );
    }

    public static function logUpdated(VisitorDog $dog, array $oldValues): void
    {
        $dog->refresh();

        LogService::log(
            self::ENTITY_TYPE,
            $dog->id,
            'updated',
            $oldValues,
            self::snapshot($dog),
            sprintf('Uppdaterade besökshund %s', $dog->dog_name),
        );
    }

    public static function logDeleted(VisitorDog $dog): void
    {
        LogService::log(
            self::ENTITY_TYPE,
            $dog->id,
            'deleted',
            self::snapshot($dog),
            null,
            sprintf('Tog bort besökshund %s', $dog->dog_name),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(VisitorDog $dog): array
    {
        return [
            'dog_name' => $dog->dog_name,
            'breed' => $dog->breed,
            'owner_phone' => $dog->owner_phone,
            'visit_date' => $dog->visit_date?->format('Y-m-d'),
            'tour_start_time' => $dog->tour_start_time !== null
                ? Str::of((string) $dog->tour_start_time)->substr(0, 5)->toString()
                : null,
            'has_photo' => $dog->photo_path !== null && $dog->photo_path !== '',
            'registered_by' => $dog->registered_by,
            'registered_as_role' => $dog->registered_as_role,
        ];
    }
}
