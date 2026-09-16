<?php

namespace App\Services;

use App\Models\Tour;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class TourBookingSequenceService
{
    public function supportsBookingSequenceFilter(): bool
    {
        return Schema::hasColumn('tour_types', 'include_in_booking_sequence');
    }

    public function applyEligibleScope(Builder $query): Builder
    {
        if (! $this->supportsBookingSequenceFilter()) {
            return $query->whereRaw('1 = 0');
        }

        if (Schema::hasColumn('tours', 'exclude_from_booking_sequence')) {
            $query->where('tours.exclude_from_booking_sequence', 0);
        }

        if (Schema::hasColumn('tours', 'closed_for_bookings')) {
            $query->where('tours.closed_for_bookings', 0);
        }

        return $query
            ->whereNotNull('tours.tour_type_id')
            ->whereExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('tour_types')
                    ->whereColumn('tour_types.id', 'tours.tour_type_id')
                    ->where('tour_types.include_in_booking_sequence', 1);
            });
    }

    public function isTourEligible(Tour $tour): bool
    {
        if (! $this->supportsBookingSequenceFilter()) {
            return false;
        }

        if (Schema::hasColumn('tours', 'exclude_from_booking_sequence') && $tour->exclude_from_booking_sequence) {
            return false;
        }

        if (Schema::hasColumn('tours', 'closed_for_bookings') && $tour->closed_for_bookings) {
            return false;
        }

        if ($tour->tour_type_id === null) {
            return false;
        }

        $tour->loadMissing('tourType');

        return (bool) $tour->tourType?->include_in_booking_sequence;
    }
}
