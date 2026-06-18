<?php

namespace App\Filament\Resources\NewsletterResource\Pages;

use App\Filament\Resources\NewsletterResource;
use App\Services\AuditLogService;
use Filament\Resources\Pages\EditRecord;

class EditNewsletter extends EditRecord
{
    protected static string $resource = NewsletterResource::class;

    protected function afterSave(): void
    {
        AuditLogService::log('newsletter_updated', $this->record, [], $this->record->only([
            'subject', 'target_audience', 'status',
        ]));
    }
}
