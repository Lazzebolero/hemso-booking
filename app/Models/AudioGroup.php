<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AudioGroup extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function devices(): HasMany
    {
        return $this->hasMany(AudioDevice::class, 'audio_group_id');
    }

    public function loudspeakers(): HasManyThrough
    {
        return $this->hasManyThrough(
            Loudspeaker::class,
            AudioDevice::class,
            'audio_group_id',
            'device_id',
            'id',
            'id',
        );
    }

    public function playingChannelsCount(): int
    {
        return $this->loudspeakers()->where('status', true)->count();
    }
}
