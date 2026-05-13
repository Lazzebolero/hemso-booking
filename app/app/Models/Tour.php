<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    use HasFactory;

    protected $fillable = [
        'title','description','tour_date','start_time','end_time','max_participants','guide_id','status','started_at','ended_at','created_by','updated_by',
    ];

    protected $casts = [
        'tour_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function guide() { return $this->belongsTo(User::class, 'guide_id'); }
    public function bookings() { return $this->hasMany(Booking::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function getBookedCountAttribute(): int
    {
        return (int) $this->bookings()->whereNotIn('status', ['cancelled'])->sum('total_count');
    }
}
