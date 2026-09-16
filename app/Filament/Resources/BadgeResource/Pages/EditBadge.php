<?php

namespace App\Filament\Resources\BadgeResource\Pages;

use App\Filament\Resources\BadgeResource;
use Filament\Resources\Pages\EditRecord;

class EditBadge extends EditRecord
{
    protected static string $resource = BadgeResource::class;

    protected array $oldValues = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['icon_path'] = self::stripUrl($data['icon_path'] ?? null);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldValues = $this->record->only(['name', 'is_active']);
        $data['icon_path'] = self::stripUrl($data['icon_path'] ?? null);

        return $data;
    }

    protected function afterSave(): void
    {
        BadgeResource::logUpdate($this->record, $this->oldValues);
    }

    private static function stripUrl(?string $value): ?string
    {
        if (! $value || ! str_starts_with($value, 'http')) {
            return $value;
        }

        $parsed = parse_url($value);

        return ltrim($parsed['path'] ?? $value, '/');
    }
}
