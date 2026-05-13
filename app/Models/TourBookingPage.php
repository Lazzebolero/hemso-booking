<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourBookingPage extends Model
{
    protected $fillable = [
        'tour_id',
        'slug',
        'page_title',
        'page_text',
        'thank_you_text',
        'full_tour_text',
        'booking_terms',
        'adult_price',
        'youth_price',
        'child_price',
        'confirmation_subject',
        'confirmation_body',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'adult_price' => 'decimal:2',
        'youth_price' => 'decimal:2',
        'child_price' => 'decimal:2',
    ];

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}