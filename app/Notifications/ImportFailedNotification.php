<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class ImportFailedNotification extends Notification
{
    public function __construct(
        public string $csvPath,
        public string $errorMessage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'import_failed',
            'csv' => $this->csvPath,
            'error' => $this->errorMessage,
        ]);
    }
}
