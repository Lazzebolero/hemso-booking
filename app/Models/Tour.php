<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    protected $fillable = [
        'title',
        'tour_type_id',
        'description',
        'tour_date',
        'start_time',
        'end_time',
        'max_participants',
        'guide_id',
        'status',
        'created_by',
        'updated_by',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'tour_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
	public function bookingPage()
{
    return $this->hasOne(\App\Models\TourBookingPage::class);
}
    public function guide()
    {
        return $this->belongsTo(User::class, 'guide_id');
    }

    public function tourType()
    {
        return $this->belongsTo(TourType::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
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
}