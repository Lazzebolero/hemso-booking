<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class TourType extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
        'is_default',
        'include_in_booking_sequence',
        'default_duration_minutes',
        'auto_complete_enabled',
        'auto_complete_grace_minutes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'include_in_booking_sequence' => 'boolean',
        'default_duration_minutes' => 'integer',
        'auto_complete_enabled' => 'boolean',
        'auto_complete_grace_minutes' => 'integer',
    ];

    /**
     * @return Collection<int, self>
     */
    public static function activeOrdered()
    {
        $types = static::query()
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($types->isNotEmpty()) {
            return $types;
        }

        return static::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
