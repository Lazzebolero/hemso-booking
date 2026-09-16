<?php

namespace App\Services;

use Illuminate\Http\Request;

class TimeClockLocationPayload
{
    public const STATUS_OK = 'ok';

    public const STATUS_MISSING = 'missing';

    public const STATUS_DENIED = 'denied';

    public const STATUS_UNAVAILABLE = 'unavailable';

    public const STATUS_TIMEOUT = 'timeout';

    public const STATUS_UNSUPPORTED = 'unsupported';

    public function __construct(
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly ?int $accuracyM,
        public readonly string $status,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $latitude = self::optionalLatitude($request->input('latitude'));
        $longitude = self::optionalLongitude($request->input('longitude'));
        $accuracyM = self::optionalAccuracy($request->input('accuracy_m'));
        $clientStatus = self::normalizeStatus($request->input('location_status'));

        if ($latitude !== null && $longitude !== null) {
            return new self($latitude, $longitude, $accuracyM, self::STATUS_OK);
        }

        return new self(null, null, null, $clientStatus ?? self::STATUS_MISSING);
    }

    /**
     * @return array<string, mixed>
     */
    public function clockInAttributes(): array
    {
        return [
            'clock_in_latitude' => $this->latitude,
            'clock_in_longitude' => $this->longitude,
            'clock_in_location_accuracy_m' => $this->accuracyM,
            'clock_in_location_status' => $this->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function clockOutAttributes(): array
    {
        return [
            'clock_out_latitude' => $this->latitude,
            'clock_out_longitude' => $this->longitude,
            'clock_out_location_accuracy_m' => $this->accuracyM,
            'clock_out_location_status' => $this->status,
        ];
    }

    public function isOk(): bool
    {
        return $this->status === self::STATUS_OK;
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_OK => 'GPS mottagen',
            self::STATUS_DENIED => 'Plats nekad',
            self::STATUS_UNAVAILABLE => 'Plats otillgänglig',
            self::STATUS_TIMEOUT => 'GPS timeout',
            self::STATUS_UNSUPPORTED => 'Stöds inte',
            self::STATUS_MISSING => 'Ingen GPS',
            default => $status ? ucfirst($status) : 'Okänd',
        };
    }

    private static function optionalLatitude(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $float = (float) $value;

        return $float >= -90 && $float <= 90 ? $float : null;
    }

    private static function optionalLongitude(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $float = (float) $value;

        return $float >= -180 && $float <= 180 ? $float : null;
    }

    private static function optionalAccuracy(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $int = (int) round((float) $value);

        return max(0, min($int, 100_000));
    }

    private static function normalizeStatus(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $allowed = [
            self::STATUS_OK,
            self::STATUS_MISSING,
            self::STATUS_DENIED,
            self::STATUS_UNAVAILABLE,
            self::STATUS_TIMEOUT,
            self::STATUS_UNSUPPORTED,
        ];

        return in_array($value, $allowed, true) ? $value : null;
    }

    public static function distanceFromFacilityKm(?float $latitude, ?float $longitude): ?float
    {
        $facilityLat = config('time_clock.facility_latitude');
        $facilityLng = config('time_clock.facility_longitude');

        if ($latitude === null || $longitude === null || ! is_numeric($facilityLat) || ! is_numeric($facilityLng)) {
            return null;
        }

        $earthRadiusKm = 6371;
        $latFrom = deg2rad((float) $facilityLat);
        $latTo = deg2rad($latitude);
        $latDelta = deg2rad($latitude - (float) $facilityLat);
        $lngDelta = deg2rad($longitude - (float) $facilityLng);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 1);
    }

    public static function isFarFromFacility(?float $latitude, ?float $longitude, float $thresholdKm = 3.0): bool
    {
        $distance = self::distanceFromFacilityKm($latitude, $longitude);

        return $distance !== null && $distance > $thresholdKm;
    }

    public static function locationDeviationForPunch(?string $status, ?float $latitude, ?float $longitude): ?array
    {
        if ($status !== self::STATUS_OK) {
            return [
                'code' => 'missing_location',
                'label' => 'Saknar GPS',
                'severity' => 'warning',
                'description' => 'Stämpling utan platsdata — granska vid misstanke.',
            ];
        }

        if (self::isFarFromFacility($latitude, $longitude)) {
            $distance = self::distanceFromFacilityKm($latitude, $longitude);

            return [
                'code' => 'far_from_facility',
                'label' => 'Långt från anläggning',
                'severity' => 'warning',
                'description' => sprintf('Plats ca %s km från referenspunkt.', number_format((float) $distance, 1, ',', ' ')),
            ];
        }

        return null;
    }
}
