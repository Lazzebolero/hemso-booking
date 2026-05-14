<?php

namespace App\Models;

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
        ];
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
