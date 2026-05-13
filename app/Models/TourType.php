<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourType extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
        'is_default',
		'default_duration_minutes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
		'default_duration_minutes' => 'integer',
		
    ];
}
