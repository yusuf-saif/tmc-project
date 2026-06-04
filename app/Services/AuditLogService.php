<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AuditLogService
{
    public static function log(string $action, ?Model $model = null, array $old = [], array $new = []): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $model?->getMorphClass(),
            'auditable_id' => $model?->getKey(),
            'old_values' => $old === [] ? null : json_encode($old, JSON_THROW_ON_ERROR),
            'new_values' => $new === [] ? null : json_encode($new, JSON_THROW_ON_ERROR),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
