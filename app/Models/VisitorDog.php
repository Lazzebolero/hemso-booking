<?php

namespace App\Models;

use App\Support\VisitorDogCareFlags;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorDog extends Model
{
    use HasFactory;

    protected $fillable = [
        'dog_name',
        'breed',
        'owner_phone',
        'visit_date',
        'tour_start_time',
        'care_flags',
        'photo_path',
        'registered_by',
        'registered_as_role',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'care_flags' => 'array',
        ];
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function needsPhoto(): bool
    {
        return blank($this->photo_path);
    }

    public function hasCareFlag(string $key): bool
    {
        return (bool) (($this->care_flags ?? [])[$key] ?? false);
    }

    public function hasSpecialCareNeeds(): bool
    {
        return VisitorDogCareFlags::hasAny($this->care_flags);
    }
}
