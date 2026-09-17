<?php

namespace App\Models;

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
