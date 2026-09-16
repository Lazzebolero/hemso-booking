<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

final class OpeningCheckTables
{
    /**
     * Memoize so dashboards do not hit information_schema on every related query.
     */
    public static function exist(): bool
    {
        return once(static fn (): bool => Schema::hasTable('opening_checks')
            && Schema::hasTable('opening_deviations'));
    }
}
