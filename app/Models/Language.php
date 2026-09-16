<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_language');
    }

    public function guides()
    {
        return $this->belongsToMany(User::class, 'guide_language')
            ->orderBy('name');
    }

    public static function defaultId(): ?int
    {
        $id = static::query()->where('is_default', true)->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        $id = static::query()->where('code', 'sv')->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        $id = static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * @return list<int>
     */
    public static function defaultIds(): array
    {
        $id = self::defaultId();

        return $id !== null ? [$id] : [];
    }
}
