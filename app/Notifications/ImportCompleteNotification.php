<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class ImportCompleteNotification extends Notification
{
    public function __construct(
        public array $result,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'import_complete',
            'imported' => $this->result['imported'] ?? 0,
            'skipped' => $this->result['skipped'] ?? 0,
            'errors' => $this->result['errors'] ?? [],
            'skipped_emails' => $this->result['skipped_emails'] ?? [],
        ]);
    }
}
