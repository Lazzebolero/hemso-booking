<?php

namespace App\Services;

use App\Models\ActivityLog;

class LogService
{
    public static function log(string $entityType, ?int $entityId, string $action, mixed $old = null, mixed $new = null, ?string $description = null): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
            'description' => $description,
        ]);
    }
}
