<?php

namespace App\Filament\Resources\BadgeResource\Pages;

use App\Filament\Resources\BadgeResource;
use App\Models\Badge;
use Filament\Resources\Pages\EditRecord;

class EditBadge extends EditRecord
{
    protected static string $resource = BadgeResource::class;

    protected array $oldValues = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['icon_path'] = Badge::normalizeIconPath($data['icon_path'] ?? null);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldValues = $this->record->only(['name', 'is_active']);
        $data['icon_path'] = Badge::normalizeIconPath($data['icon_path'] ?? null);

        return $data;
    }

    protected function afterSave(): void
    {
        BadgeResource::logUpdate($this->record, $this->oldValues);
    }
}
