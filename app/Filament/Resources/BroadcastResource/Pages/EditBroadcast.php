<?php

namespace App\Filament\Resources\BroadcastResource\Pages;

use App\Filament\Resources\BroadcastResource;
use App\Services\AuditLogService;
use Filament\Resources\Pages\EditRecord;

class EditBroadcast extends EditRecord
{
    protected static string $resource = BroadcastResource::class;

    protected function afterSave(): void
    {
        AuditLogService::log('broadcast_updated', $this->record, [], $this->record->only([
            'title', 'target_audience', 'status',
        ]));
    }
}
