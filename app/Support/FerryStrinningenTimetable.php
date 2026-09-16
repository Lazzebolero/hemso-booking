<?php

namespace App\Support;

/**
 * Officiell tidtabell för avgångar från Strinningen (Hemsöleden).
 *
 * Källa: Trafikverkets tidtabell för Hemsöleden (september 2026).
 * Uppdateras i kod när Trafikverket publicerar ny tabell – inte live via API.
 * Live-API används bara för senast avgått, nästa avgång, förseningar och extraturer.
 *
 * @see https://www.trafikverket.se/resa-och-trafik/farjetrafik/hemsoleden/
 */
class FerryStrinningenTimetable
{
    public const REVISION = '2026-09-10';

    /**
     * @return list<array{time: string, requires_call: bool, no_duplicates: bool}>
     */
    public static function departuresFor(string $dayType): array
    {
        return match (FerryDayTypes::normalize($dayType)) {
            FerryDayTypes::SATURDAY, FerryDayTypes::SUNDAY => self::saturdayAndSunday(),
            default => self::weekday(),
        };
    }

    public static function revision(): string
    {
        return self::REVISION;
    }

    /**
     * Måndag–fredag.
     *
     * @return list<array{time: string, requires_call: bool, no_duplicates: bool}>
     */
    private static function weekday(): array
    {
        return self::rows([
            ['00:00', true],
            ['01:00', true],
            ['02:00', true],
            ['04:30', true],
            ['05:00', true],
            ['05:30', true],
            ['06:00', true],
            ['06:20', false],
            ['06:40', true],
            ['07:00', false],
            ['07:20', false],
            ['07:40', true],
            ['08:00', false],
            ['08:30', false],
            ['09:00', false],
            ['09:30', false],
            ['10:00', false],
            ['10:30', false],
            ['11:00', false],
            ['11:30', false],
            ['12:00', false],
            ['12:30', false],
            ['13:00', false, true],
            ['13:40', false],
            ['14:00', false],
            ['14:40', false],
            ['15:00', false],
            ['15:30', false],
            ['16:00', false],
            ['16:20', false],
            ['16:40', false],
            ['17:00', false],
            ['17:30', false, true],
            ['18:00', true],
            ['18:30', true],
            ['19:00', true],
            ['19:30', true],
            ['20:00', true],
            ['20:30', true],
            ['21:00', true],
            ['21:30', true],
            ['22:00', true],
            ['22:30', true],
            ['23:00', true],
            ['23:30', true],
        ]);
    }

    /**
     * Lördag och söndag – samma tabell.
     *
     * @return list<array{time: string, requires_call: bool, no_duplicates: bool}>
     */
    private static function saturdayAndSunday(): array
    {
        return self::rows([
            ['00:00', true],
            ['01:00', true],
            ['02:00', true],
            ['04:30', true],
            ['05:00', true],
            ['05:30', true],
            ['06:00', true],
            ['06:30', true],
            ['07:00', true],
            ['07:30', true],
            ['08:00', true],
            ['08:30', true],
            ['09:00', true],
            ['09:30', true],
            ['10:00', false],
            ['10:30', false],
            ['11:00', false],
            ['11:30', false],
            ['12:00', false],
            ['12:30', false],
            ['13:00', false, true],
            ['13:40', false],
            ['14:00', false],
            ['14:30', false],
            ['15:00', false],
            ['15:30', false],
            ['16:00', false],
            ['16:30', false],
            ['17:00', false],
            ['17:30', false, true],
            ['18:00', true],
            ['18:30', true],
            ['19:00', true],
            ['19:30', true],
            ['20:00', true],
            ['20:30', true],
            ['21:00', true],
            ['21:30', true],
            ['22:00', true],
            ['22:30', true],
            ['23:00', true],
            ['23:30', true],
        ]);
    }

    /**
     * @param  list<array{0: string, 1: bool, 2?: bool}>  $times
     * @return list<array{time: string, requires_call: bool, no_duplicates: bool}>
     */
    private static function rows(array $times): array
    {
        return array_map(
            fn (array $row): array => [
                'time' => $row[0],
                'requires_call' => $row[1],
                'no_duplicates' => $row[2] ?? false,
            ],
            $times,
        );
    }
}
