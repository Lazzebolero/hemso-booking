<?php

namespace App\Models;

use Database\Factories\HistoricalDailyVisitorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class HistoricalDailyVisitor extends Model
{
    /** @use HasFactory<HistoricalDailyVisitorFactory> */
    use HasFactory;

    protected $fillable = [
        'stat_date',
        'participant_count',
        'iso_week',
        'iso_week_year',
        'iso_weekday',
        'imported_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'imported_at' => 'datetime',
        ];
    }

    /**
     * @return array{iso_week: int, iso_week_year: int, iso_weekday: int}
     */
    public static function isoAttributesFromDate(Carbon $date): array
    {
        return [
            'iso_week' => (int) $date->isoWeek,
            'iso_week_year' => (int) $date->isoWeekYear,
            'iso_weekday' => (int) $date->dayOfWeekIso,
        ];
    }
}
