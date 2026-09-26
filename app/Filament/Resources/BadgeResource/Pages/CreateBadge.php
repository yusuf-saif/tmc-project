<?php

namespace App\Filament\Resources\BadgeResource\Pages;

use App\Filament\Resources\BadgeResource;
use App\Models\Badge;
use Filament\Resources\Pages\CreateRecord;

class CreateBadge extends CreateRecord
{
    protected static string $resource = BadgeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['icon_path'] = Badge::normalizeIconPath($data['icon_path'] ?? null);

        return $data;
    }

    protected function afterCreate(): void
    {
        BadgeResource::logCreate($this->record);
    }
}
