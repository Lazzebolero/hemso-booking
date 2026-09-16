<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyGuideOrder extends Model
{
    public const SOURCE_SCHEDULE = 'schedule';

    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'guide_date',
        'user_id',
        'sort_order',
        'source',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'guide_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('guide_date', $date);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
