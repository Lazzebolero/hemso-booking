<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Collection;

class GuideLanguageMatchService
{
    /**
     * @return list<string>
     */
    public function requiredLanguageCodesForTour(Tour $tour): array
    {
        $tour->loadMissing(['bookings.languages']);

        return $this->normalizeLanguageCodes(
            $tour->bookings
                ->where('is_waitlist', false)
                ->whereNotIn('status', ['cancelled'])
                ->flatMap(fn ($booking) => $booking->languages->pluck('code'))
        );
    }

    /**
     * @return list<string>
     */
    public function guideLanguageCodes(User $guide): array
    {
        $guide->loadMissing('guideLanguages');

        return $this->normalizeLanguageCodes(
            $guide->guideLanguages->pluck('code')
        );
    }

    /**
     * @param  list<string>  $requiredCodes
     * @return list<string>
     */
    public function missingLanguageCodes(User $guide, array $requiredCodes): array
    {
        if ($requiredCodes === []) {
            return [];
        }

        return array_values(array_diff(
            $requiredCodes,
            $this->guideLanguageCodes($guide)
        ));
    }

    /**
     * @param  list<string>  $requiredCodes
     */
    public function hasLanguageMismatch(User $guide, array $requiredCodes): bool
    {
        return $this->missingLanguageCodes($guide, $requiredCodes) !== [];
    }

    /**
     * @param  list<string>  $missingCodes
     */
    public function mismatchMessage(array $missingCodes): string
    {
        if ($missingCodes === []) {
            return '';
        }

        return 'Guiden saknar bokade språk: '.implode(', ', $missingCodes).'.';
    }

    /**
     * @param  list<string>  $bookingLanguageCodes
     */
    public function bookingGuideLanguageWarning(Tour $tour, array $bookingLanguageCodes): ?string
    {
        $tour->loadMissing(['guide.guideLanguages']);

        if ($tour->guide === null) {
            return null;
        }

        $bookingCodes = $this->normalizeLanguageCodes($bookingLanguageCodes);

        if ($bookingCodes === []) {
            return null;
        }

        $missingFromBooking = array_values(array_diff(
            $bookingCodes,
            $this->guideLanguageCodes($tour->guide)
        ));

        if ($missingFromBooking === []) {
            return null;
        }

        return sprintf(
            'Bokningen har språk %s. Guiden %s saknar %s.',
            implode(', ', $bookingCodes),
            $tour->guide->name,
            implode(', ', $missingFromBooking)
        );
    }

    /**
     * @param  list<string>  $requiredCodes
     * @return array{
     *     required_language_codes: list<string>,
     *     missing_language_codes: list<string>,
     *     has_language_mismatch: bool,
     *     message: string
     * }
     */
    public function assessGuideForTour(User $guide, Tour $tour): array
    {
        $requiredCodes = $this->requiredLanguageCodesForTour($tour);
        $missingCodes = $this->missingLanguageCodes($guide, $requiredCodes);

        return [
            'required_language_codes' => $requiredCodes,
            'missing_language_codes' => $missingCodes,
            'has_language_mismatch' => $missingCodes !== [],
            'message' => $this->mismatchMessage($missingCodes),
        ];
    }

    /**
     * @param  Collection<int, mixed>|array<int, string|null>  $codes
     * @return list<string>
     */
    private function normalizeLanguageCodes(Collection|array $codes): array
    {
        return collect($codes)
            ->filter(fn ($code) => is_string($code) && $code !== '')
            ->map(fn (string $code) => strtoupper($code))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
