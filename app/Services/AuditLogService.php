<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    public static function log(
        string $action,
        ?Model $model = null,
        array $old = [],
        array $new = [],
        ?Model $actor = null,
        ?int $targetUserId = null,
    ): void {
        $actor ??= auth()?->user();

        $data = [
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'auditable_type' => $model?->getMorphClass(),
            'auditable_id' => $model?->getKey(),
            'old_values' => $old === [] ? null : json_encode($old, JSON_THROW_ON_ERROR),
            'new_values' => $new === [] ? null : json_encode($new, JSON_THROW_ON_ERROR),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'target_user_id' => $targetUserId,
            'performed_by_role' => self::resolveRole($actor),
            'created_at' => now(),
        ];

        try {
            DB::table('audit_logs')->insert($data);
        } catch (\Throwable $e) {
            Log::error('Failed to write audit log', [
                'action' => $action,
                'error' => $e->getMessage(),
                'data' => array_filter($data, fn ($v) => ! is_string($v) || strlen($v) < 1000),
            ]);
        }
    }

    protected static function resolveRole(?Model $actor): ?string
    {
        if (! $actor || ! method_exists($actor, 'getRoleNames')) {
            return null;
        }

        $roles = $actor->getRoleNames();

        return $roles->isNotEmpty() ? $roles->first() : null;
    }
}
