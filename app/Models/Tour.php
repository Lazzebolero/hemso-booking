<?php

namespace App\Models;

use App\Services\TourBookingSequenceService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tour extends Model
{
    protected $fillable = [
        'title',
        'tour_type_id',
        'description',
        'tour_date',
        'start_time',
        'end_time',
        'original_start_time',
        'original_end_time',
        'max_participants',
        'guide_id',
        'status',
        'default_includes_meal',
        'exclude_from_booking_sequence',
        'exclude_from_schedule_statistics',
        'closed_for_bookings',
        'created_by',
        'updated_by',
        'started_at',
        'ended_at',
        'auto_completed_at',
        'baseline_end_time',
        'booked_total_at_start',
        'actual_total_at_start',
        'headcount_adjusted_at',
        'headcount_adjusted_by',
        'ferry_adjusted_at',
    ];

    protected $casts = [
        'tour_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'auto_completed_at' => 'datetime',
        'headcount_adjusted_at' => 'datetime',
        'ferry_adjusted_at' => 'datetime',
        'default_includes_meal' => 'boolean',
        'exclude_from_booking_sequence' => 'boolean',
        'exclude_from_schedule_statistics' => 'boolean',
        'closed_for_bookings' => 'boolean',
    ];

    public function bookingPage()
    {
        return $this->hasOne(TourBookingPage::class);
    }

    public function guide()
    {
        return $this->belongsTo(User::class, 'guide_id');
    }

    public function coGuides()
    {
        return $this->belongsToMany(User::class, 'tour_guide')
            ->withPivot(['role', 'notes'])
            ->withTimestamps();
    }

    public function assistantGuides()
    {
        return $this->coGuides()->wherePivot('role', 'assistant');
    }

    public function traineeGuides()
    {
        return $this->coGuides()->wherePivot('role', 'trainee');
    }

    public function tourType()
    {
        return $this->belongsTo(TourType::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(TourPhoto::class)->latest();
    }

    public function getLanguageCodesAttribute(): array
    {
        return $this->bookings()
            ->with('languages')
            ->get()
            ->flatMap(fn ($booking) => $booking->languages->pluck('code'))
            ->unique()
            ->values()
            ->all();
    }

    public function getLanguageNamesAttribute(): array
    {
        return $this->bookings()
            ->with('languages')
            ->get()
            ->flatMap(fn ($booking) => $booking->languages->pluck('name'))
            ->unique()
            ->values()
            ->all();
    }

    public function formattedActualStartedAt(): ?string
    {
        return $this->started_at?->format('Y-m-d H:i');
    }

    public function formattedActualEndedAt(): ?string
    {
        return $this->ended_at?->format('Y-m-d H:i');
    }

    public function actualDurationLabel(): ?string
    {
        if ($this->started_at === null || $this->ended_at === null) {
            return null;
        }

        $minutes = (int) $this->started_at->diffInMinutes($this->ended_at);

        if ($minutes <= 0) {
            return '0 min';
        }

        $hours = intdiv($minutes, 60);
        $restMinutes = $minutes % 60;

        if ($hours > 0 && $restMinutes > 0) {
            return "{$hours} h {$restMinutes} min";
        }

        if ($hours > 0) {
            return "{$hours} h";
        }

        return "{$restMinutes} min";
    }

    public function wasAutoCompleted(): bool
    {
        return $this->auto_completed_at !== null;
    }

    public function isFerryAdjusted(): bool
    {
        return $this->ferry_adjusted_at !== null && $this->original_start_time !== null;
    }

    public function displayTime(?string $time): string
    {
        if ($time === null || $time === '') {
            return '-';
        }

        return substr($time, 0, 5);
    }

    public function completionStatusLabel(): string
    {
        if ($this->status !== 'completed') {
            return match ($this->status) {
                'started' => 'Pågår',
                'planned' => 'Planerad',
                'cancelled' => 'Inställd',
                default => ucfirst((string) $this->status),
            };
        }

        return $this->wasAutoCompleted() ? 'Automatiskt avslutad' : 'Avslutad';
    }

    public function isDueToStart(?CarbonInterface $at = null): bool
    {
        if ($this->status !== 'planned' || ! $this->tour_date || ! $this->start_time) {
            return false;
        }

        $at ??= now();
        $scheduledStart = $this->tour_date->copy()->setTimeFromTimeString(substr((string) $this->start_time, 0, 8));

        return $scheduledStart->lte($at);
    }

    public function scopePlannedFromTodayOnward(Builder $query, ?CarbonInterface $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where('status', 'planned')
            ->whereDate('tour_date', '>=', $at->toDateString());
    }

    public function scopeEligibleForBookingSequence($query)
    {
        return app(TourBookingSequenceService::class)->applyEligibleScope($query);
    }

    public function isEligibleForBookingSequence(): bool
    {
        return app(TourBookingSequenceService::class)->isTourEligible($this);
    }
}
