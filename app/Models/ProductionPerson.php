<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionPerson extends Model
{
    use HasFactory;

    public const KIND_ADMIN = 'admin';

    public const KIND_STAFF = 'staff';

    public const KIND_PARTICIPANT = 'participant';

    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    protected $fillable = [
        'production_id',
        'user_id',
        'name',
        'kind',
        'is_inside',
        'departed_at',
        'departed_on',
        'last_presence_at',
        'sort_order',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_inside' => 'boolean',
            'departed_at' => 'datetime',
            'departed_on' => 'date',
            'last_presence_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function kindLabels(): array
    {
        return [
            self::KIND_ADMIN => 'Admin',
            self::KIND_STAFF => 'Personal',
            self::KIND_PARTICIPANT => 'Deltagare',
        ];
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function presenceLogs(): HasMany
    {
        return $this->hasMany(ProductionPresenceLog::class);
    }

    public function departureLogs(): HasMany
    {
        return $this->hasMany(ProductionDepartureLog::class);
    }

    public function scopeParticipants(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_PARTICIPANT);
    }

    public function scopeCrew(Builder $query): Builder
    {
        return $query->whereIn('kind', [self::KIND_ADMIN, self::KIND_STAFF]);
    }

    public function scopeRemaining(Builder $query): Builder
    {
        return $query->whereNull('departed_at');
    }

    public function scopeInside(Builder $query): Builder
    {
        return $query->where('is_inside', true);
    }

    public function isParticipant(): bool
    {
        return $this->kind === self::KIND_PARTICIPANT;
    }

    public function hasDeparted(): bool
    {
        return $this->departed_at !== null;
    }

    public function kindLabel(): string
    {
        return self::kindLabels()[$this->kind] ?? $this->kind;
    }

    public function canLogIn(): bool
    {
        return in_array($this->kind, [self::KIND_ADMIN, self::KIND_STAFF], true);
    }

    public function isInside(): bool
    {
        return (bool) $this->is_inside;
    }
}
