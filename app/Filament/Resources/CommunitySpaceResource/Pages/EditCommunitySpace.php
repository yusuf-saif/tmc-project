<?php

namespace App\Filament\Resources\CommunitySpaceResource\Pages;

use App\Filament\Resources\CommunitySpaceResource;
use Filament\Resources\Pages\EditRecord;

class EditCommunitySpace extends EditRecord
{
    protected static string $resource = CommunitySpaceResource::class;

    protected array $oldValues = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldValues = $this->record->only(['name', 'is_active', 'is_youth_space', 'sort_order']);

        return $data;
    }

    protected function afterSave(): void
    {
        CommunitySpaceResource::logUpdate($this->record, $this->oldValues);
    }
}
