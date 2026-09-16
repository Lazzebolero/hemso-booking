<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\FacilityOccupancySetting;

class FacilityOccupancyService
{
    public function ongoingTourGuestCount(): int
    {
        return (int) Booking::query()
            ->whereHas('tour', fn ($query) => $query->where('status', 'started'))
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false)
            ->sum('total_count');
    }

    public function extraCount(): int
    {
        return (int) FacilityOccupancySetting::current()->extra_count;
    }

    public function totalInFacility(): int
    {
        return $this->ongoingTourGuestCount() + $this->extraCount();
    }

    /**
     * @return array{
     *     ongoing_tour_guests: int,
     *     extra_count: int,
     *     total_in_facility: int
     * }
     */
    public function snapshot(): array
    {
        $ongoing = $this->ongoingTourGuestCount();
        $extra = $this->extraCount();

        return [
            'ongoing_tour_guests' => $ongoing,
            'extra_count' => $extra,
            'total_in_facility' => $ongoing + $extra,
        ];
    }

    public function updateExtraCount(int $count, int $userId): FacilityOccupancySetting
    {
        $setting = FacilityOccupancySetting::current();
        $old = (int) $setting->extra_count;

        $setting->update([
            'extra_count' => max(0, $count),
            'updated_by' => $userId,
        ]);

        if (class_exists(LogService::class) && $old !== (int) $setting->extra_count) {
            LogService::log(
                'facility_occupancy',
                $setting->id,
                'extra_count_updated',
                ['extra_count' => $old],
                ['extra_count' => (int) $setting->extra_count],
                'Uppdaterade övrigt antal i anläggningen'
            );
        }

        return $setting->fresh();
    }
}
