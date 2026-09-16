<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionPresenceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'production_person_id',
        'direction',
        'occurred_at',
        'with_group',
        'recorded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'with_group' => 'boolean',
        ];
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(ProductionPerson::class, 'production_person_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isIn(): bool
    {
        return $this->direction === ProductionPerson::DIRECTION_IN;
    }

    public function directionLabel(): string
    {
        return $this->isIn() ? 'In' : 'Ut';
    }

    public function personName(): string
    {
        return $this->person?->name ?? 'Borttagen person';
    }

    public function recordedByName(): ?string
    {
        if ($this->recorder === null) {
            return null;
        }

        if ((int) $this->recorded_by === (int) $this->person?->user_id) {
            return null;
        }

        return $this->recorder->name;
    }
}
