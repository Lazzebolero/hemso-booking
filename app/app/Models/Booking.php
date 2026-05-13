<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_id','booking_name','contact_name','phone','email','men_count','women_count','youth_count','child_count','total_count','notes','status','created_by','updated_by',
    ];

    public function tour() { return $this->belongsTo(Tour::class); }
}
