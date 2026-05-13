<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    /**
     * Public URL for an uploaded guide photo (disk public + storage link).
     */
    public function attachmentPublicUrl(): ?string
    {
        if (empty($this->attachment_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }
}