<?php

namespace App\Filament\Resources\CommunitySpaceResource\Pages;

use App\Filament\Resources\CommunitySpaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommunitySpaces extends ListRecords
{
    protected static string $resource = CommunitySpaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
