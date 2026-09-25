<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionDepartureLog extends Model
{
    public const ACTION_DEPARTED = 'departed';

    public const ACTION_RESTORED = 'restored';

    protected $fillable = [
        'production_id',
        'production_person_id',
        'action',
        'occurred_at',
        'recorded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
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

    public function actionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_DEPARTED => 'Åkt ut',
            self::ACTION_RESTORED => 'Återställd',
            default => $this->action,
        };
    }

    public function personName(): string
    {
        return $this->person?->name ?? 'Borttagen person';
    }

    public function recordedByName(): string
    {
        return $this->recorder?->name ?? 'Loggades inte';
    }
}
