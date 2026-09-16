<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DailyCountryLog extends Model
{
    protected $fillable = [
        'log_date',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'log_date' => 'date',
    ];

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'daily_country_log_country')
            ->orderBy('name');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
