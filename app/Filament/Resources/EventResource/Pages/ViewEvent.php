<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('rsvps')
                ->label('View RSVPs')
                ->url(fn (): string => EventResource::getUrl('rsvps', ['record' => $this->record])),
            Actions\EditAction::make(),
        ];
    }
}
