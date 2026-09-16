<?php

namespace App\Models;

use App\Support\AudioChannelSides;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loudspeaker extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'device_id',
        'side',
        'sound_id',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AudioDevice::class, 'device_id');
    }

    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }

    public function isPlaying(): bool
    {
        return $this->status;
    }

    public function sideLabel(): string
    {
        return AudioChannelSides::label($this->side);
    }
}
