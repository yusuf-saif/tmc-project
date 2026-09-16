<?php

namespace App\Filament\Resources\BadgeResource\Pages;

use App\Filament\Resources\BadgeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBadge extends CreateRecord
{
    protected static string $resource = BadgeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['icon_path'] = self::stripUrl($data['icon_path'] ?? null);

        return $data;
    }

    protected function afterCreate(): void
    {
        BadgeResource::logCreate($this->record);
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
