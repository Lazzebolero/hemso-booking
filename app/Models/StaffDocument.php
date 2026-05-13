<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDocument extends Model
{
    protected $fillable = [
        'title',
        'description',
        'file_path',
        'original_name',
        'mime_type',
        'audience_scope',
        'role_slug',
        'shift_function',
        'is_active',
        'sort_order',
        'uploaded_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $roleSlugs = method_exists($user, 'roles')
            ? $user->roles->pluck('slug')->filter()->values()->all()
            : [];

        return $query->where(function ($q) use ($roleSlugs, $user) {
            $q->where('audience_scope', 'all');

            if (!empty($roleSlugs)) {
                $q->orWhere(function ($roleQuery) use ($roleSlugs) {
                    $roleQuery->where('audience_scope', 'role')
                        ->whereIn('role_slug', $roleSlugs);
                });
            }

            $currentFunction = $this->resolveUserShiftFunction($user);

            if ($currentFunction) {
                $q->orWhere(function ($functionQuery) use ($currentFunction) {
                    $functionQuery->where('audience_scope', 'function')
                        ->where('shift_function', $currentFunction);
                });
            }
        });
    }

    public static function audienceScopes(): array
    {
        return [
            'all' => 'Alla',
            'role' => 'Roll',
            'function' => 'Funktion',
        ];
    }

    private function resolveUserShiftFunction(User $user): ?string
    {
        $todayShift = WorkShift::query()
            ->where('user_id', $user->id)
            ->whereDate('shift_date', now()->toDateString())
            ->whereNotNull('shift_function')
            ->orderBy('start_time')
            ->first();

        return $todayShift?->shift_function;
    }
}