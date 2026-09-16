<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class FacilityReport extends Model
{
    protected $fillable = [
        'title',
        'description',
        'attachment_path',
        'category_id',
        'priority_id',
        'status_id',
        'location_id',
        'location_text',
        'reported_by',
        'assigned_to',
    ];

    public function category()
    {
        return $this->belongsTo(ReportCategory::class, 'category_id');
    }

    public function priority()
    {
        return $this->belongsTo(ReportPriority::class, 'priority_id');
    }

    public function statusRelation()
    {
        return $this->belongsTo(ReportStatus::class, 'status_id');
    }

    public function location()
    {
        return $this->belongsTo(ReportLocation::class, 'location_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FacilityReportAttachment::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return Collection<int, FacilityReportAttachment>
     */
    public function resolvedAttachments(): Collection
    {
        $this->loadMissing('attachments');

        if ($this->attachments->isNotEmpty()) {
            return $this->attachments;
        }

        if (empty($this->attachment_path)) {
            return collect();
        }

        return collect([
            new FacilityReportAttachment([
                'facility_report_id' => $this->id,
                'path' => $this->attachment_path,
                'original_name' => null,
                'sort_order' => 0,
            ]),
        ]);
    }

    /**
     * Public URL for an uploaded guide photo (disk public + storage link).
     */
    public function attachmentPublicUrl(): ?string
    {
        $first = $this->resolvedAttachments()->first();

        if ($first === null || empty($first->path)) {
            return null;
        }

        return Storage::disk('public')->url($first->path);
    }
}
