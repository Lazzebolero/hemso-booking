<?php

namespace App\Models;

use App\Support\Roles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'is_kiosk',
        'kiosk_target',
        'facility_reports_acknowledged_at',
    ];

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_kiosk' => 'boolean',
        'password' => 'hashed',
        'facility_reports_acknowledged_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Role relations & helpers
    |--------------------------------------------------------------------------
    */

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string $slug): bool
    {
        if (! $this->relationLoaded('roles')) {
            $this->loadMissing('roles');
        }

        return $this->roles->contains('slug', $slug);
    }

    public function hasAnyRole(array $slugs): bool
    {
        if (! $this->relationLoaded('roles')) {
            $this->loadMissing('roles');
        }

        return $this->roles->whereIn('slug', $slugs)->isNotEmpty();
    }

    public function hasAllRoles(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if (! $this->hasRole($slug)) {
                return false;
            }
        }

        return true;
    }

    public function availableRoleSlugs(): array
    {
        if (! $this->relationLoaded('roles')) {
            $this->loadMissing('roles');
        }

        return $this->roles
            ->pluck('slug')
            ->values()
            ->all();
    }

    public function activeRole(): ?string
    {
        return session('active_role');
    }

    public function hasActiveRole(string $slug): bool
    {
        return $this->activeRole() === $slug;
    }

    public function canActivateRole(string $slug): bool
    {
        return $this->hasRole($slug);
    }

    public function assignRoles(array $roles): self
    {
        $ids = collect($roles)->pluck('id')->all();

        $this->roles()->sync($ids);

        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Roles::ADMIN);
    }

    public function isHost(): bool
    {
        return $this->hasRole(Roles::HOST);
    }

    public function isGuide(): bool
    {
        return $this->hasRole(Roles::GUIDE);
    }

    public function isRestaurant(): bool
    {
        return $this->hasRole(Roles::RESTAURANT);
    }

    public function isActiveAdmin(): bool
    {
        return $this->hasActiveRole(Roles::ADMIN);
    }

    public function isActiveHost(): bool
    {
        return $this->hasActiveRole(Roles::HOST);
    }

    public function isActiveGuide(): bool
    {
        return $this->hasActiveRole(Roles::GUIDE);
    }

    public function isActiveRestaurant(): bool
    {
        return $this->hasActiveRole(Roles::RESTAURANT);
    }

    public function workShifts(): HasMany
    {
        return $this->hasMany(WorkShift::class);
    }

    public function workingShiftFor(string $role, $date): ?WorkShift
    {
        return $this->workShifts()
            ->whereDate('shift_date', $date)
            ->where('shift_role', $role)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Domain relations
    |--------------------------------------------------------------------------
    */

    public function guidedTours(): HasMany
    {
        return $this->hasMany(Tour::class, 'guide_id');
    }

    public function createdTours(): HasMany
    {
        return $this->hasMany(Tour::class, 'created_by');
    }

    public function guideShifts(): HasMany
    {
        return $this->hasMany(GuideShift::class, 'guide_id');
    }

    public function reportedFacilityReports(): HasMany
    {
        return $this->hasMany(FacilityReport::class, 'reported_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Messaging
    |--------------------------------------------------------------------------
    */

    public function conversationParticipants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['last_read_at', 'is_muted'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
