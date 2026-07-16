<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QueueHealthAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $delayedCount,
        public ?string $drainReport = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $data = [
            'title' => 'Queue Health Alert',
            'body' => "{$this->delayedCount} delayed job(s) detected in the queue. Check the worker service.",
            'action_url' => '/admin',
            'delayed_count' => $this->delayedCount,
        ];

        if ($this->drainReport) {
            $data['drain_report'] = $this->drainReport;
            $data['body'] = "{$this->delayedCount} delayed job(s) were drained. See report.";
        }

        return $data;
    }
}
