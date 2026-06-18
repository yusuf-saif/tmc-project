<?php

namespace App\Filament\Resources\InAppAnnouncementResource\Pages;

use App\Filament\Resources\InAppAnnouncementResource;
use App\Services\AuditLogService;
use Filament\Resources\Pages\EditRecord;

class EditInAppAnnouncement extends EditRecord
{
    protected static string $resource = InAppAnnouncementResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function afterSave(): void
    {
        AuditLogService::log('in_app_announcement_updated', $this->record, [], $this->record->only([
            'title', 'type', 'priority', 'status',
        ]));
    }
}
