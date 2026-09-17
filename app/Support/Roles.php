<?php

namespace App\Support;

class Roles
{
    public const ADMIN = 'admin';

    public const HOST = 'host';

    public const GUIDE = 'guide';

    public const RESTAURANT = 'restaurant';

    public const RESTAURANT_STATISTIK = 'restaurant_statistik';

    public const ELEV = 'elev';

    public const PRODUKTION_ADMIN = 'produktion_admin';

    public const PRODUKTION_PERSONAL = 'produktion_personal';

    public static function labels(): array
    {
        return [
            self::ADMIN => 'Admin',
            self::HOST => 'Värd',
            self::GUIDE => 'Guide',
            self::RESTAURANT => 'Restaurang',
            self::RESTAURANT_STATISTIK => 'Restaurang statistik',
            self::ELEV => 'Trainee / elev',
            self::PRODUKTION_ADMIN => 'Produktion admin',
            self::PRODUKTION_PERSONAL => 'Produktion personal',
        ];
    }

    public static function descriptions(): array
    {
        return [
            self::ADMIN => 'Administration och full kontroll',
            self::HOST => 'Välj efter inloggning: vanlig bokningsdashboard, eller mobil personalvy (som restaurang) utan tur- och bokningsadmin där.',
            self::GUIDE => 'Guidevy för turer och rapportering',
            self::RESTAURANT => 'Personalsida för restaurang',
            self::RESTAURANT_STATISTIK => 'Ren statistiksida för restaurangskärm utan navigation',
            self::ELEV => 'Syns i arbetsschemat men loggar inte in i appen. Kan senare uppgraderas till guide.',
            self::PRODUKTION_ADMIN => 'TV-produktion: stämpla in/ut i berget och hantera deltagare.',
            self::PRODUKTION_PERSONAL => 'TV-produktion: stämpla in/ut i berget och ta med deltagare som grupp.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function productionLoginRoles(): array
    {
        return [
            self::PRODUKTION_ADMIN,
            self::PRODUKTION_PERSONAL,
        ];
    }

    public static function isProductionLoginRole(string $slug): bool
    {
        return in_array($slug, self::productionLoginRoles(), true);
    }

    /**
     * Hemsö-personal som hanteras under /admin/users. TV-produktionens
     * admin, personal och deltagare hör inte hit.
     *
     * @return list<string>
     */
    public static function hemsoStaffRoles(): array
    {
        return array_values(array_diff(self::all(), self::productionLoginRoles()));
    }

    /**
     * @return list<string>
     */
    public static function scheduleOnlyRoles(): array
    {
        return [
            self::ELEV,
        ];
    }

    /**
     * @return list<string>
     */
    public static function loginRoles(): array
    {
        return array_values(array_diff(self::all(), self::scheduleOnlyRoles()));
    }

    public static function isScheduleOnly(string $slug): bool
    {
        return in_array($slug, self::scheduleOnlyRoles(), true);
    }

    /**
     * Roller som kan ligga på arbetsschema (inte TV-produktion).
     *
     * @return list<string>
     */
    public static function scheduleStaffRoles(): array
    {
        return [
            self::ADMIN,
            self::HOST,
            self::GUIDE,
            self::ELEV,
            self::RESTAURANT,
        ];
    }

    /**
     * Sorteras tillsammans med guiderna i schemamallen.
     *
     * @return list<string>
     */
    public static function schedulePriorityRoles(): array
    {
        return [
            self::ADMIN,
            self::HOST,
            self::GUIDE,
            self::ELEV,
        ];
    }

    public static function all(): array
    {
        return array_keys(self::labels());
    }
}
