<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionPhoneNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'label',
        'phone',
        'sort_order',
    ];

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    public function telHref(): string
    {
        $normalized = preg_replace('/[^\d+]/', '', $this->phone) ?? '';

        return 'tel:'.$normalized;
    }
}
