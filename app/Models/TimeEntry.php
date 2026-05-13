<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeEntry extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CORRECTED = 'corrected';

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at_original',
        'clock_out_at_original',
        'start_at',
        'end_at',
        'break_minutes',
        'status',
        'user_comment',
        'admin_comment',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in_at_original' => 'datetime',
        'clock_out_at_original' => 'datetime',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'break_minutes' => 'integer',
    ];

    protected $appends = [
        'worked_minutes',
        'worked_hours_formatted',
        'status_label',
        'status_badge_class',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(TimeEntryAudit::class)->latest();
    }

    public function getWorkedMinutesAttribute(): int
    {
        if (! $this->start_at || ! $this->end_at) {
            return 0;
        }

        $minutes = $this->start_at->diffInMinutes($this->end_at, false);
        $minutes -= (int) ($this->break_minutes ?? 0);

        return max(0, $minutes);
    }

    public function getWorkedHoursFormattedAttribute(): string
    {
        $minutes = $this->worked_minutes;
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%dh %02dm', $hours, $remainingMinutes);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Öppet',
            self::STATUS_DRAFT => 'Utkast',
            self::STATUS_SUBMITTED => 'Inskickad',
            self::STATUS_APPROVED => 'Godkänd',
            self::STATUS_CORRECTED => 'Korrigerad',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'text-bg-warning',
            self::STATUS_DRAFT => 'text-bg-info',
            self::STATUS_SUBMITTED => 'text-bg-primary',
            self::STATUS_APPROVED => 'text-bg-success',
            self::STATUS_CORRECTED => 'text-bg-secondary',
            default => 'text-bg-secondary',
        };
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function isEditableByUser(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_DRAFT], true);
    }

    public static function currentOpenForUser(int $userId): ?self
    {
        return self::forUser($userId)
            ->open()
            ->latest('clock_in_at_original')
            ->first();
    }

    public static function openEntriesForUser(int $userId)
    {
        return self::forUser($userId)
            ->open()
            ->orderBy('clock_in_at_original')
            ->get();
    }

    public static function filterPeriod(string $filter): array
    {
        $now = now();

        return match ($filter) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            default => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()],
        };
    }
}
