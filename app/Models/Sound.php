<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Sound extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'path',
        'file_path',
        'original_name',
        'mime_type',
        'uploaded_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function loudspeakers(): HasMany
    {
        return $this->hasMany(Loudspeaker::class);
    }

    public function deleteStoredFile(): void
    {
        if ($this->file_path !== '' && Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }
    }
}
