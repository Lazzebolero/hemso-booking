<?php

namespace App\Support;

class Roles
{
    public const ADMIN = 'admin';

    public const HOST = 'host';

    public const GUIDE = 'guide';

    public const RESTAURANT = 'restaurant';

    public const RESTAURANT_STATISTIK = 'restaurant_statistik';

    public static function labels(): array
    {
        return [
            self::ADMIN => 'Admin',
            self::HOST => 'Värd',
            self::GUIDE => 'Guide',
            self::RESTAURANT => 'Restaurang',
            self::RESTAURANT_STATISTIK => 'Restaurang statistik',
        ];
    }

    public static function descriptions(): array
    {
        return [
            self::ADMIN => 'Administration och full kontroll',
            self::HOST => 'Efter inloggning väljer du bokningsdashboard eller mobil personalvy (som restaurang).',
            self::GUIDE => 'Guidevy för turer och rapportering',
            self::RESTAURANT => 'Personalsida för restaurang',
            self::RESTAURANT_STATISTIK => 'Ren statistiksida för restaurangskärm utan navigation',
        ];
    }

    public static function all(): array
    {
        return array_keys(self::labels());
    }
}
