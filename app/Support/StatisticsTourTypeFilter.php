<?php

namespace App\Support;

use App\Models\TourType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StatisticsTourTypeFilter
{
    public const ALL_VALUE = 'all';

    /**
     * @return array{
     *     0: ?int,
     *     1: string,
     *     2: Collection<int, TourType>,
     *     3: string
     * }
     */
    public static function resolve(Request $request): array
    {
        $tourTypes = TourType::activeOrdered();

        if (! $request->has('tour_type_id')) {
            return [
                null,
                'Alla turtyper',
                $tourTypes,
                self::ALL_VALUE,
            ];
        }

        $rawValue = $request->string('tour_type_id')->toString();

        if ($rawValue === self::ALL_VALUE) {
            return [
                null,
                'Alla turtyper',
                $tourTypes,
                self::ALL_VALUE,
            ];
        }

        $tourTypeId = (int) $rawValue;

        if ($tourTypeId <= 0 || ! $tourTypes->contains('id', $tourTypeId)) {
            return [
                null,
                'Alla turtyper',
                $tourTypes,
                self::ALL_VALUE,
            ];
        }

        return [
            $tourTypeId,
            self::labelFor($tourTypeId, $tourTypes),
            $tourTypes,
            self::formValue($tourTypeId),
        ];
    }

    public static function formValue(?int $tourTypeId): string
    {
        return $tourTypeId === null ? self::ALL_VALUE : (string) $tourTypeId;
    }

    /**
     * @param  Collection<int, TourType>  $tourTypes
     */
    private static function labelFor(int $tourTypeId, Collection $tourTypes): string
    {
        return $tourTypes->firstWhere('id', $tourTypeId)?->name ?? 'Vald turtyp';
    }
}
