<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_id',
        'booking_name',
        'contact_name',
        'phone',
        'email',
        'men_count',
        'women_count',
        'youth_count',
        'child_count',
        'total_count',
        'notes',
        'status',
        'is_waitlist',
        'is_walk_in',
        'arrival_status',
        'checked_in_at',
        'reminder_sent_at',
        'moved_from_tour_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_waitlist' => 'boolean',
        'is_walk_in' => 'boolean',
        'checked_in_at' => 'datetime',
		'reminder_sent_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    public function languages()
    {
        return $this->belongsToMany(Language::class, 'booking_language');
    }
	public function notificationLogs()
{
    return $this->morphMany(\App\Models\NotificationLog::class, 'notifiable');
}
}