<?php

namespace App\Listeners;

use App\Services\AuditLogService;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        AuditLogService::log(
            'login_failed',
            null,
            [],
            [
                'email' => $event->credentials['email'] ?? 'unknown',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        );
    }
}
