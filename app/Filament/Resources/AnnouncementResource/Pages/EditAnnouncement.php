<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected array $oldValues = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldValues = $this->record->only(['title', 'status']);
        $data['updated_by'] = auth()->id();

        if ($data['status'] === 'published' && blank($this->record->published_at) && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        AnnouncementResource::logUpdate($this->record, $this->oldValues);
    }
}
