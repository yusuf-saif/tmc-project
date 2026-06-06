<?php

namespace App\Filament\Resources\CommunitySpaceResource\Pages;

use App\Filament\Resources\CommunitySpaceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunitySpace extends CreateRecord
{
    protected static string $resource = CommunitySpaceResource::class;

    protected function afterCreate(): void
    {
        CommunitySpaceResource::logCreate($this->record);
    }
}
