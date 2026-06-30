<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    protected WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ]);
    }

    public function send(User $user, string $title, string $body, ?string $url = null): void
    {
        $queued = false;

        foreach ($user->pushSubscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
            ]);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'url' => $url ?? '/',
                'icon' => '/images/img1.png',
            ]);

            $this->webPush->queueNotification($subscription, $payload);
            $queued = true;
        }

        if (! $queued) {
            return;
        }

        try {
            foreach ($this->webPush->flush() as $report) {
                if (!$report->isSuccess() && $report->isSubscriptionExpired()) {
                    $endpoint = $report->getRequest()->getUri()->__toString();
                    PushSubscription::where('endpoint', $endpoint)->delete();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Push notification send failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendToMany(Collection $users, string $title, string $body, ?string $url = null): void
    {
        foreach ($users as $user) {
            $this->send($user, $title, $body, $url);
        }
    }
}
