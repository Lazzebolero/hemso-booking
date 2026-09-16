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
        'country_id',
        'men_count',
        'women_count',
        'youth_count',
        'child_count',
        'unspecified_count',
        'total_count',
        'notes',
        'status',
        'is_waitlist',
        'is_walk_in',
        'includes_meal',
        'to_be_invoiced',
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
        'includes_meal' => 'boolean',
        'to_be_invoiced' => 'boolean',
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

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function notificationLogs()
    {
        return $this->morphMany(NotificationLog::class, 'notifiable');
    }

    public function mealLabel(): string
    {
        return $this->includes_meal ? 'Med mat' : 'Ej mat';
    }

    public function invoiceLabel(): string
    {
        return $this->to_be_invoiced ? 'Faktureras' : '';
    }
}
