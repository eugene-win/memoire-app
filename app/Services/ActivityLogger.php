<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public function record(
        ?User $actor,
        string $action,
        ?Model $subject = null,
        array $metadata = [],
        string $result = 'success'
    ): void {
        ActivityLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'result' => $result,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
