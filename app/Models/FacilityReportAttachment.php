<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FacilityReportAttachment extends Model
{
    protected $fillable = [
        'facility_report_id',
        'path',
        'original_name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(FacilityReport::class, 'facility_report_id');
    }

    public function existsOnDisk(): bool
    {
        return filled($this->path) && Storage::disk('public')->exists($this->path);
    }

    public function absolutePath(): ?string
    {
        if (! $this->existsOnDisk()) {
            return null;
        }

        return Storage::disk('public')->path($this->path);
    }
}
