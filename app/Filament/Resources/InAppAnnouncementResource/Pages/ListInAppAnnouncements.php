<?php

namespace App\Filament\Resources\InAppAnnouncementResource\Pages;

use App\Filament\Resources\InAppAnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInAppAnnouncements extends ListRecords
{
    protected static string $resource = InAppAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
