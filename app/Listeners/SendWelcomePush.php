<?php

namespace App\Listeners;

use App\Events\MemberOnboardingCompleted;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;

class SendWelcomePush
{
    public function handle(MemberOnboardingCompleted $event): void
    {
        try {
            $nickname = $event->user->memberProfile?->display_name ?? $event->user->name;

            app(PushNotificationService::class)->send(
                $event->user,
                'Welcome to TMC',
                "Welcome to TMC, {$nickname}!",
                route('home'),
            );
        } catch (\Throwable $e) {
            Log::error('SendWelcomePush: failed', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
