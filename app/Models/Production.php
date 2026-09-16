<?php

namespace App\Models;

use App\Support\ProductionSites;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Production extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sites',
        'starts_on',
        'ends_on',
        'is_active',
        'company',
        'client',
        'client_contact',
        'client_phone',
        'client_email',
        'notes',
        'created_by',
    ];

    /**
     * Internal Hemsö details — never serialize to production-team views or JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'company',
        'client',
        'client_contact',
        'client_phone',
        'client_email',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
            'sites' => 'array',
        ];
    }

    public function people(): HasMany
    {
        return $this->hasMany(ProductionPerson::class);
    }

    public function presenceLogs(): HasMany
    {
        return $this->hasMany(ProductionPresenceLog::class);
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(ProductionPhoneNumber::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return list<string>
     */
    public function siteKeys(): array
    {
        return array_values(array_filter(
            $this->sites ?? [],
            fn ($key) => is_string($key) && $key !== '',
        ));
    }

    public function siteListLabel(): string
    {
        try {
            $labels = ProductionSites::labelsFor($this->siteKeys());
        } catch (\Throwable) {
            return '';
        }

        return $labels === [] ? '' : implode(', ', $labels);
    }

    public function hasSite(string $key): bool
    {
        return in_array($key, $this->siteKeys(), true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Production>  $query
     * @return Builder<Production>
     */
    public function scopeCurrentPeriod(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->active()
            ->whereDate('starts_on', '<=', $today)
            ->whereDate('ends_on', '>=', $today);
    }

    public static function current(): ?self
    {
        return static::query()
            ->currentPeriod()
            ->orderByDesc('id')
            ->first();
    }
}
