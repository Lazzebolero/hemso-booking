<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SystemMessage extends Model
{
    protected $fillable = [
        'title',
        'message_type',
        'body',
        'target_roles',
        'is_important',
        'priority',
        'popup_only',
        'requires_ack',
        'send_email',
        'remind_every_minutes',
        'last_reminder_at',
        'next_reminder_at',
        'starts_at',
        'ends_at',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'is_important' => 'boolean',
        'popup_only' => 'boolean',
        'requires_ack' => 'boolean',
        'send_email' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'next_reminder_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'system_message_user')
            ->withPivot(['read_at', 'dismissed_at', 'acknowledged_at'])
            ->withTimestamps();
    }

    public function scopeVisibleNow(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeForRole(Builder $query, ?string $role): Builder
    {
        if (!$role) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($role) {
            $q->whereNull('target_roles')
                ->orWhereJsonContains('target_roles', 'all')
                ->orWhereJsonContains('target_roles', $role);
        });
    }

    public function scopeNotDismissedForUser(Builder $query, int $userId): Builder
    {
        return $query->whereDoesntHave('users', function (Builder $q) use ($userId) {
            $q->where('users.id', $userId)
                ->whereNotNull('system_message_user.dismissed_at');
        });
    }

    public function getTargetRolesLabelAttribute(): string
    {
        $roles = $this->target_roles ?? [];

        if (empty($roles) || in_array('all', $roles, true)) {
            return 'Alla';
        }

        $map = [
            'admin' => 'Admins',
            'host' => 'Värdar',
            'guide' => 'Guider',
        ];

        return collect($roles)
            ->map(fn ($role) => $map[$role] ?? $role)
            ->implode(', ');
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ((int) $this->priority) {
            1 => 'Låg',
            3 => 'Hög',
            default => 'Normal',
        };
    }

    public function getMessageTypeLabelAttribute(): string
    {
        return match ($this->message_type) {
            'alert' => 'Driftlarm',
            default => 'Meddelande',
        };
    }
}