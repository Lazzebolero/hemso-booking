<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'title','description','category','priority','location','status','reported_by','assigned_to','internal_comment','resolved_at',
    ];

    protected $casts = ['resolved_at' => 'datetime'];

    public function reporter() { return $this->belongsTo(User::class, 'reported_by'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
}
