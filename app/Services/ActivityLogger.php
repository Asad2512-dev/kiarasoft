<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Central audit trail writer. Strips anything that looks like a secret.
 */
class ActivityLogger
{
    private const REDACT = ['password', 'password_confirmation', 'current_password', 'remember_token', 'token', '_token'];

    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
        ?int $shopId = null,
        ?int $branchId = null,
    ): ActivityLog {
        $actor ??= auth()->user();
        $request = app()->runningInConsole() ? null : request();

        return ActivityLog::create([
            'shop_id' => $shopId ?? $subject?->getAttribute('shop_id') ?? $actor?->shop_id,
            'branch_id' => $branchId ?? $subject?->getAttribute('branch_id'),
            'user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'description' => mb_substr($description, 0, 255),
            'properties' => $properties ? Arr::except($properties, self::REDACT) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }

    /** Returns only changed, non-secret attributes of a model as old/new pairs. */
    public static function diff(Model $model): array
    {
        $changes = [];
        foreach (Arr::except($model->getChanges(), array_merge(self::REDACT, ['updated_at'])) as $key => $new) {
            $changes[$key] = ['old' => $model->getOriginal($key) instanceof \BackedEnum ? $model->getOriginal($key)->value : $model->getOriginal($key), 'new' => $new];
        }

        return $changes;
    }
}
