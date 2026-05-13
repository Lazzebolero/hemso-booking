<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportPriority extends Model
{
    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function reports()
    {
        return $this->hasMany(FacilityReport::class, 'priority_id');
    }
}