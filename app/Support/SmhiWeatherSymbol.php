<?php

namespace App\Support;

class SmhiWeatherSymbol
{
    /**
     * @var array<int, string>
     */
    private const LABELS = [
        1 => 'Klart',
        2 => 'Nästan klart',
        3 => 'Halvklart',
        4 => 'Molnigt',
        5 => 'Mycket moln',
        6 => 'Mulet',
        7 => 'Dimma',
        8 => 'Lätt regnskurar',
        9 => 'Regnskurar',
        10 => 'Kraftiga regnskurar',
        11 => 'Åska',
        12 => 'Lätt snöblandat regn',
        13 => 'Snöblandat regn',
        14 => 'Kraftigt snöblandat regn',
        15 => 'Lätt snöfall',
        16 => 'Snöfall',
        17 => 'Kraftigt snöfall',
        18 => 'Regn',
        19 => 'Kraftigt regn',
        20 => 'Åskregn',
        21 => 'Lätt snöblandat regn och åska',
        22 => 'Snöblandat regn och åska',
        23 => 'Kraftigt snöblandat regn och åska',
        24 => 'Lätt snöfall och åska',
        25 => 'Snöfall och åska',
        26 => 'Kraftigt snöfall och åska',
        27 => 'Dimma',
    ];

    public static function label(?int $code): ?string
    {
        if ($code === null) {
            return null;
        }

        return self::LABELS[$code] ?? 'Väder';
    }
}
