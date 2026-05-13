<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function guidedTours() { return $this->hasMany(Tour::class, 'guide_id'); }
    public function createdTours() { return $this->hasMany(Tour::class, 'created_by'); }
    public function guideShifts() { return $this->hasMany(GuideShift::class, 'guide_id'); }
    public function reportedFacilityReports() { return $this->hasMany(FacilityReport::class, 'reported_by'); }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isHost(): bool { return $this->role === 'host'; }
    public function isGuide(): bool { return $this->role === 'guide'; }
}
