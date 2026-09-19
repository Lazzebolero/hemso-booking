<?php

namespace App\Support;

class ShiftDefaultTimes
{
    public static function format(?string $start, ?string $end, string $fallbackStart = '10:00', ?string $fallbackEnd = null): string
    {
        $start = self::normalize($start) ?? $fallbackStart;
        $end = self::normalize($end) ?? $fallbackEnd;

        return $end ? $start.'-'.$end : $start;
    }

    public static function normalize(mixed $time): ?string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        $string = trim((string) $time);

        if ($string === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})[:.](\d{2})/', $string, $matches) === 1) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        return null;
    }
}
