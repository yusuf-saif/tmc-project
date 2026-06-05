<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use Filament\Resources\Pages\EditRecord;

class EditResource extends EditRecord
{
    protected static string $resource = ResourceResource::class;

    protected array $oldValues = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldValues = $this->record->only(['title', 'status', 'category', 'type']);
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function afterSave(): void
    {
        ResourceResource::logUpdate($this->record, $this->oldValues);
    }
}
