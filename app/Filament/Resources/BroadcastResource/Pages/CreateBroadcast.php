<?php

namespace App\Filament\Resources\BroadcastResource\Pages;

use App\Filament\Resources\BroadcastResource;
use App\Services\AuditLogService;
use Filament\Resources\Pages\CreateRecord;

class CreateBroadcast extends CreateRecord
{
    protected static string $resource = BroadcastResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = 'queued';

        return $data;
    }

    protected function afterCreate(): void
    {
        AuditLogService::log('broadcast_created', $this->record, [], $this->record->only([
            'title', 'target_audience', 'status',
        ]));
    }
}
