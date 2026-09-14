<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Throwable;

class ActivityLogger
{
    public function record(
        string $action,
        ?User $actor = null,
        ?User $target = null,
        ?string $description = null,
        array $metadata = [],
        ?Request $request = null
    ): ?ActivityLog {
        try {
            $request ??= request();

            return ActivityLog::create([
                'actor_user_id' => $actor?->id,
                'target_user_id' => $target?->id,
                'action' => $action,
                'description' => $description,
                'metadata' => $metadata ?: null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
