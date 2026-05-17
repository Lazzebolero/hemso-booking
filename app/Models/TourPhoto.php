<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourPhoto extends Model
{
    protected $fillable = [
        'tour_id',
        'uploaded_by',
        'image_path',
        'original_name',
        'caption',
        'mime_type',
        'size',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'size' => 'integer',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
