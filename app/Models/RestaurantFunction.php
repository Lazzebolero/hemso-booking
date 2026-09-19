<?php

namespace App\Models;

use App\Support\ShiftDefaultTimes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class RestaurantFunction extends Model
{
    public const CACHE_KEY = 'restaurant_functions.options';

    protected $fillable = [
        'slug',
        'name',
        'sort_order',
        'is_active',
        'default_start_time',
        'default_end_time',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }

    /**
     * @return array<string, string>
     */
    public static function activeOptions(): array
    {
        return self::optionsFromDatabase(activeOnly: true);
    }

    /**
     * @return array<string, string>
     */
    public static function allOptions(): array
    {
        return self::optionsFromDatabase(activeOnly: false);
    }

    public static function label(?string $slug): string
    {
        if (blank($slug)) {
            return '';
        }

        return self::allOptions()[$slug] ?? ucfirst(str_replace('_', ' ', $slug));
    }

    public static function timeRangeFor(?string $slug): string
    {
        if (! is_string($slug) || $slug === '' || ! Schema::hasTable('restaurant_functions')) {
            return ShiftDefaultTimes::format(null, null, '10:00', '16:00');
        }

        $function = static::query()->where('slug', $slug)->first();

        if (! $function) {
            return ShiftDefaultTimes::format(null, null, '10:00', '16:00');
        }

        return ShiftDefaultTimes::format(
            $function->default_start_time,
            $function->default_end_time,
            '10:00',
            null,
        );
    }

    public function timeRangeLabel(): string
    {
        return ShiftDefaultTimes::format(
            $this->default_start_time,
            $this->default_end_time,
            '10:00',
            null,
        );
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY.'.active');
        Cache::forget(self::CACHE_KEY.'.all');
    }

    /**
     * @return array<string, string>
     */
    private static function optionsFromDatabase(bool $activeOnly): array
    {
        if (! Schema::hasTable('restaurant_functions')) {
            return self::fallbackOptions();
        }

        $options = static::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->all();

        return $options !== [] ? $options : self::fallbackOptions();
    }

    /**
     * @return array<string, string>
     */
    private static function fallbackOptions(): array
    {
        return [
            'kock' => 'Kock',
            'kallskank' => 'Kallskänk',
            'kassa' => 'Kassa',
            'disk' => 'Disk',
            'glassbar' => 'Glassbar',
            'servering' => 'Servering',
        ];
    }
}
