<?php

namespace App\Support;

class CountryCatalog
{
    /**
     * @return list<array{code: string, name: string, is_quick_pick: bool, sort_order: int}>
     */
    public static function defaults(): array
    {
        return [
            ['code' => 'se', 'name' => 'Sverige', 'is_quick_pick' => true, 'sort_order' => 1],
            ['code' => 'no', 'name' => 'Norge', 'is_quick_pick' => true, 'sort_order' => 2],
            ['code' => 'de', 'name' => 'Tyskland', 'is_quick_pick' => true, 'sort_order' => 3],
            ['code' => 'dk', 'name' => 'Danmark', 'is_quick_pick' => true, 'sort_order' => 4],
            ['code' => 'fi', 'name' => 'Finland', 'is_quick_pick' => true, 'sort_order' => 5],
            ['code' => 'nl', 'name' => 'Nederländerna', 'is_quick_pick' => true, 'sort_order' => 6],
            ['code' => 'gb', 'name' => 'Storbritannien', 'is_quick_pick' => true, 'sort_order' => 7],
            ['code' => 'fr', 'name' => 'Frankrike', 'is_quick_pick' => true, 'sort_order' => 8],
            ['code' => 'pl', 'name' => 'Polen', 'is_quick_pick' => true, 'sort_order' => 9],
            ['code' => 'us', 'name' => 'USA', 'is_quick_pick' => true, 'sort_order' => 10],
            ['code' => 'it', 'name' => 'Italien', 'is_quick_pick' => true, 'sort_order' => 11],
            ['code' => 'es', 'name' => 'Spanien', 'is_quick_pick' => true, 'sort_order' => 12],
            ['code' => 'be', 'name' => 'Belgien', 'is_quick_pick' => false, 'sort_order' => 20],
            ['code' => 'ch', 'name' => 'Schweiz', 'is_quick_pick' => false, 'sort_order' => 21],
            ['code' => 'at', 'name' => 'Österrike', 'is_quick_pick' => false, 'sort_order' => 22],
            ['code' => 'cz', 'name' => 'Tjeckien', 'is_quick_pick' => false, 'sort_order' => 23],
            ['code' => 'sk', 'name' => 'Slovakien', 'is_quick_pick' => false, 'sort_order' => 24],
            ['code' => 'ee', 'name' => 'Estland', 'is_quick_pick' => false, 'sort_order' => 25],
            ['code' => 'lv', 'name' => 'Lettland', 'is_quick_pick' => false, 'sort_order' => 26],
            ['code' => 'lt', 'name' => 'Litauen', 'is_quick_pick' => false, 'sort_order' => 27],
            ['code' => 'ua', 'name' => 'Ukraina', 'is_quick_pick' => false, 'sort_order' => 28],
            ['code' => 'ca', 'name' => 'Kanada', 'is_quick_pick' => false, 'sort_order' => 29],
            ['code' => 'ie', 'name' => 'Irland', 'is_quick_pick' => false, 'sort_order' => 30],
            ['code' => 'au', 'name' => 'Australien', 'is_quick_pick' => false, 'sort_order' => 31],
            ['code' => 'nz', 'name' => 'Nya Zeeland', 'is_quick_pick' => false, 'sort_order' => 32],
            ['code' => 'pt', 'name' => 'Portugal', 'is_quick_pick' => false, 'sort_order' => 33],
            ['code' => 'gr', 'name' => 'Grekland', 'is_quick_pick' => false, 'sort_order' => 34],
            ['code' => 'hu', 'name' => 'Ungern', 'is_quick_pick' => false, 'sort_order' => 35],
            ['code' => 'ro', 'name' => 'Rumänien', 'is_quick_pick' => false, 'sort_order' => 36],
            ['code' => 'hr', 'name' => 'Kroatien', 'is_quick_pick' => false, 'sort_order' => 37],
            ['code' => 'si', 'name' => 'Slovenien', 'is_quick_pick' => false, 'sort_order' => 38],
            ['code' => 'tr', 'name' => 'Turkiet', 'is_quick_pick' => false, 'sort_order' => 39],
            ['code' => 'cn', 'name' => 'Kina', 'is_quick_pick' => false, 'sort_order' => 40],
            ['code' => 'jp', 'name' => 'Japan', 'is_quick_pick' => false, 'sort_order' => 41],
            ['code' => 'kr', 'name' => 'Sydkorea', 'is_quick_pick' => false, 'sort_order' => 42],
            ['code' => 'in', 'name' => 'Indien', 'is_quick_pick' => false, 'sort_order' => 43],
            ['code' => 'br', 'name' => 'Brasilien', 'is_quick_pick' => false, 'sort_order' => 44],
            ['code' => 'mx', 'name' => 'Mexiko', 'is_quick_pick' => false, 'sort_order' => 45],
            ['code' => 'ar', 'name' => 'Argentina', 'is_quick_pick' => false, 'sort_order' => 46],
            ['code' => 'za', 'name' => 'Sydafrika', 'is_quick_pick' => false, 'sort_order' => 47],
        ];
    }

    public const DEFAULT_MAX_QUICK_PICKS = 12;

    public const ABSOLUTE_MAX_QUICK_PICKS = 24;

    public static function maxQuickPicks(): int
    {
        $configured = (int) setting('country_quick_pick_max', self::DEFAULT_MAX_QUICK_PICKS);

        return max(1, min(self::ABSOLUTE_MAX_QUICK_PICKS, $configured));
    }
}
