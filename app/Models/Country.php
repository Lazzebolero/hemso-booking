<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'is_quick_pick',
        'is_proposed',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_quick_pick' => 'boolean',
        'is_proposed' => 'boolean',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function flagUrl(): string
    {
        $code = strtolower($this->code);
        $svgPath = public_path("images/flags/{$code}.svg");

        if (is_file($svgPath)) {
            return asset("images/flags/{$code}.svg");
        }

        return asset('images/flags/xx.svg');
    }

    public function hasFlagAsset(): bool
    {
        return is_file(public_path('images/flags/'.strtolower($this->code).'.svg'));
    }

    /**
     * @param  Builder<Country>  $query
     * @return Builder<Country>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Country>  $query
     * @return Builder<Country>
     */
    public function scopeQuickPick(Builder $query): Builder
    {
        return $query
            ->where('is_quick_pick', true)
            ->where('is_proposed', false);
    }
}
