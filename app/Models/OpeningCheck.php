<?php

namespace App\Models;

use App\Support\OpeningCheckpoints;
use Carbon\CarbonInterface;
use Database\Factories\OpeningCheckFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class OpeningCheck extends Model
{
    /** @use HasFactory<OpeningCheckFactory> */
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'check_date',
        'opened_by',
        'started_at',
        'completed_at',
        'visitor_opens_at',
        'confirmed',
        'status',
        'items',
    ];

    protected function casts(): array
    {
        return [
            'check_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'confirmed' => 'boolean',
            'items' => 'array',
        ];
    }

    public function scopeForDate(Builder $query, CarbonInterface|string $date): Builder
    {
        $day = Carbon::parse($date)->toDateString();
        $nextDay = Carbon::parse($day)->addDay()->toDateString();

        return $query
            ->where('check_date', '>=', $day)
            ->where('check_date', '<', $nextDay);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function deviations(): HasMany
    {
        return $this->hasMany(OpeningDeviation::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function outcomeFor(string $key): ?string
    {
        $value = $this->items[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function hasOpenDeviations(): bool
    {
        if ($this->relationLoaded('deviations')) {
            return $this->deviations->contains(
                fn (OpeningDeviation $deviation): bool => $deviation->isOpen()
            );
        }

        return $this->deviations()->open()->exists();
    }

    public function visitorOpensAtInput(): ?string
    {
        if ($this->visitor_opens_at === null || $this->visitor_opens_at === '') {
            return null;
        }

        return substr((string) $this->visitor_opens_at, 0, 5);
    }

    public function statusLabel(): string
    {
        if ($this->isCompleted()) {
            return $this->hasOpenDeviations() ? 'Klar, med öppen avvikelse' : 'Klar';
        }

        return 'Pågår';
    }

    /**
     * @return list<string>
     */
    public function unansweredCheckpointKeys(): array
    {
        return collect(OpeningCheckpoints::keys())
            ->reject(fn (string $key): bool => in_array($this->outcomeFor($key), OpeningCheckpoints::outcomes(), true))
            ->values()
            ->all();
    }
}
