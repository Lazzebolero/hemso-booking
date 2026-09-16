<?php

namespace App\Models;

use App\Support\OpeningCheckpoints;
use Database\Factories\OpeningDeviationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningDeviation extends Model
{
    /** @use HasFactory<OpeningDeviationFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'opening_check_id',
        'checkpoint_key',
        'occurred_at',
        'location',
        'description',
        'immediate_action',
        'informed_person',
        'decision_before_opening',
        'status',
        'reported_by',
        'resolved_by',
        'resolved_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function openingCheck(): BelongsTo
    {
        return $this->belongsTo(OpeningCheck::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function statusLabel(): string
    {
        return $this->isResolved() ? 'Åtgärdad' : 'Öppen';
    }

    public function checkpointLabel(): string
    {
        if (! is_string($this->checkpoint_key) || $this->checkpoint_key === '') {
            return 'Övrig avvikelse';
        }

        return OpeningCheckpoints::labels()[$this->checkpoint_key] ?? $this->checkpoint_key;
    }
}
