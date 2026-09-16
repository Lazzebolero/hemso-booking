<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AudioDevice extends Model
{
    public $incrementing = false;

    protected $keyType = 'int';

    /** @var list<string> */
    protected $fillable = [
        'id',
        'audio_group_id',
        'name',
        'location',
        'hostname',
        'notes',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AudioGroup::class, 'audio_group_id');
    }

    public function loudspeakers(): HasMany
    {
        return $this->hasMany(Loudspeaker::class, 'device_id');
    }

    public function displayLabel(): string
    {
        return sprintf('#%d %s', $this->id, $this->name);
    }
}
