<?php

namespace App\Filament\Resources\InAppAnnouncementResource\Pages;

use App\Filament\Resources\InAppAnnouncementResource;
use App\Services\AuditLogService;
use Filament\Resources\Pages\CreateRecord;

class CreateInAppAnnouncement extends CreateRecord
{
    protected static string $resource = InAppAnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        AuditLogService::log('in_app_announcement_created', $this->record, [], $this->record->only([
            'title', 'type', 'priority', 'status',
        ]));
    }
}
