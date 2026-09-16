<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityMemory extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';

    public const TYPE_AUDIO = 'audio';

    public const CONSENT_WRITTEN = 'written';

    public const CONSENT_RECORDED = 'recorded';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_READ = 'read';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_FLAGGED = 'flagged';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'type',
        'body',
        'audio_path',
        'audio_duration_seconds',
        'audio_mime_type',
        'audio_size',
        'context_note',
        'location_text',
        'era_text',
        'visitor_name',
        'consent_type',
        'consent_given',
        'tour_id',
        'collected_by',
        'status',
        'admin_notes',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'consent_given' => 'boolean',
        'audio_duration_seconds' => 'integer',
        'audio_size' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isText(): bool
    {
        return $this->type === self::TYPE_TEXT;
    }

    public function isAudio(): bool
    {
        return $this->type === self::TYPE_AUDIO;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_AUDIO => 'Ljud',
            default => 'Text',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_READ => 'Läst',
            self::STATUS_ARCHIVED => 'Arkiverad',
            self::STATUS_FLAGGED => 'Flaggad',
            self::STATUS_REJECTED => 'Avvisad',
            default => 'Ny',
        };
    }

    public function consentTypeLabel(): string
    {
        return match ($this->consent_type) {
            self::CONSENT_RECORDED => 'Inspelning',
            default => 'Skriftligt minne',
        };
    }

    public function summaryText(int $length = 160): string
    {
        if ($this->isText()) {
            return str($this->body ?? '')->limit($length)->toString();
        }

        return str($this->context_note ?? 'Ljudinspelning utan kontexttext')->limit($length)->toString();
    }

    public function formattedAudioDuration(): ?string
    {
        if ($this->audio_duration_seconds === null) {
            return null;
        }

        $minutes = intdiv($this->audio_duration_seconds, 60);
        $seconds = $this->audio_duration_seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Ny',
            self::STATUS_READ => 'Läst',
            self::STATUS_ARCHIVED => 'Arkiverad',
            self::STATUS_FLAGGED => 'Flaggad',
            self::STATUS_REJECTED => 'Avvisad',
        ];
    }
}
