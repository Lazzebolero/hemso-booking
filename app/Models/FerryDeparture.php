<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FerryDeparture extends Model
{
    /** @use HasFactory<\Database\Factories\FerryDepartureFactory> */
    use HasFactory;

    protected $fillable = [
        'direction',
        'day_type',
        'departure_time',
        'requires_call',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_call' => 'boolean',
        ];
    }
}
