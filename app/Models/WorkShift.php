<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkShift extends Model
{
    protected $fillable = [
        'user_id',
        'shift_date',
        'start_time',
        'end_time',
        'shift_role',
        'shift_function',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'shift_date' => 'date',
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

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->whereDate('shift_date', $date);
    }

    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->where('shift_role', $role);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['cancelled']);
    }

    public static function restaurantFunctions(): array
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