<?php

namespace App\Models;

use Database\Factories\PostalCodeCollectionDayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostalCodeCollectionDay extends Model
{
    /** @use HasFactory<PostalCodeCollectionDayFactory> */
    use HasFactory;

    protected $fillable = [
        'collection_date',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PostalCodeEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
