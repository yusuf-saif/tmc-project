<?php

namespace App\Filament\Resources\BadgeResource\Pages;

use App\Filament\Resources\BadgeResource;
use Filament\Resources\Pages\EditRecord;

class EditBadge extends EditRecord
{
    protected static string $resource = BadgeResource::class;

    protected array $oldValues = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldValues = $this->record->only(['name', 'is_active']);

        return $data;
    }

    protected function afterSave(): void
    {
        BadgeResource::logUpdate($this->record, $this->oldValues);
    }
}
