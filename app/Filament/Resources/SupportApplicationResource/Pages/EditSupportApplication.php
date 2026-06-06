<?php

namespace App\Filament\Resources\SupportApplicationResource\Pages;

use App\Filament\Resources\SupportApplicationResource;
use App\Services\AuditLogService;
use Filament\Resources\Pages\EditRecord;

class EditSupportApplication extends EditRecord
{
    protected static string $resource = SupportApplicationResource::class;

    protected array $oldValues = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldValues = $this->record->only(['status', 'admin_notes']);
        $data['reviewed_by'] = auth()->id();
        $data['reviewed_at'] = now();

        return $data;
    }

    protected function afterSave(): void
    {
        AuditLogService::log('support_application_updated', $this->record, $this->oldValues, $this->record->only(['status', 'admin_notes']));
    }
}
