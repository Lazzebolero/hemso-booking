<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuideShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'guide_id','tour_id','shift_type','title','shift_date','start_time','end_time','notes','created_by','updated_by',
    ];

    protected $casts = ['shift_date' => 'date'];

    public function guide() { return $this->belongsTo(User::class, 'guide_id'); }
    public function tour() { return $this->belongsTo(Tour::class); }
}
