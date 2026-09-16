<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TourPhoto extends Model
{
    protected $fillable = [
        'tour_id',
        'uploaded_by',
        'path',
        'image_path',
        'original_name',
        'mime_type',
        'size',
        'caption',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path ?: $this->image_path);
    }
}
