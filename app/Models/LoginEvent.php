<?php

namespace App\Models;

use Database\Factories\LoginEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginEvent extends Model
{
    /** @use HasFactory<LoginEventFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'event_type',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
