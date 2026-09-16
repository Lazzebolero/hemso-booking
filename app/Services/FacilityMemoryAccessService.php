<?php

namespace App\Services;

use App\Models\FacilityMemory;
use App\Models\User;
use App\Support\ActiveRole;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;

class FacilityMemoryAccessService
{
    public function canViewArchive(): bool
    {
        return ActiveRole::slug() === Roles::ADMIN;
    }

    public function canCollectMemories(): bool
    {
        return in_array(ActiveRole::slug(), [Roles::HOST, Roles::GUIDE], true);
    }

    public function canAdministrateMemories(): bool
    {
        return ActiveRole::slug() === Roles::ADMIN;
    }

    public function canDeleteMemories(): bool
    {
        return ActiveRole::slug() === Roles::ADMIN;
    }

    public function canViewMemory(FacilityMemory $memory, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if ($this->canViewArchive()) {
            return true;
        }

        return (int) $memory->collected_by === (int) $user->id;
    }

    /**
     * @param  Builder<FacilityMemory>  $query
     * @return Builder<FacilityMemory>
     */
    public function scopeArchiveIndex(Builder $query): Builder
    {
        return $query;
    }

    /**
     * @param  Builder<FacilityMemory>  $query
     * @return Builder<FacilityMemory>
     */
    public function scopeOwnMemories(Builder $query, User $user): Builder
    {
        return $query->where('collected_by', $user->id);
    }

    public function abortUnlessCanView(FacilityMemory $memory): void
    {
        abort_unless($this->canViewMemory($memory), 403);
    }

    public function abortUnlessCanAdministrate(): void
    {
        abort_unless($this->canAdministrateMemories(), 403);
    }

    public function abortUnlessCanDelete(): void
    {
        abort_unless($this->canDeleteMemories(), 403);
    }

    public function abortUnlessCanCollect(): void
    {
        abort_unless($this->canCollectMemories(), 403);
    }
}
