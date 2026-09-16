<?php

namespace App\Models;

use Database\Factories\PostalCodeEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostalCodeEntry extends Model
{
    /** @use HasFactory<PostalCodeEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'postal_code_collection_day_id',
        'postal_code',
        'locality',
        'municipality_name',
        'county_code',
        'county_name',
        'lookup_matched',
    ];

    protected function casts(): array
    {
        return [
            'lookup_matched' => 'boolean',
        ];
    }

    public function collectionDay(): BelongsTo
    {
        return $this->belongsTo(PostalCodeCollectionDay::class, 'postal_code_collection_day_id');
    }
}
