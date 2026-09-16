<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BookingParticipantService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     men_count: int,
     *     women_count: int,
     *     youth_count: int,
     *     child_count: int,
     *     unspecified_count: int,
     *     total_count: int
     * }
     */
    public function normalize(array $input): array
    {
        $men = max(0, (int) ($input['men_count'] ?? 0));
        $women = max(0, (int) ($input['women_count'] ?? 0));
        $youth = max(0, (int) ($input['youth_count'] ?? 0));
        $child = max(0, (int) ($input['child_count'] ?? 0));
        $unspecified = max(0, (int) ($input['unspecified_count'] ?? 0));

        $categorized = $men + $women + $youth + $child;

        if (array_key_exists('participant_count', $input) && $input['participant_count'] !== null && $input['participant_count'] !== '') {
            $total = max(0, (int) $input['participant_count']);

            if ($total <= 0) {
                throw ValidationException::withMessages([
                    'participant_count' => 'Du måste ange minst 1 deltagare totalt.',
                ]);
            }

            if ($categorized + $unspecified === 0) {
                return $this->counts($men, $women, $youth, $child, $total - $categorized, $total);
            }
        }

        if (array_key_exists('total_count', $input) && $input['total_count'] !== null && $input['total_count'] !== '') {
            $total = max(0, (int) $input['total_count']);
        } else {
            $total = $categorized + $unspecified;
        }

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'men_count' => 'Du måste ange minst 1 deltagare totalt.',
            ]);
        }

        if ($categorized > $total) {
            throw ValidationException::withMessages([
                'unspecified_count' => 'Summan män, kvinnor, ungdomar och barn får inte överstiga totalt antal.',
            ]);
        }

        $unspecified = $total - $categorized;

        return $this->counts($men, $women, $youth, $child, $unspecified, $total);
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @return array{
     *     men: int,
     *     women: int,
     *     youth: int,
     *     children: int,
     *     unspecified: int,
     *     total: int
     * }
     */
    public function summarizeBookings(Collection $bookings): array
    {
        $active = $bookings
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false);

        return [
            'men' => (int) $active->sum('men_count'),
            'women' => (int) $active->sum('women_count'),
            'youth' => (int) $active->sum('youth_count'),
            'children' => (int) $active->sum('child_count'),
            'unspecified' => (int) $active->sum('unspecified_count'),
            'total' => (int) $active->sum('total_count'),
        ];
    }

    public function formatCategorySummary(int $men, int $women, int $youth, int $children, int $unspecified = 0): string
    {
        $parts = [
            "M{$men}",
            "K{$women}",
            "U{$youth}",
            "B{$children}",
        ];

        if ($unspecified > 0) {
            $parts[] = "O{$unspecified}";
        }

        return implode(' ', $parts);
    }

    /**
     * @return array{
     *     men_count: int,
     *     women_count: int,
     *     youth_count: int,
     *     child_count: int,
     *     unspecified_count: int,
     *     total_count: int
     * }
     */
    private function counts(int $men, int $women, int $youth, int $child, int $unspecified, int $total): array
    {
        return [
            'men_count' => $men,
            'women_count' => $women,
            'youth_count' => $youth,
            'child_count' => $child,
            'unspecified_count' => $unspecified,
            'total_count' => $total,
        ];
    }
}
